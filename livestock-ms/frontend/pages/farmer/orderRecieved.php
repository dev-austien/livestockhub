<?php
session_start();
require_once '../../../backend/shared/db_config.php';

// 1. Security & Role Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Farmer') {
    header("Location: ../auth/login.php"); 
    exit();
}

$farmer_id = $_SESSION['farmer_id'];

// 2. Handle Order Actions (Confirming an order)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['order_id'])) {
    $order_id = $_POST['order_id'];
    $new_status = ($_POST['action'] === 'confirm') ? 'Confirmed' : 'Cancelled';
    
    // Using backticks for `order` table
    $updateStmt = $pdo->prepare("
        UPDATE `order` o
        JOIN livestock l ON o.livestock_id = l.livestock_id
        SET o.status = ? 
        WHERE o.order_id = ? AND l.farmer_id = ?
    ");
    $updateStmt->execute([$new_status, $order_id, $farmer_id]);
    
    header("Location: ordersReceived.php?msg=Order " . $new_status);
    exit();
}

// 3. Fetch Statistics (Consolidated for performance)
$stats = ['pending' => 0, 'confirmed' => 0, 'completed' => 0, 'revenue' => 0];

$statStmt = $pdo->prepare("
    SELECT 
        status, 
        COUNT(*) as count, 
        SUM(CASE WHEN status = 'Completed' AND MONTH(order_date) = MONTH(CURRENT_DATE()) THEN total_price ELSE 0 END) as revenue
    FROM `order` o
    JOIN livestock l ON o.livestock_id = l.livestock_id
    WHERE l.farmer_id = ?
    GROUP BY status
");
$statStmt->execute([$farmer_id]);
while ($row = $statStmt->fetch()) {
    $s = strtolower($row['status']);
    if (isset($stats[$s])) $stats[$s] = $row['count'];
    if ($row['revenue'] > 0) $stats['revenue'] = $row['revenue'];
}

// 4. Fetch All Orders
// Joining with user (buyer) and livestock/category for details
$orderSql = "
    SELECT 
        o.order_id, 
        o.order_date, 
        o.total_price, 
        o.status, 
        u.user_first_name, 
        u.user_last_name,
        c.category_name
    FROM `order` o
    JOIN user u ON o.user_id = u.user_id
    JOIN livestock l ON o.livestock_id = l.livestock_id
    JOIN category c ON l.category_id = c.category_id
    WHERE l.farmer_id = ?
    ORDER BY o.order_date DESC
";
$orderStmt = $pdo->prepare($orderSql);
$orderStmt->execute([$farmer_id]);
$orders = $orderStmt->fetchAll();

$page_title   = 'Orders Received';
$current_page = 'orderRecieved';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AgriHub — Orders Received</title>
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
                    <div class="ag-stat-delta <?= $stats['pending'] > 0 ? 'warn' : '' ?>">
                        <?= $stats['pending'] > 0 ? 'Action required' : 'All clear' ?>
                    </div>
                </div>
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Confirmed</div>
                    <div class="ag-stat-val"><?= $stats['confirmed'] ?></div>
                    <div class="ag-stat-delta ok">In progress</div>
                </div>
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Completed</div>
                    <div class="ag-stat-val"><?= $stats['completed'] ?></div>
                    <div class="ag-stat-delta">History</div>
                </div>
            </div>

            <div class="ag-card">
                <div class="ag-card-header">
                    <span class="ag-card-title">Order Ledger</span>
                </div>
                <table class="ag-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Buyer</th>
                            <th>Livestock</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:40px;">No orders found.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($orders as $order): 
                            $status = strtolower($order['status']);
                            $tagClass = ($status == 'pending') ? 'warn' : (($status == 'confirmed' || $status == 'completed') ? 'ok' : 'danger');
                        ?>
                        <tr>
                            <td class="strong">#ORD-<?= $order['order_id'] ?></td>
                            <td><?= htmlspecialchars($order['user_first_name'] . ' ' . $order['user_last_name']) ?></td>
                            <td class="muted"><?= htmlspecialchars($order['category_name']) ?></td>
                            <td class="strong">₱<?= number_format($order['total_price'], 2) ?></td>
                            <td class="muted"><?= date('M d, Y', strtotime($order['order_date'])) ?></td>
                            <td>
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
                                <a href="viewOrder.php?id=<?= $order['order_id'] ?>" class="ag-btn ag-btn-ghost"
                                    style="font-size:11px;padding:4px 10px;">Details</a>
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
                <div class="ag-side-title">Monthly Performance</div>
                <div style="font-size:24px; font-weight:500; color:var(--accent); margin: 8px 0;">
                    ₱<?= number_format($stats['revenue'], 2) ?>
                </div>
                <div class="ag-text-sm ag-text-muted">Total Revenue (<?= date('M') ?>)</div>
                <div class="ag-divider" style="margin: 15px 0; border-top: 1px solid #eee;"></div>
                <div class="ag-meta-row">
                    <span class="ag-meta-lbl">Conversion Rate</span>
                    <span class="ag-meta-val">
                        <?php 
                            $total = array_sum(array_slice($stats, 0, 3));
                            echo ($total > 0) ? round(($stats['completed'] / $total) * 100) : 0;
                        ?>%
                    </span>
                </div>
            </div>
        </aside>
    </div>
</body>

</html>