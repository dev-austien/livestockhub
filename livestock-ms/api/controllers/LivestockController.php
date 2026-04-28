<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';

class LivestockController {
    private PDO $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * GET /api/livestock
     * Query params: farmer_id, category_id, breed_id, sale_status, gender, location_id, search
     */
    public function index(array $authUser): void {
        $where  = [];
        $params = [];

        // Farmers see only their own livestock by default
        if ($authUser['role'] === 'Farmer') {
            $where[]  = "f.user_id = ?";
            $params[] = $authUser['sub'];
        }

        // Optional filters
        $filters = [
            'farmer_id'   => 'l.farmer_id',
            'category_id' => 'l.category_id',
            'breed_id'    => 'l.breed_id',
            'location_id' => 'l.location_id',
            'sale_status' => 'l.sale_status',
            'gender'      => 'l.gender',
        ];
        foreach ($filters as $param => $col) {
            if (!empty($_GET[$param])) {
                $where[]  = "$col = ?";
                $params[] = $_GET[$param];
            }
        }
        if (!empty($_GET['search'])) {
            $where[]  = "(l.tag_number LIKE ? OR l.description LIKE ?)";
            $like     = '%' . $_GET['search'] . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT l.*,
                       c.category_name, b.breed_name,
                       loc.location_name, loc.location_city_muni,
                       f.farm_name, u.user_first_name, u.user_last_name
                FROM livestock l
                LEFT JOIN category c  ON c.category_id  = l.category_id
                LEFT JOIN breeds b    ON b.breed_id      = l.breed_id
                LEFT JOIN location loc ON loc.location_id = l.location_id
                LEFT JOIN farmers f   ON f.farmer_id     = l.farmer_id
                LEFT JOIN user u      ON u.user_id        = f.user_id
                $whereSQL
                ORDER BY l.date_created DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        Response::success($stmt->fetchAll());
    }

    /** GET /api/livestock/{id} */
    public function show(int $id): void {
        $stmt = $this->db->prepare(
            "SELECT l.*,
                    c.category_name, b.breed_name,
                    loc.location_name, loc.location_brgy, loc.location_city_muni, loc.location_province,
                    f.farm_name, f.farmer_id,
                    u.user_first_name, u.user_last_name, u.user_email, u.user_phone_number
             FROM livestock l
             LEFT JOIN category c   ON c.category_id   = l.category_id
             LEFT JOIN breeds b     ON b.breed_id       = l.breed_id
             LEFT JOIN location loc ON loc.location_id  = l.location_id
             LEFT JOIN farmers f    ON f.farmer_id      = l.farmer_id
             LEFT JOIN user u       ON u.user_id        = f.user_id
             WHERE l.livestock_id = ?"
        );
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) Response::notFound('Livestock not found');
        Response::success($item);
    }

