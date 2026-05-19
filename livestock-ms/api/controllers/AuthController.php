<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/jwt.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/upload.php';

class AuthController {
    private PDO $db;

    public function __construct() {
        $database   = new Database();
        $this->db   = $database->getConnection();
    }

    /**
     * POST /api/auth/login
     * Body: { "email": "", "password": "" }
     */
    public function login(): void {
        $body = $this->getBody();

        if (empty($body['email']) || empty($body['password'])) {
            Response::error('Email and password are required');
        }

        $login = trim($body['email']);
        $stmt = $this->db->prepare(
            "SELECT u.*, f.farmer_id, f.farm_name
             FROM user u
             LEFT JOIN farmers f ON f.user_id = u.user_id
             WHERE (u.user_email = ? OR u.username = ?)
             LIMIT 1"
        );
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($body['password'], $user['password_hash'])) {
            Response::error('Invalid credentials', 401);
        }

        if ($user['user_status'] === 'Pending') {
            Response::error('Account creation pending. Wait for admin to approve.', 403);
        }
        if ($user['user_status'] === 'Banned') {
            Response::error('Your account has been permanently banned.', 403);
        }
        if ($user['user_status'] === 'Suspended') {
            if (!empty($user['suspension_ends_at']) && strtotime($user['suspension_ends_at']) <= time()) {
                $this->db->prepare(
                    "UPDATE user SET user_status = 'Active', ban_type = 'none', suspension_ends_at = NULL, ban_reason = NULL WHERE user_id = ?"
                )->execute([$user['user_id']]);
                $user['user_status'] = 'Active';
            } else {
                $until = $user['suspension_ends_at'] ?? 'later';
                Response::error("Your account is temporarily suspended until {$until}.", 403);
            }
        }
        if ($user['user_status'] !== 'Active') {
            Response::error('Account is not active. Please contact support.', 403);
        }

        $payload = [
            'sub'       => $user['user_id'],
            'username'  => $user['username'],
            'email'     => $user['user_email'],
            'role'      => $user['user_role'],
            'farmer_id' => $user['farmer_id'],
            'iat'       => time(),
            'exp'       => time() + JWT_EXPIRY,
        ];

        $token = JWT::encode($payload);

        unset($user['password_hash'], $user['user_pfp']);
        Response::success([
            'token' => $token,
            'user'  => $user,
        ], 'Login successful');
    }

    /**
     * POST /api/auth/register
     * JSON for Buyer | multipart/form-data for Farmer (with PNG documents)
     */
    public function register(): void {
        $isMultipart = !empty($_FILES);

        if ($isMultipart) {
            $this->registerFarmerMultipart();
            return;
        }

        $body = $this->getBody();
        $required = ['email', 'password', 'first_name', 'last_name'];
        foreach ($required as $field) {
            if (empty($body[$field])) {
                Response::error("Field '{$field}' is required");
            }
        }

        if (!filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email format');
        }
        if (strlen($body['password']) < 6) {
            Response::error('Password must be at least 6 characters');
        }

        $stmt = $this->db->prepare("SELECT user_id FROM user WHERE user_email = ?");
        $stmt->execute([$body['email']]);
        if ($stmt->fetch()) {
            Response::error('Email already in use', 409);
        }

        $role = in_array($body['role'] ?? '', ['Farmer', 'Buyer']) ? $body['role'] : 'Buyer';
        if ($role === 'Farmer') {
            Response::error('Farmer registration requires document uploads. Use the registration form.');
        }

        $stmt = $this->db->prepare(
            "INSERT INTO user (username, user_email, user_phone_number, password_hash, user_role,
                               user_last_name, user_first_name, user_middle_name, user_status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Active')"
        );
        $stmt->execute([
            $body['username']    ?? '',
            $body['email'],
            $body['phone']       ?? null,
            password_hash($body['password'], PASSWORD_BCRYPT),
            $role,
            $body['last_name'],
            $body['first_name'],
            $body['middle_name'] ?? null,
        ]);

        Response::created(['user_id' => (int)$this->db->lastInsertId()], 'Registration successful');
    }

    private function registerFarmerMultipart(): void {
        $email      = trim($_POST['email'] ?? '');
        $password   = $_POST['password'] ?? '';
        $firstName  = trim($_POST['first_name'] ?? '');
        $lastName   = trim($_POST['last_name'] ?? '');
        $middleName = trim($_POST['middle_name'] ?? '') ?: null;
        $username   = trim($_POST['username'] ?? '');
        $phone      = trim($_POST['phone'] ?? '') ?: null;
        $farmName   = trim($_POST['farm_name'] ?? '') ?: ($firstName . "'s Farm");

        if (!$email || !$password || !$firstName || !$lastName) {
            Response::error('Required fields: email, password, first name, last name');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email format');
        }
        if (strlen($password) < 6) {
            Response::error('Password must be at least 6 characters');
        }
        if (empty($_FILES['valid_id']) || empty($_FILES['birth_cert'])) {
            Response::error('Valid I.D and Birth certificate PNG files are required');
        }

        $stmt = $this->db->prepare("SELECT user_id FROM user WHERE user_email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            Response::error('Email already in use', 409);
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "INSERT INTO user (username, user_email, user_phone_number, password_hash, user_role,
                                   user_last_name, user_first_name, user_middle_name, user_status)
                 VALUES (?, ?, ?, ?, 'Farmer', ?, ?, ?, 'Pending')"
            );
            $stmt->execute([
                $username,
                $email,
                $phone,
                password_hash($password, PASSWORD_BCRYPT),
                $lastName,
                $firstName,
                $middleName,
            ]);
            $userId = (int)$this->db->lastInsertId();

            $idPath    = saveFarmerPngUpload($_FILES['valid_id'], 'valid_id', $userId);
            $birthPath = saveFarmerPngUpload($_FILES['birth_cert'], 'birth_cert', $userId);

            $stmt = $this->db->prepare("INSERT INTO farmers (user_id, farm_name) VALUES (?, ?)");
            $stmt->execute([$userId, $farmName]);

            $stmt = $this->db->prepare(
                "INSERT INTO farmer_documents (user_id, valid_id_path, birth_cert_path) VALUES (?, ?, ?)"
            );
            $stmt->execute([$userId, $idPath, $birthPath]);

            $this->db->commit();
            Response::created(
                ['user_id' => $userId, 'pending' => true],
                'Account creation pending. Wait for admin to approve.'
            );
        } catch (Exception $e) {
            $this->db->rollBack();
            Response::error($e->getMessage());
        }
    }

    /**
     * GET /api/auth/me  (requires token)
     */
    public function me(array $authUser): void {
        $stmt = $this->db->prepare(
            "SELECT u.user_id, u.username, u.user_email, u.user_phone_number, u.user_role,
                    u.user_last_name, u.user_first_name, u.user_middle_name,
                    u.created_at, u.user_status,
                    f.farmer_id, f.farm_name
             FROM user u
             LEFT JOIN farmers f ON f.user_id = u.user_id
             WHERE u.user_id = ?"
        );
        $stmt->execute([$authUser['sub']]);
        $user = $stmt->fetch();
        if (!$user) {
            Response::notFound('User not found');
        }
        Response::success($user);
    }

    private function getBody(): array {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }
}
