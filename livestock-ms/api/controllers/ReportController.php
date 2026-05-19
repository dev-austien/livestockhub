<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';

class ReportController {
    private PDO $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    /** GET /api/reports — Admin: all open reports */
    public function index(array $authUser): void {
        if ($authUser['role'] !== 'Admin') {
            Response::forbidden();
        }

        $stmt = $this->db->query(
            "SELECT r.*,
                    ru.user_first_name AS reporter_first, ru.user_last_name AS reporter_last, ru.user_role AS reporter_role,
                    tu.user_first_name AS reported_first, tu.user_last_name AS reported_last,
                    tu.user_email AS reported_email, f.farm_name
             FROM reports r
             JOIN user ru ON ru.user_id = r.reporter_user_id
             JOIN user tu ON tu.user_id = r.reported_user_id
             LEFT JOIN farmers f ON f.user_id = tu.user_id
             WHERE r.status = 'open'
             ORDER BY r.created_at DESC"
        );
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row = array_merge($row, $this->userStats((int)$row['reported_user_id'], $row['reported_role']));
        }
        Response::success($rows);
    }

    /** GET /api/reports/search?q= — Farmer/Buyer lookup */
    public function search(array $authUser): void {
        if (!in_array($authUser['role'], ['Farmer', 'Buyer'], true)) {
            Response::forbidden();
        }

        $q = trim($_GET['q'] ?? '');
        if ($q === '') {
            Response::error('Search query is required');
        }

        $like = '%' . $q . '%';
        $stmt = $this->db->prepare(
            "SELECT u.user_id, u.user_first_name, u.user_last_name, u.user_email, u.user_role,
                    f.farm_name, f.farmer_id
             FROM user u
             LEFT JOIN farmers f ON f.user_id = u.user_id
             WHERE u.user_role IN ('Farmer','Buyer')
               AND u.user_status = 'Active'
               AND u.user_id != ?
               AND (
                 u.user_email LIKE ? OR
                 u.user_first_name LIKE ? OR
                 u.user_last_name LIKE ? OR
                 CONCAT(u.user_first_name,' ',u.user_last_name) LIKE ? OR
                 f.farm_name LIKE ?
               )
             LIMIT 1"
        );
        $stmt->execute([$authUser['sub'], $like, $like, $like, $like, $like]);
        $user = $stmt->fetch();
        if (!$user) {
            Response::notFound('No matching user found');
        }

        $stats = $this->userStats((int)$user['user_id'], $user['user_role']);
        Response::success(array_merge($user, $stats));
    }

    /** POST /api/reports — Submit a report */
    public function store(array $authUser): void {
        if (!in_array($authUser['role'], ['Farmer', 'Buyer'], true)) {
            Response::forbidden();
        }

        $body = $this->getBody();
        $reportedId = (int)($body['reported_user_id'] ?? 0);
        $description = trim($body['description'] ?? '');

        if (!$reportedId) {
            Response::error('reported_user_id is required');
        }
        if ($description === '') {
            Response::error('Description is required');
        }
        if ($reportedId === (int)$authUser['sub']) {
            Response::error('You cannot report yourself');
        }

        $stmt = $this->db->prepare(
            "SELECT user_id, user_role FROM user WHERE user_id = ? AND user_role IN ('Farmer','Buyer') AND user_status = 'Active'"
        );
        $stmt->execute([$reportedId]);
        $target = $stmt->fetch();
        if (!$target) {
            Response::notFound('User not found');
        }

        $stmt = $this->db->prepare(
            "INSERT INTO reports (reporter_user_id, reported_user_id, reported_role, description)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$authUser['sub'], $reportedId, $target['user_role'], $description]);

        Response::created(['report_id' => (int)$this->db->lastInsertId()], 'Report submitted');
    }

    private function userStats(int $userId, string $role): array {
        if ($role === 'Buyer') {
            $stmt = $this->db->prepare(
                "SELECT COALESCE(SUM(total_price),0) AS total_spent,
                        COUNT(*) AS completed_orders
                 FROM orders WHERE buyer_id = ? AND status = 'Completed'"
            );
            $stmt->execute([$userId]);
            $row = $stmt->fetch();
            return [
                'total_spent'       => (float)($row['total_spent'] ?? 0),
                'total_earning'     => 0,
                'completed_orders'  => (int)($row['completed_orders'] ?? 0),
            ];
        }

        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(o.total_price),0) AS total_earning,
                    COUNT(*) AS completed_orders
             FROM orders o
             JOIN livestock l ON l.livestock_id = o.livestock_id
             JOIN farmers f ON f.farmer_id = l.farmer_id
             WHERE f.user_id = ? AND o.status = 'Completed'"
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return [
            'total_spent'      => 0,
            'total_earning'    => (float)($row['total_earning'] ?? 0),
            'completed_orders' => (int)($row['completed_orders'] ?? 0),
        ];
    }

    private function getBody(): array {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }
}
