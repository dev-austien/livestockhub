<?php
session_start();
require_once '../../../backend/db_config.php';

// 1. Security & Role Check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'farmer') {
    header("Location: ../auth/login.php"); 
    exit();
}

$farmer_id = $_SESSION['user_id'] ?? 1;

// 2. Fetch Aggregated Stats
// This Month's Revenue
$thisMonthStmt = $conn->prepare("SELECT SUM(total_amount) FROM orders WHERE seller_id = ? AND status = 'Completed' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
$thisMonthStmt->execute([$farmer_id]);
$revThisMonth = $thisMonthStmt->fetchColumn() ?: 0;

// Last Month's Revenue
$lastMonthStmt = $conn->prepare("SELECT SUM(total_amount) FROM orders WHERE seller_id = ? AND status = 'Completed' AND MONTH(created_at) = MONTH(STR_TO_DATE(DATE_FORMAT(NOW() ,'%Y-%m-01'),'%Y-%m-%d') - INTERVAL 1 MONTH)");
$lastMonthStmt->execute([$farmer_id]);
$revLastMonth = $lastMonthStmt->fetchColumn() ?: 0;

// Total Sales & Transaction Count
$totalStmt = $conn->prepare("SELECT SUM(total_amount) as total_rev, COUNT(*) as total_count FROM orders WHERE seller_id = ? AND status = 'Completed'");
$totalStmt->execute([$farmer_id]);
$totals = $totalStmt->fetch(PDO::FETCH_ASSOC);

// 3. Fetch Recent Transactions
$txnStmt = $conn->prepare("
    SELECT o.*, u.full_name as buyer_name 
    FROM orders o 
    JOIN users u ON o.buyer_id = u.id 
    WHERE o.seller_id = ? 
    ORDER BY o.created_at DESC 
    LIMIT 15
");
$txnStmt->execute([$farmer_id]);
$transactions = $txnStmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Calculate Percentage Delta
$delta = 0;
if ($revLastMonth > 0) {
    $delta = (($revThisMonth - $revLastMonth) / $revLastMonth) * 100;
}

$page_title   = 'Transactions';
$current_page = 'transaction';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AgriHub — Transactions</title>
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
                <h1 class="ag-page-title">Transaction <em>history.</em></h1>
            </div>

            <div class="ag-stats ag-stats-4 ag-mb-md">
                <div class="ag-stat">
                    <div class="ag-stat-lbl">This month</div>
                    <div class="ag-stat-val">₱<?= number_format($revThisMonth / 1000, 1) ?>k</div>
                    <div class="ag-stat-delta <?= $delta >= 0 ? 'ok' : 'danger' ?>">
                        <?= $delta >= 0 ? '+' : '' ?><?= round($delta) ?>% vs last mo.
                    </div>
                </div>
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Last month</div>
                    <div class="ag-stat-val">₱<?= number_format($revLastMonth / 1000, 1) ?>k</div>
                    <div class="ag-stat-delta ok">Settled</div>
                </div>
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Total sales</div>
                    <div class="ag-stat-val">₱<?= number_format($totals['total_rev'] / 1000, 1) ?>k</div>
                    <div class="ag-stat-delta">All time</div>
                </div>
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Transactions</div>
                    <div class="ag-stat-val"><?= $totals['total_count'] ?></div>
                    <div class="ag-stat-delta">All time</div>
                </div>
            </div>

            <div class="ag-card">
                <div class="ag-card-header">
                    <span class="ag-card-title">Recent transactions</span>
                    <span class="ag-pill"><?= count($transactions) ?> items shown</span>
                </div>
                <table class="ag-table">
                    <thead>
                        <tr>
                            <th>Ref #</th>
                            <th>Order</th>
                            <th>Buyer</th>
                            <th>Item</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $txn): ?>
                        <tr>
                            <td class="muted">TXN-<?= $txn['order_id'] + 8000 ?></td>
                            <td class="muted">ORD-<?= $txn['order_id'] ?></td>
                            <td><?= htmlspecialchars($txn['buyer_name']) ?></td>
                            <td class="muted"><?= $txn['quantity'] ?>x <?= htmlspecialchars($txn['livestock_type']) ?>
                            </td>
                            <td class="strong">₱<?= number_format($txn['total_amount'], 2) ?></td>
                            <td class="muted"><?= date('M d', strtotime($txn['created_at'])) ?></td>
                            <td>
                                <?php 
                                    $status = strtolower($txn['status']);
                                    $class = ($status == 'completed') ? 'ok' : (($status == 'pending') ? 'warn' : 'info');
                                    $label = ($status == 'completed') ? 'Paid' : $txn['status'];
                                ?>
                                <span class="ag-tag <?= $class ?>"><?= $label ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>

        <aside class="ag-sidebar">
            <div class="ag-side-card">
                <div class="ag-side-title">Revenue split</div>
                <?php
                // Dynamic split query
                $splitStmt = $conn->prepare("SELECT livestock_type, SUM(total_amount) as amt FROM orders WHERE seller_id = ? AND status = 'Completed' GROUP BY livestock_type");
                $splitStmt->execute([$farmer_id]);
                while($row = $splitStmt->fetch(PDO::FETCH_ASSOC)):
                ?>
                <div class="ag-meta-row">
                    <span class="ag-meta-lbl"><?= htmlspecialchars($row['livestock_type']) ?> sales</span>
                    <span class="ag-meta-val">₱<?= number_format($row['amt']) ?></span>
                </div>
                <?php endwhile; ?>
            </div>
        </aside>
    </div>

</body>

</html>