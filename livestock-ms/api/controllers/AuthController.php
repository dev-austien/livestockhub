<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/jwt.php';
require_once __DIR__ . '/../helpers/response.php';

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

        $stmt = $this->db->prepare(
            "SELECT u.*, f.farmer_id, f.farm_name
             FROM user u
             LEFT JOIN farmers f ON f.user_id = u.user_id
             WHERE u.user_email = ? AND u.user_status = 'Active'
             LIMIT 1"
        );
        $stmt->execute([trim($body['email'])]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($body['password'], $user['password_hash'])) {
            Response::error('Invalid credentials', 401);
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
     * Body: { "username", "email", "password", "first_name", "last_name", "middle_name", "phone", "role", "farm_name" }
     */
    public function register(): void {
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

        // Check email uniqueness
        $stmt = $this->db->prepare("SELECT user_id FROM user WHERE user_email = ?");
        $stmt->execute([$body['email']]);
        if ($stmt->fetch()) Response::error('Email already in use', 409);

        $role = in_array($body['role'] ?? '', ['Farmer', 'Buyer']) ? $body['role'] : 'Buyer';

        $stmt = $this->db->prepare(
            "INSERT INTO user (username, user_email, user_phone_number, password_hash, user_role,
                               user_last_name, user_first_name, user_middle_name)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
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
        $userId = (int)$this->db->lastInsertId();

        // Auto-create farmer record if role is Farmer
        if ($role === 'Farmer') {
            $farmName = $body['farm_name'] ?? ($body['first_name'] . "'s Farm");
            $stmt = $this->db->prepare("INSERT INTO farmers (user_id, farm_name) VALUES (?, ?)");
            $stmt->execute([$userId, $farmName]);
        }

        Response::created(['user_id' => $userId], 'Registration successful');
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
        if (!$user) Response::notFound('User not found');
        Response::success($user);
    }

    private function getBody(): array {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }
}
