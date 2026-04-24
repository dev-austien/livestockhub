<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';

class OrderController {
    private PDO $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * GET /api/orders
     * Admin: all | Farmer: orders on their livestock | Buyer: own orders
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
            $stmt->execute([$authUser['sub']]);
        } else {
            $stmt = $this->db->prepare($this->baseQuery("o.buyer_id = ?"));
            $stmt->execute([$authUser['sub']]);
        }
        Response::success($stmt->fetchAll());
    }

    /** GET /api/orders/{id} */
    public function show(array $authUser, int $id): void {
        $stmt = $this->db->prepare($this->baseQuery("o.order_id = ?"));
        $stmt->execute([$id]);
        $order = $stmt->fetch();
        if (!$order) Response::notFound('Order not found');
        $this->assertAccess($authUser, $order);
        Response::success($order);
    }

    /** POST /api/orders — Buyer creates an order */
    public function store(array $authUser): void {
        if ($authUser['role'] === 'Admin') Response::forbidden('Admins cannot place orders');
        $body = $this->getBody();

        if (empty($body['livestock_id'])) Response::error('livestock_id is required');
        if (empty($body['order_type']) || !in_array($body['order_type'], ['Buy', 'Reserve'])) {
            Response::error("order_type must be 'Buy' or 'Reserve'");
        }

        // Fetch livestock details
        $stmt = $this->db->prepare(
            "SELECT * FROM livestock WHERE livestock_id = ? AND sale_status = 'Available'"
        );
        $stmt->execute([$body['livestock_id']]);
        $livestock = $stmt->fetch();
        if (!$livestock) Response::error('Livestock is not available for purchase', 422);

        $qty        = max(1, (int)($body['quantity'] ?? 1));
        $unitPrice  = (float)$livestock['price'];
        $totalPrice = $qty * $unitPrice;

        // Reservation expiry: 3 days from now by default
        $expiry = ($body['order_type'] === 'Reserve')
            ? date('Y-m-d', strtotime('+3 days'))
            : null;

        $stmt = $this->db->prepare(
            "INSERT INTO orders (buyer_id, livestock_id, quantity, unit_price, order_type,
                                 status, total_price, reservation_expiry)
             VALUES (?, ?, ?, ?, ?, 'Pending', ?, ?)"
        );
        $stmt->execute([
            $authUser['sub'],
            $livestock['livestock_id'],
            $qty,
            $unitPrice,
            $body['order_type'],
            $totalPrice,
            $expiry,
        ]);

        $orderId = (int)$this->db->lastInsertId();

        // Mark livestock as reserved if applicable
        if ($body['order_type'] === 'Reserve') {
            $this->db->prepare(
                "UPDATE livestock SET sale_status = 'Reserved' WHERE livestock_id = ?"
            )->execute([$livestock['livestock_id']]);
        }

        Response::created(['order_id' => $orderId, 'total_price' => $totalPrice], 'Order placed');
    }

    /**
     * PATCH /api/orders/{id}/status
     * Farmer confirms/completes/cancels. Admin can do anything.
     */
    public function updateStatus(array $authUser, int $id): void {
        $stmt = $this->db->prepare($this->baseQuery("o.order_id = ?"));
        $stmt->execute([$id]);
        $order = $stmt->fetch();
        if (!$order) Response::notFound('Order not found');

        $body = $this->getBody();
        $status = $body['status'] ?? '';

        $validStatuses = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];
        if (!in_array($status, $validStatuses)) {
            Response::error('Invalid status. Must be: ' . implode(', ', $validStatuses));
        }

        // Role rules
        if ($authUser['role'] === 'Buyer') {
            if ($order['buyer_id'] != $authUser['sub']) Response::forbidden();
            if ($status !== 'Cancelled') Response::forbidden('Buyers can only cancel orders');
        } elseif ($authUser['role'] === 'Farmer') {
            if ((int)$order['farmer_user_id'] !== $authUser['sub']) Response::forbidden();
        }

        $this->db->prepare("UPDATE orders SET status = ? WHERE order_id = ?")->execute([$status, $id]);

        // Update livestock sale_status accordingly
        $newSaleStatus = match($status) {
            'Completed' => 'Sold',
            'Cancelled' => 'Available',
            default     => null,
        };
        if ($newSaleStatus) {
            $this->db->prepare(
                "UPDATE livestock SET sale_status = ? WHERE livestock_id = ?"
            )->execute([$newSaleStatus, $order['livestock_id']]);
        }

        Response::success(null, 'Order status updated');
    }

    /** DELETE /api/orders/{id} — Admin or buyer (own pending order) */
    public function delete(array $authUser, int $id): void {
        $stmt = $this->db->prepare($this->baseQuery("o.order_id = ?"));
        $stmt->execute([$id]);
        $order = $stmt->fetch();
        if (!$order) Response::notFound('Order not found');

        if ($authUser['role'] !== 'Admin') {
            if ((int)$order['buyer_id'] !== $authUser['sub']) Response::forbidden();
            if ($order['status'] !== 'Pending') Response::error('Only pending orders can be deleted');
        }

        $this->db->prepare("DELETE FROM orders WHERE order_id = ?")->execute([$id]);
        Response::success(null, 'Order deleted');
    }

    // ──── Helpers ────────────────────────────────────────────────

    private function baseQuery(string $extra = ''): string {
        $where = $extra ? "WHERE $extra" : '';
        return "SELECT o.*,
                       l.tag_number, l.sale_status AS livestock_sale_status,
                       c.category_name, b.breed_name,
                       buyer.user_first_name AS buyer_first_name, buyer.user_last_name AS buyer_last_name, buyer.user_email AS buyer_email,
                       seller.user_id AS farmer_user_id, seller.user_first_name AS farmer_first_name, seller.user_last_name AS farmer_last_name,
                       f.farm_name
                FROM orders o
                LEFT JOIN livestock l  ON l.livestock_id = o.livestock_id
                LEFT JOIN category c   ON c.category_id  = l.category_id
                LEFT JOIN breeds b     ON b.breed_id      = l.breed_id
                LEFT JOIN farmers f    ON f.farmer_id     = l.farmer_id
                LEFT JOIN user seller  ON seller.user_id  = f.user_id
                LEFT JOIN user buyer   ON buyer.user_id   = o.buyer_id
                $where
                ORDER BY o.created_at DESC";
    }

    private function assertAccess(array $authUser, array $order): void {
        if ($authUser['role'] === 'Admin') return;
        if ($authUser['role'] === 'Buyer' && (int)$order['buyer_id'] === $authUser['sub']) return;
        if ($authUser['role'] === 'Farmer' && (int)$order['farmer_user_id'] === $authUser['sub']) return;
        Response::forbidden();
    }

    private function getBody(): array {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }
}
