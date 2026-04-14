<?php
session_start();
require_once '../../../backend/db_config.php';

// 1. Security & Role Check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'farmer') {
    header("Location: ../auth/login.php"); 
    exit();
}

$farmer_id = $_SESSION['user_id'] ?? 1; // Assuming session stores user_id

// 2. Handle Order Actions (Confirming an order)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['order_id'])) {
    $order_id = $_POST['order_id'];
    $new_status = ($_POST['action'] === 'confirm') ? 'Confirmed' : 'Cancelled';
    
    $updateStmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ? AND seller_id = ?");
    $updateStmt->execute([$new_status, $order_id, $farmer_id]);
    // Optional: Add success message here
}

// 3. Fetch Statistics
// Pending count
$pendingCount = $conn->prepare("SELECT COUNT(*) FROM orders WHERE seller_id = ? AND status = 'Pending'");
$pendingCount->execute([$farmer_id]);
$stats['pending'] = $pendingCount->fetchColumn();

// Confirmed count
$confirmedCount = $conn->prepare("SELECT COUNT(*) FROM orders WHERE seller_id = ? AND status = 'Confirmed'");
$confirmedCount->execute([$farmer_id]);
$stats['confirmed'] = $confirmedCount->fetchColumn();

// Completed count (this month)
$completedCount = $conn->prepare("SELECT COUNT(*) FROM orders WHERE seller_id = ? AND status = 'Completed' AND MONTH(created_at) = MONTH(CURRENT_DATE())");
$completedCount->execute([$farmer_id]);
$stats['completed'] = $completedCount->fetchColumn();

// Total Revenue
$revenueStmt = $conn->prepare("SELECT SUM(total_amount) FROM orders WHERE seller_id = ? AND status = 'Completed' AND MONTH(created_at) = MONTH(CURRENT_DATE())");
$revenueStmt->execute([$farmer_id]);
$totalRevenue = $revenueStmt->fetchColumn() ?: 0;

// 4. Fetch All Orders
$orderSql = "SELECT o.*, u.full_name as buyer_name 
             FROM orders o 
             JOIN users u ON o.buyer_id = u.id 
             WHERE o.seller_id = ? 
             ORDER BY o.created_at DESC";
$orderStmt = $conn->prepare($orderSql);
$orderStmt->execute([$farmer_id]);
$orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

$page_title   = 'Orders Received';
$current_page = 'orderRecieved';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AgriHub — Orders Received</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500&family=Playfair+Display:ital,wght@0,600;1,500&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="../../css/agrihub.css" />
</head>

<body>

    <?php include '../../css/include.css/nav.php'; ?>

    <div class="ag-page">
        <main class="ag-main">
            <div class="ag-page-header">
                <div class="ag-eyebrow">Farmer portal</div>
                <h1 class="ag-page-title">Orders <em>received.</em></h1>
            </div>

            <div class="ag-stats ag-mb-md">
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Pending</div>
                    <div class="ag-stat-val"><?= $stats['pending'] ?></div>
                    <div class="ag-stat-delta warn">Needs action</div>
                </div>
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Confirmed</div>
                    <div class="ag-stat-val"><?= $stats['confirmed'] ?></div>
                    <div class="ag-stat-delta ok">Awaiting pickup</div>
                </div>
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Completed</div>
                    <div class="ag-stat-val"><?= $stats['completed'] ?></div>
                    <div class="ag-stat-delta">This month</div>
                </div>
            </div>

            <div class="ag-card">
                <div class="ag-card-header">
                    <span class="ag-card-title">All orders</span>
                    <span class="ag-pill"><?= $stats['pending'] ?> pending</span>
                </div>
                <table class="ag-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Buyer</th>
                            <th>Animal</th>
                            <th>Qty</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding:20px;">No orders found.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td class="strong">ORD-<?= $order['order_id'] ?></td>
                            <td><?= htmlspecialchars($order['buyer_name']) ?></td>
                            <td class="muted"><?= htmlspecialchars($order['livestock_type'] ?? 'N/A') ?></td>
                            <td><?= $order['quantity'] ?></td>
                            <td class="strong">₱<?= number_format($order['total_amount'], 2) ?></td>
                            <td class="muted"><?= date('M d', strtotime($order['created_at'])) ?></td>
                            <td>
                                <?php 
                                        $status = strtolower($order['status']);
                                        $tagClass = ($status == 'pending') ? 'warn' : (($status == 'confirmed' || $status == 'completed') ? 'ok' : 'danger');
                                    ?>
                                <span class="ag-tag <?= $tagClass ?>"><?= $order['status'] ?></span>
                            </td>
                            <td>
                                <?php if ($order['status'] === 'Pending'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                    <input type="hidden" name="action" value="confirm">
                                    <button type="submit" class="ag-btn ag-btn-primary"
                                        style="font-size:11px;padding:4px 10px;">Confirm</button>
                                </form>
                                <?php else: ?>
                                <button class="ag-btn ag-btn-secondary"
                                    style="font-size:11px;padding:4px 10px;">View</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>

        <aside class="ag-sidebar">
            <div class="ag-side-card">
                <div class="ag-side-title">Revenue this month</div>
                <div style="font-size:28px;font-weight:500;color:var(--accent);margin-bottom:4px;">
                    ₱<?= number_format($totalRevenue, 2) ?></div>
                <div class="ag-text-sm ag-text-muted">From <?= $stats['completed'] ?> completed orders</div>
                <div class="ag-divider"></div>
                <div class="ag-meta-row">
                    <span class="ag-meta-lbl">Avg. order value</span>
                    <span
                        class="ag-meta-val">₱<?= ($stats['completed'] > 0) ? number_format($totalRevenue / $stats['completed'], 0) : '0' ?></span>
                </div>
            </div>
        </aside>
    </div>

</body>

</html>