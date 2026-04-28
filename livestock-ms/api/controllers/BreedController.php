<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';

class BreedController {
    private PDO $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /** GET /api/breeds?category_id=1 */
    public function index(): void {
        $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;

        if ($categoryId) {
            $stmt = $this->db->prepare(
                "SELECT b.*, c.category_name FROM breeds b
                 JOIN category c ON c.category_id = b.category_id
                 WHERE b.category_id = ? ORDER BY b.breed_name"
            );
            $stmt->execute([$categoryId]);
        } else {
            $stmt = $this->db->query(
                "SELECT b.*, c.category_name FROM breeds b
                 JOIN category c ON c.category_id = b.category_id
                 ORDER BY c.category_name, b.breed_name"
            );
        }
        Response::success($stmt->fetchAll());
    }

    /** GET /api/breeds/{id} */
    public function show(int $id): void {
        $stmt = $this->db->prepare(
            "SELECT b.*, c.category_name FROM breeds b
             JOIN category c ON c.category_id = b.category_id
             WHERE b.breed_id = ?"
        );
        $stmt->execute([$id]);
        $breed = $stmt->fetch();
        if (!$breed) Response::notFound('Breed not found');
        Response::success($breed);
    }

    /** POST /api/breeds — Admin only */
    public function store(array $authUser): void {
        if ($authUser['role'] !== 'Admin') Response::forbidden();
        $body = $this->getBody();

        if (empty($body['breed_name']) || empty($body['category_id'])) {
            Response::error('breed_name and category_id are required');
        }

        $stmt = $this->db->prepare("INSERT INTO breeds (category_id, breed_name) VALUES (?, ?)");
        $stmt->execute([(int)$body['category_id'], $body['breed_name']]);
        Response::created(['breed_id' => (int)$this->db->lastInsertId()], 'Breed created');
    }

    /** PUT /api/breeds/{id} — Admin only */
    public function update(array $authUser, int $id): void {
        if ($authUser['role'] !== 'Admin') Response::forbidden();
        $body = $this->getBody();

        $fields = [];
        $params = [];
        if (!empty($body['breed_name']))  { $fields[] = "breed_name = ?";  $params[] = $body['breed_name']; }
        if (!empty($body['category_id'])) { $fields[] = "category_id = ?"; $params[] = (int)$body['category_id']; }

        if (empty($fields)) Response::error('No fields to update');

        $params[] = $id;
        $this->db->prepare("UPDATE breeds SET " . implode(', ', $fields) . " WHERE breed_id = ?")->execute($params);
        Response::success(null, 'Breed updated');
    }

    /** DELETE /api/breeds/{id} — Admin only */
    public function delete(array $authUser, int $id): void {
        if ($authUser['role'] !== 'Admin') Response::forbidden();
        $stmt = $this->db->prepare("DELETE FROM breeds WHERE breed_id = ?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) Response::notFound('Breed not found');
        Response::success(null, 'Breed deleted');
    }

    private function getBody(): array {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }
}
