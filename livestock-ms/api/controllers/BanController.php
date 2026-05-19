<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';

class BanController {
    private PDO $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    /** POST /api/bans */
    public function store(array $authUser): void {
        if ($authUser['role'] !== 'Admin') {
            Response::forbidden();
        }

        $body = $this->getBody();
        $userId   = (int)($body['user_id'] ?? 0);
        $banType  = $body['ban_type'] ?? '';
        $hours    = (int)($body['hours'] ?? 0);
        $reason   = trim($body['reason'] ?? '');
        $reportId = (int)($body['report_id'] ?? 0);

        if (!$userId) {
            Response::error('user_id is required');
        }
        if (!in_array($banType, ['temporary', 'permanent'], true)) {
            Response::error("ban_type must be 'temporary' or 'permanent'");
        }
        if ($reason === '') {
            Response::error('Reason is required');
        }
        if ($banType === 'temporary' && $hours < 1) {
            Response::error('Hours must be at least 1 for temporary suspension');
        }

        $stmt = $this->db->prepare("SELECT user_id, user_role FROM user WHERE user_id = ?");
        $stmt->execute([$userId]);
        $target = $stmt->fetch();
        if (!$target) {
            Response::notFound('User not found');
        }
        if ($target['user_role'] === 'Admin') {
            Response::error('Cannot ban an admin account');
        }

        if ($banType === 'temporary') {
            $this->db->prepare(
                "UPDATE user SET user_status = 'Suspended', ban_type = 'temporary',
                 suspension_ends_at = DATE_ADD(NOW(), INTERVAL ? HOUR), ban_reason = ?
                 WHERE user_id = ?"
            )->execute([$hours, $reason, $userId]);
        } else {
            $this->db->prepare(
                "UPDATE user SET user_status = 'Banned', ban_type = 'permanent',
                 suspension_ends_at = NULL, ban_reason = ?
                 WHERE user_id = ?"
            )->execute([$reason, $userId]);
        }

        $this->db->prepare(
            "INSERT INTO user_bans (user_id, admin_id, ban_type, hours, reason) VALUES (?, ?, ?, ?, ?)"
        )->execute([$userId, $authUser['sub'], $banType, $banType === 'temporary' ? $hours : null, $reason]);

        if ($reportId > 0) {
            $this->db->prepare("UPDATE reports SET status = 'resolved' WHERE report_id = ?")->execute([$reportId]);
        }

        Response::success(null, 'Ban applied successfully');
    }

    private function getBody(): array {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }
}