    /** POST /api/livestock — Farmer/Admin */
    public function store(array $authUser): void {
        if (!in_array($authUser['role'], ['Farmer', 'Admin'])) Response::forbidden();

        // Handle multipart form data OR JSON
        $body = $this->getBody();

        $required = ['tag_number', 'category_id', 'gender'];
        foreach ($required as $f) {
            if (empty($body[$f])) Response::error("Field '$f' is required");
        }

        $farmerId = $this->resolveFarmerId($authUser, $body);
        $imagePath = $this->handleImageUpload();

        $stmt = $this->db->prepare(
            "INSERT INTO livestock (tag_number, farmer_id, location_id, category_id, breed_id,
                                    gender, health_status, date_of_birth, sale_status,
                                    price, description, livestock_image, current_weight)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $body['tag_number'],
            $farmerId,
            $body['location_id']    ?? null,
            (int)$body['category_id'],
            $body['breed_id']        ?? null,
            $body['gender'],
            $body['health_status']   ?? null,
            $body['date_of_birth']   ?? null,
            $body['sale_status']     ?? 'Available',
            $body['price']           ?? 0.00,
            $body['description']     ?? null,
            $imagePath,
            $body['current_weight']  ?? null,
        ]);

        $livestockId = (int)$this->db->lastInsertId();

        // Log initial weight if provided
        if (!empty($body['current_weight'])) {
            $this->db->prepare(
                "INSERT INTO livestock_weight (livestock_id, weight) VALUES (?, ?)"
            )->execute([$livestockId, $body['current_weight']]);
        }

        Response::created(['livestock_id' => $livestockId], 'Livestock added');
    }

    /** PUT /api/livestock/{id} — Farmer (owner) / Admin */
    public function update(array $authUser, int $id): void {
        $this->ownerOrAdmin($authUser, $id);
        $body = $this->getBody();

        $cols = ['tag_number','location_id','category_id','breed_id','gender',
                 'health_status','date_of_birth','sale_status','price','description','current_weight'];
        $fields = [];
        $params = [];
        foreach ($cols as $col) {
            if (array_key_exists($col, $body)) {
                $fields[] = "$col = ?";
                $params[] = $body[$col];
            }
        }

        // Handle image replacement
        $imagePath = $this->handleImageUpload();
        if ($imagePath) {
            $fields[] = "livestock_image = ?";
            $params[] = $imagePath;
        }

        if (empty($fields)) Response::error('No fields to update');

        $params[] = $id;
        $this->db->prepare("UPDATE livestock SET " . implode(', ', $fields) . " WHERE livestock_id = ?")->execute($params);

        // Log new weight entry if weight changed
        if (!empty($body['current_weight'])) {
            $this->db->prepare(
                "INSERT INTO livestock_weight (livestock_id, weight) VALUES (?, ?)"
            )->execute([$id, $body['current_weight']]);
        }

        Response::success(null, 'Livestock updated');
    }

    /** DELETE /api/livestock/{id} — Farmer (owner) / Admin */
    public function delete(array $authUser, int $id): void {
        $this->ownerOrAdmin($authUser, $id);
        $this->db->prepare("DELETE FROM livestock WHERE livestock_id = ?")->execute([$id]);
        Response::success(null, 'Livestock deleted');
    }

    /** GET /api/livestock/{id}/weights */
    public function weights(int $id): void {
        $stmt = $this->db->prepare(
            "SELECT * FROM livestock_weight WHERE livestock_id = ? ORDER BY date_recorded DESC"
        );
        $stmt->execute([$id]);
        Response::success($stmt->fetchAll());
    }

    /** POST /api/livestock/{id}/weights — Farmer/Admin */
    public function addWeight(array $authUser, int $id): void {
        $this->ownerOrAdmin($authUser, $id);
        $body = $this->getBody();

        if (empty($body['weight'])) Response::error('weight is required');

        $this->db->prepare(
            "INSERT INTO livestock_weight (livestock_id, weight) VALUES (?, ?)"
        )->execute([$id, $body['weight']]);

        // Update current_weight on the livestock record
        $this->db->prepare(
            "UPDATE livestock SET current_weight = ? WHERE livestock_id = ?"
        )->execute([$body['weight'], $id]);

        Response::created(['weight_id' => (int)$this->db->lastInsertId()], 'Weight recorded');
    }

    /** DELETE /api/livestock/{id}/weights/{weight_id} — Admin/Farmer */
    public function deleteWeight(array $authUser, int $id, int $weightId): void {
        $this->ownerOrAdmin($authUser, $id);
        $stmt = $this->db->prepare("DELETE FROM livestock_weight WHERE weight_id = ? AND livestock_id = ?");
        $stmt->execute([$weightId, $id]);
        if ($stmt->rowCount() === 0) Response::notFound('Weight record not found');
        Response::success(null, 'Weight record deleted');
    }

    // ──── Helpers ────────────────────────────────────────────────

    private function ownerOrAdmin(array $authUser, int $id): array {
        $stmt = $this->db->prepare(
            "SELECT l.*, f.user_id FROM livestock l
             JOIN farmers f ON f.farmer_id = l.farmer_id
             WHERE l.livestock_id = ?"
        );
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        if (!$item) Response::notFound('Livestock not found');

        if ($authUser['role'] !== 'Admin' && (int)$item['user_id'] !== $authUser['sub']) {
            Response::forbidden();
        }
        return $item;
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

    private function handleImageUpload(): ?string {
        if (empty($_FILES['livestock_image']['tmp_name'])) return null;

        $uploadDir = __DIR__ . '/../../uploads/livestock/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $ext      = strtolower(pathinfo($_FILES['livestock_image']['name'], PATHINFO_EXTENSION));
        $allowed  = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed)) Response::error('Invalid image type');

        $filename = uniqid('ls_', true) . '.' . $ext;
        $dest     = $uploadDir . $filename;

        if (!move_uploaded_file($_FILES['livestock_image']['tmp_name'], $dest)) {
            Response::error('Image upload failed');
        }
        return 'uploads/livestock/' . $filename;
    }

    private function getBody(): array {
        $body = json_decode(file_get_contents('php://input'), true);
        if ($body) return $body;
        // Fall back to POST data (multipart form)
        return array_merge($_POST, []);
    }
}
