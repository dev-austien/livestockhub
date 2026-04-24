<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';

class TransactionController {
    private PDO $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * GET /api/transactions
     * Admin: all | Farmer: transactions on their livestock orders | Buyer: own transactions
     */
    public function index(array $authUser): void {
        if ($authUser['role'] === 'Admin') {
            $stmt = $this->db->query($this->baseQuery());
            Response::success($stmt->fetchAll());
            return;
        }

        if ($authUser['role'] === 'Farmer') {
            $stmt = $this->db->prepare(
                $this->baseQuery("o.livestock_id IN (
                    SELECT livestock_id FROM livestock l
                    JOIN farmers f ON f.farmer_id = l.farmer_id
                    WHERE f.user_id = ?
                )")
            );
        } else {
            $stmt = $this->db->prepare($this->baseQuery("o.buyer_id = ?"));
        }
        $stmt->execute([$authUser['sub']]);
        Response::success($stmt->fetchAll());
    }

    /** GET /api/transactions/{id} */
    public function show(array $authUser, int $id): void {
        $stmt = $this->db->prepare($this->baseQuery("t.transaction_id = ?"));
        $stmt->execute([$id]);
        $tx = $stmt->fetch();
        if (!$tx) Response::notFound('Transaction not found');
        $this->assertAccess($authUser, $tx);
        Response::success($tx);
    }

    /** POST /api/transactions — Admin/Farmer records a payment */
    public function store(array $authUser): void {
        if ($authUser['role'] === 'Buyer') Response::forbidden('Buyers cannot record transactions');
        $body = $this->getBody();

        if (empty($body['order_id'])) Response::error('order_id is required');

        // Verify order exists
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE order_id = ?");
        $stmt->execute([$body['order_id']]);
        $order = $stmt->fetch();
        if (!$order) Response::notFound('Order not found');

        // Farmer can only record transactions for their own orders
        if ($authUser['role'] === 'Farmer') {
            $stmt = $this->db->prepare(
                "SELECT f.user_id FROM livestock l
                 JOIN farmers f ON f.farmer_id = l.farmer_id
                 WHERE l.livestock_id = ?"
            );
            $stmt->execute([$order['livestock_id']]);
            $farmer = $stmt->fetch();
            if (!$farmer || (int)$farmer['user_id'] !== $authUser['sub']) Response::forbidden();
        }

        $validMethods   = ['Cash', 'Bank Transfer', 'GCash', 'Maya', 'Credit Card', 'Other'];
        $paymentMethod  = $body['payment_method'] ?? 'Cash';
        if (!in_array($paymentMethod, $validMethods)) {
            Response::error('Invalid payment_method. Options: ' . implode(', ', $validMethods));
        }

        $validStatuses  = ['Pending', 'Paid', 'Failed', 'Refunded'];
        $paymentStatus  = $body['payment_status'] ?? 'Paid';
        if (!in_array($paymentStatus, $validStatuses)) {
            Response::error('Invalid payment_status');
        }

        $stmt = $this->db->prepare(
            "INSERT INTO transaction (order_id, payment_method, payment_status, total_amount)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([
            $order['order_id'],
            $paymentMethod,
            $paymentStatus,
            $body['total_amount'] ?? $order['total_price'],
        ]);

        // If paid, mark order as Completed and livestock as Sold
        if ($paymentStatus === 'Paid') {
            $this->db->prepare(
                "UPDATE orders SET status = 'Completed' WHERE order_id = ?"
            )->execute([$order['order_id']]);
            $this->db->prepare(
                "UPDATE livestock SET sale_status = 'Sold' WHERE livestock_id = ?"
            )->execute([$order['livestock_id']]);
        }

        Response::created(['transaction_id' => (int)$this->db->lastInsertId()], 'Transaction recorded');
    }

    /** PUT /api/transactions/{id} — Admin only */
    public function update(array $authUser, int $id): void {
        if ($authUser['role'] !== 'Admin') Response::forbidden();
        $body = $this->getBody();

        $fields = [];
        $params = [];
        $allowed = ['payment_method', 'payment_status', 'total_amount'];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $body)) {
                $fields[] = "$f = ?";
                $params[] = $body[$f];
            }
        }
        if (empty($fields)) Response::error('No fields to update');

        $params[] = $id;
        $this->db->prepare("UPDATE transaction SET " . implode(', ', $fields) . " WHERE transaction_id = ?")->execute($params);
        Response::success(null, 'Transaction updated');
    }

    /** DELETE /api/transactions/{id} — Admin only */
    public function delete(array $authUser, int $id): void {
        if ($authUser['role'] !== 'Admin') Response::forbidden();
        $stmt = $this->db->prepare("DELETE FROM transaction WHERE transaction_id = ?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) Response::notFound('Transaction not found');
        Response::success(null, 'Transaction deleted');
    }

    // ──── Helpers ────────────────────────────────────────────────

    private function baseQuery(string $extra = ''): string {
        $where = $extra ? "WHERE $extra" : '';
        return "SELECT t.*,
                       o.status AS order_status, o.order_type, o.quantity,
                       o.buyer_id, o.livestock_id,
                       l.tag_number, c.category_name, b.breed_name,
                       buyer.user_first_name AS buyer_first_name, buyer.user_last_name AS buyer_last_name,
                       f.farm_name
                FROM transaction t
                LEFT JOIN orders o     ON o.order_id      = t.order_id
                LEFT JOIN livestock l  ON l.livestock_id  = o.livestock_id
                LEFT JOIN category c   ON c.category_id   = l.category_id
                LEFT JOIN breeds b     ON b.breed_id      = l.breed_id
                LEFT JOIN farmers f    ON f.farmer_id     = l.farmer_id
                LEFT JOIN user buyer   ON buyer.user_id   = o.buyer_id
                $where
                ORDER BY t.transaction_date DESC";
    }

    private function assertAccess(array $authUser, array $tx): void {
        if ($authUser['role'] === 'Admin') return;
        // Check if buyer
        if ($authUser['role'] === 'Buyer' && (int)$tx['buyer_id'] === $authUser['sub']) return;
        // Check if farmer owns the livestock
        if ($authUser['role'] === 'Farmer') {
            $stmt = $this->db->prepare(
                "SELECT f.user_id FROM livestock l
                 JOIN farmers f ON f.farmer_id = l.farmer_id
                 WHERE l.livestock_id = ?"
            );
            $stmt->execute([$tx['livestock_id']]);
            $farmer = $stmt->fetch();
            if ($farmer && (int)$farmer['user_id'] === $authUser['sub']) return;
        }
        Response::forbidden();
    }

    private function getBody(): array {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }
}
