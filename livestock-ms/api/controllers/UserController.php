<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';

class UserController {
    private PDO $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * GET /api/users  — Admin: all users | Others: own profile
     */
    public function index(array $authUser): void {
        if ($authUser['role'] === 'Admin') {
            $stmt = $this->db->query(
                "SELECT user_id, username, user_email, user_phone_number, user_role,
                        user_last_name, user_first_name, user_middle_name, created_at, user_status
                 FROM user ORDER BY created_at DESC"
            );
            Response::success($stmt->fetchAll());
        } else {
            $this->show($authUser, $authUser['sub']);
        }
    }

    /**
     * GET /api/users/{id}
     */
    public function show(array $authUser, int $id): void {
        // Non-admins can only view themselves
        if ($authUser['role'] !== 'Admin' && $authUser['sub'] !== $id) {
            Response::forbidden();
        }
        $stmt = $this->db->prepare(
            "SELECT u.user_id, u.username, u.user_email, u.user_phone_number, u.user_role,
                    u.user_last_name, u.user_first_name, u.user_middle_name, u.created_at, u.user_status,
                    f.farmer_id, f.farm_name
             FROM user u
             LEFT JOIN farmers f ON f.user_id = u.user_id
             WHERE u.user_id = ?"
        );
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user) Response::notFound('User not found');
        Response::success($user);
    }

    /**
     * PUT /api/users/{id}
     */
    public function update(array $authUser, int $id): void {
        if ($authUser['role'] !== 'Admin' && $authUser['sub'] !== $id) {
            Response::forbidden();
        }

        $body = $this->getBody();
        $fields = [];
        $params = [];

        $allowed = ['username', 'user_phone_number', 'user_last_name', 'user_first_name', 'user_middle_name'];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $body)) {
                $fields[] = "$f = ?";
                $params[] = $body[$f];
            }
        }

        // Admin can update role and status
        if ($authUser['role'] === 'Admin') {
            if (!empty($body['user_role']) && in_array($body['user_role'], ['Admin', 'Farmer', 'Buyer'])) {
                $fields[] = "user_role = ?";
                $params[] = $body['user_role'];
            }
            if (!empty($body['user_status']) && in_array($body['user_status'], ['Active', 'Suspended', 'Inactive'])) {
                $fields[] = "user_status = ?";
                $params[] = $body['user_status'];
            }
        }

        // Password change
        if (!empty($body['password'])) {
            if (strlen($body['password']) < 6) Response::error('Password must be at least 6 characters');
            $fields[] = "password_hash = ?";
            $params[] = password_hash($body['password'], PASSWORD_BCRYPT);
        }

        if (empty($fields)) Response::error('No fields to update');

        $params[] = $id;
        $this->db->prepare("UPDATE user SET " . implode(', ', $fields) . " WHERE user_id = ?")->execute($params);
        Response::success(null, 'User updated successfully');
    }

    /**
     * DELETE /api/users/{id}  — Admin only
     */
    public function delete(array $authUser, int $id): void {
        if ($authUser['role'] !== 'Admin') Response::forbidden();
        if ($authUser['sub'] === $id) Response::error('Cannot delete yourself');

        $stmt = $this->db->prepare("SELECT user_id FROM user WHERE user_id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) Response::notFound('User not found');

        $this->db->prepare("DELETE FROM user WHERE user_id = ?")->execute([$id]);
        Response::success(null, 'User deleted');
    }

    /**
     * PATCH /api/users/{id}/status  — Admin only
     */
    public function updateStatus(array $authUser, int $id): void {
        if ($authUser['role'] !== 'Admin') Response::forbidden();
        $body = $this->getBody();
        if (!in_array($body['status'] ?? '', ['Active', 'Suspended', 'Inactive'])) {
            Response::error('Invalid status value');
        }
        $this->db->prepare("UPDATE user SET user_status = ? WHERE user_id = ?")->execute([$body['status'], $id]);
        Response::success(null, 'Status updated');
    }

    private function getBody(): array {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }
}
