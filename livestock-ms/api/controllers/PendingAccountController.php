<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/mail.php';

class PendingAccountController {
    private PDO $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    /** GET /api/pending-accounts */
    public function index(array $authUser): void {
        $this->requireAdmin($authUser);

        $stmt = $this->db->query(
            "SELECT u.user_id, u.username, u.user_email, u.user_phone_number,
                    u.user_first_name, u.user_last_name, u.user_middle_name, u.created_at,
                    f.farm_name, fd.valid_id_path, fd.birth_cert_path
             FROM user u
             LEFT JOIN farmers f ON f.user_id = u.user_id
             LEFT JOIN farmer_documents fd ON fd.user_id = u.user_id
             WHERE u.user_status = 'Pending' AND u.user_role = 'Farmer'
             ORDER BY u.created_at ASC"
        );
        Response::success($stmt->fetchAll());
    }

    /** GET /api/pending-accounts/{id} */
    public function show(array $authUser, int $id): void {
        $this->requireAdmin($authUser);
        $account = $this->findPending($id);
        Response::success($account);
    }

    /** POST /api/pending-accounts/{id}/approve */
    public function approve(array $authUser, int $id): void {
        $this->requireAdmin($authUser);
        $account = $this->findPending($id);

        $this->db->prepare("UPDATE user SET user_status = 'Active' WHERE user_id = ?")->execute([$id]);

        $email = $account['user_email'];
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendUserEmail($email, 'LivestockHub — Account Approved',
                "Your account is approved, please Log in to continue using.");
        }

        Response::success(null, 'Account approved');
    }

    /** POST /api/pending-accounts/{id}/decline */
    public function decline(array $authUser, int $id): void {
        $this->requireAdmin($authUser);
        $account = $this->findPending($id);

        $email = $account['user_email'];
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendUserEmail($email, 'LivestockHub — Account Declined',
                'Account creation declined.');
        }

        $this->db->prepare("DELETE FROM user WHERE user_id = ?")->execute([$id]);

        Response::success(null, 'Account declined');
    }

    private function findPending(int $id): array {
        $stmt = $this->db->prepare(
            "SELECT u.user_id, u.username, u.user_email, u.user_phone_number,
                    u.user_first_name, u.user_last_name, u.user_middle_name, u.created_at,
                    f.farm_name, fd.valid_id_path, fd.birth_cert_path
             FROM user u
             LEFT JOIN farmers f ON f.user_id = u.user_id
             LEFT JOIN farmer_documents fd ON fd.user_id = u.user_id
             WHERE u.user_id = ? AND u.user_status = 'Pending' AND u.user_role = 'Farmer'"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            Response::notFound('Pending account not found');
        }
        return $row;
    }

    private function requireAdmin(array $authUser): void {
        if ($authUser['role'] !== 'Admin') {
            Response::forbidden();
        }
    }
}
