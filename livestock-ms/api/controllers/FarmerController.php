<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';

class FarmerController {
    private PDO $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /** GET /api/farmers */
    public function index(array $authUser): void {
        if ($authUser['role'] === 'Admin') {
            $stmt = $this->db->query(
                "SELECT f.farmer_id, f.farm_name,
                        u.user_id, u.user_first_name, u.user_last_name, u.user_email, u.user_phone_number, u.user_status
                 FROM farmers f
                 JOIN user u ON u.user_id = f.user_id
                 ORDER BY f.farmer_id"
            );
        } else {
            $stmt = $this->db->prepare(
                "SELECT f.farmer_id, f.farm_name,
                        u.user_id, u.user_first_name, u.user_last_name, u.user_email, u.user_phone_number
                 FROM farmers f
                 JOIN user u ON u.user_id = f.user_id
                 WHERE f.user_id = ?"
            );
            $stmt->execute([$authUser['sub']]);
        }
        Response::success($stmt->fetchAll());
    }

    /** GET /api/farmers/{id} */
    public function show(array $authUser, int $id): void {
        $stmt = $this->db->prepare(
            "SELECT f.farmer_id, f.farm_name,
                    u.user_id, u.user_first_name, u.user_last_name, u.user_email, u.user_phone_number
             FROM farmers f
             JOIN user u ON u.user_id = f.user_id
             WHERE f.farmer_id = ?"
        );
        $stmt->execute([$id]);
        $farmer = $stmt->fetch();
        if (!$farmer) Response::notFound('Farmer not found');
        Response::success($farmer);
    }

    /** PUT /api/farmers/{id} */
    public function update(array $authUser, int $id): void {
        $farmer = $this->ownerOrAdmin($authUser, $id);
        $body = $this->getBody();

        if (empty($body['farm_name'])) Response::error('farm_name is required');

        $this->db->prepare("UPDATE farmers SET farm_name = ? WHERE farmer_id = ?")->execute([$body['farm_name'], $id]);
        Response::success(null, 'Farm updated');
    }

    /** GET /api/farmers/{id}/contacts */
    public function contacts(array $authUser, int $id): void {
        $stmt = $this->db->prepare("SELECT * FROM farmers_contact WHERE farmer_id = ?");
        $stmt->execute([$id]);
        Response::success($stmt->fetchAll());
    }

    /** POST /api/farmers/{id}/contacts */
    public function addContact(array $authUser, int $id): void {
        $this->ownerOrAdmin($authUser, $id);
        $body = $this->getBody();

        if (empty($body['contact_type']) || empty($body['contact_value'])) {
            Response::error('contact_type and contact_value are required');
        }

        $stmt = $this->db->prepare(
            "INSERT INTO farmers_contact (farmer_id, contact_type, contact_value) VALUES (?, ?, ?)"
        );
        $stmt->execute([$id, $body['contact_type'], $body['contact_value']]);
        Response::created(['contact_id' => (int)$this->db->lastInsertId()], 'Contact added');
    }

    /** DELETE /api/farmers/{id}/contacts/{contact_id} */
    public function deleteContact(array $authUser, int $id, int $contactId): void {
        $this->ownerOrAdmin($authUser, $id);
        $stmt = $this->db->prepare("DELETE FROM farmers_contact WHERE contact_id = ? AND farmer_id = ?");
        $stmt->execute([$contactId, $id]);
        if ($stmt->rowCount() === 0) Response::notFound('Contact not found');
        Response::success(null, 'Contact deleted');
    }

    /** Ensure caller owns this farmer record or is Admin */
    private function ownerOrAdmin(array $authUser, int $farmerId): array {
        $stmt = $this->db->prepare("SELECT * FROM farmers WHERE farmer_id = ?");
        $stmt->execute([$farmerId]);
        $farmer = $stmt->fetch();
        if (!$farmer) Response::notFound('Farmer not found');

        if ($authUser['role'] !== 'Admin' && (int)$farmer['user_id'] !== $authUser['sub']) {
            Response::forbidden();
        }
        return $farmer;
    }

    private function getBody(): array {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }
}
