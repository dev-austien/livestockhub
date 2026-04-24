<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';

class LocationController {
    private PDO $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /** GET /api/locations */
    public function index(array $authUser): void {
        if ($authUser['role'] === 'Admin') {
            $stmt = $this->db->query(
                "SELECT l.*, f.farm_name, u.user_first_name, u.user_last_name
                 FROM location l
                 JOIN farmers f ON f.farmer_id = l.farmer_id
                 JOIN user u ON u.user_id = f.user_id
                 ORDER BY l.location_id"
            );
        } elseif ($authUser['role'] === 'Farmer') {
            $stmt = $this->db->prepare(
                "SELECT l.*
                 FROM location l
                 JOIN farmers f ON f.farmer_id = l.farmer_id
                 WHERE f.user_id = ?
                 ORDER BY l.location_id"
            );
            $stmt->execute([$authUser['sub']]);
        } else {
            // Buyers can list locations for browsing
            $stmt = $this->db->query(
                "SELECT l.location_id, l.location_name, l.location_type,
                        l.location_brgy, l.location_city_muni, l.location_province,
                        l.location_latitude, l.location_longitude, l.capacity, f.farm_name
                 FROM location l JOIN farmers f ON f.farmer_id = l.farmer_id
                 ORDER BY l.location_id"
            );
        }
        Response::success($stmt->fetchAll());
    }

    /** GET /api/locations/{id} */
    public function show(int $id): void {
        $stmt = $this->db->prepare(
            "SELECT l.*, f.farm_name FROM location l
             JOIN farmers f ON f.farmer_id = l.farmer_id
             WHERE l.location_id = ?"
        );
        $stmt->execute([$id]);
        $loc = $stmt->fetch();
        if (!$loc) Response::notFound('Location not found');
        Response::success($loc);
    }

    /** POST /api/locations — Farmer/Admin */
    public function store(array $authUser): void {
        if (!in_array($authUser['role'], ['Farmer', 'Admin'])) Response::forbidden();
        $body = $this->getBody();

        if (empty($body['location_name'])) Response::error('location_name is required');

        // Determine farmer_id
        $farmerId = $this->resolveFarmerId($authUser, $body);

        $stmt = $this->db->prepare(
            "INSERT INTO location (farmer_id, location_name, description, location_type,
                                   location_brgy, location_city_muni, location_province,
                                   location_latitude, location_longitude, capacity)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $farmerId,
            $body['location_name'],
            $body['description']         ?? null,
            $body['location_type']        ?? null,
            $body['location_brgy']        ?? null,
            $body['location_city_muni']   ?? null,
            $body['location_province']    ?? null,
            $body['location_latitude']    ?? null,
            $body['location_longitude']   ?? null,
            $body['capacity']             ?? 0,
        ]);
        Response::created(['location_id' => (int)$this->db->lastInsertId()], 'Location created');
    }

    /** PUT /api/locations/{id} — Farmer/Admin */
    public function update(array $authUser, int $id): void {
        $this->ownerOrAdmin($authUser, $id);
        $body = $this->getBody();

        $cols = ['location_name','description','location_type','location_brgy',
                 'location_city_muni','location_province','location_latitude',
                 'location_longitude','capacity'];
        $fields = [];
        $params = [];
        foreach ($cols as $col) {
            if (array_key_exists($col, $body)) {
                $fields[] = "$col = ?";
                $params[] = $body[$col];
            }
        }
        if (empty($fields)) Response::error('No fields to update');

        $params[] = $id;
        $this->db->prepare("UPDATE location SET " . implode(', ', $fields) . " WHERE location_id = ?")->execute($params);
        Response::success(null, 'Location updated');
    }

    /** DELETE /api/locations/{id} — Farmer/Admin */
    public function delete(array $authUser, int $id): void {
        $this->ownerOrAdmin($authUser, $id);
        $this->db->prepare("DELETE FROM location WHERE location_id = ?")->execute([$id]);
        Response::success(null, 'Location deleted');
    }

    private function ownerOrAdmin(array $authUser, int $id): void {
        $stmt = $this->db->prepare(
            "SELECT l.*, f.user_id FROM location l
             JOIN farmers f ON f.farmer_id = l.farmer_id
             WHERE l.location_id = ?"
        );
        $stmt->execute([$id]);
        $loc = $stmt->fetch();
        if (!$loc) Response::notFound('Location not found');

        if ($authUser['role'] !== 'Admin' && (int)$loc['user_id'] !== $authUser['sub']) {
            Response::forbidden();
        }
    }

    private function resolveFarmerId(array $authUser, array $body): int {
        if ($authUser['role'] === 'Admin' && !empty($body['farmer_id'])) {
            return (int)$body['farmer_id'];
        }
        $stmt = $this->db->prepare("SELECT farmer_id FROM farmers WHERE user_id = ?");
        $stmt->execute([$authUser['sub']]);
        $farmer = $stmt->fetch();
        if (!$farmer) Response::error('Farmer profile not found', 404);
        return (int)$farmer['farmer_id'];
    }

    private function getBody(): array {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }
}
