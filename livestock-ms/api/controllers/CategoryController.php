<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';

class CategoryController {
    private PDO $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /** GET /api/categories */
    public function index(): void {
        $stmt = $this->db->query("SELECT * FROM category ORDER BY category_name");
        Response::success($stmt->fetchAll());
    }

    /** GET /api/categories/{id} */
    public function show(int $id): void {
        $stmt = $this->db->prepare("SELECT * FROM category WHERE category_id = ?");
        $stmt->execute([$id]);
        $cat = $stmt->fetch();
        if (!$cat) Response::notFound('Category not found');
        Response::success($cat);
    }

    /** POST /api/categories — Admin only */
    public function store(array $authUser): void {
        if ($authUser['role'] !== 'Admin') Response::forbidden();
        $body = $this->getBody();

        if (empty($body['category_name'])) Response::error('category_name is required');

        $stmt = $this->db->prepare("INSERT INTO category (category_name, description) VALUES (?, ?)");
        $stmt->execute([$body['category_name'], $body['description'] ?? null]);
        Response::created(['category_id' => (int)$this->db->lastInsertId()], 'Category created');
    }

    /** PUT /api/categories/{id} — Admin only */
    public function update(array $authUser, int $id): void {
        if ($authUser['role'] !== 'Admin') Response::forbidden();
        $body = $this->getBody();

        if (empty($body['category_name'])) Response::error('category_name is required');

        $stmt = $this->db->prepare("UPDATE category SET category_name = ?, description = ? WHERE category_id = ?");
        $stmt->execute([$body['category_name'], $body['description'] ?? null, $id]);
        if ($stmt->rowCount() === 0) Response::notFound('Category not found');
        Response::success(null, 'Category updated');
    }

    /** DELETE /api/categories/{id} — Admin only */
    public function delete(array $authUser, int $id): void {
        if ($authUser['role'] !== 'Admin') Response::forbidden();
        $stmt = $this->db->prepare("DELETE FROM category WHERE category_id = ?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) Response::notFound('Category not found');
        Response::success(null, 'Category deleted');
    }

    private function getBody(): array {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }
}
