<?php
session_start();
require_once '../../../backend/shared/db_config.php';

// 1. Security & Role Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Farmer') {
    header("Location: ../auth/login.php"); 
    exit();
}

$farmer_id = $_SESSION['farmer_id'];

// 2. Fetch Aggregated Stats
// This Month's Revenue
$thisMonthStmt = $pdo->prepare("
    SELECT SUM(total_price) 
    FROM `order` o
    JOIN livestock l ON o.livestock_id = l.livestock_id
    WHERE l.farmer_id = ? 
    AND o.status = 'Completed' 
    AND MONTH(o.order_date) = MONTH(CURRENT_DATE()) 
    AND YEAR(o.order_date) = YEAR(CURRENT_DATE())
");
$thisMonthStmt->execute([$farmer_id]);
$revThisMonth = $thisMonthStmt->fetchColumn() ?: 0;

// Last Month's Revenue (Improved date logic)
$lastMonthStmt = $pdo->prepare("
    SELECT SUM(total_price) 
    FROM `order` o
    JOIN livestock l ON o.livestock_id = l.livestock_id
    WHERE l.farmer_id = ? 
    AND o.status = 'Completed' 
    AND MONTH(o.order_date) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH)
    AND YEAR(o.order_date) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)
");
$lastMonthStmt->execute([$farmer_id]);
$revLastMonth = $lastMonthStmt->fetchColumn() ?: 0;

// Total Sales & Transaction Count
$totalStmt = $pdo->prepare("
    SELECT SUM(total_price) as total_rev, COUNT(*) as total_count 
    FROM `order` o
    JOIN livestock l ON o.livestock_id = l.livestock_id
    WHERE l.farmer_id = ? AND o.status = 'Completed'
");
$totalStmt->execute([$farmer_id]);
$totals = $totalStmt->fetch();

// 3. Fetch Recent Transactions
$txnStmt = $pdo->prepare("
    SELECT o.*, u.user_first_name, u.user_last_name, c.category_name
    FROM `order` o 
    JOIN user u ON o.user_id = u.user_id 
    JOIN livestock l ON o.livestock_id = l.livestock_id
    JOIN category c ON l.category_id = c.category_id
    WHERE l.farmer_id = ? 
    ORDER BY o.order_date DESC 
    LIMIT 15
");
$txnStmt->execute([$farmer_id]);
$transactions = $txnStmt->fetchAll();

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
                        <?= $delta >= 0 ? '↑' : '↓' ?><?= abs(round($delta)) ?>% vs last mo.
                    </div>
                </div>
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Last month</div>
                    <div class="ag-stat-val">₱<?= number_format($revLastMonth / 1000, 1) ?>k</div>
                    <div class="ag-stat-delta ok">Settled</div>
                </div>
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Total sales</div>
                    <div class="ag-stat-val">₱<?= number_format(($totals['total_rev'] ?? 0) / 1000, 1) ?>k</div>
                    <div class="ag-stat-delta">All time</div>
                </div>
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Transactions</div>
                    <div class="ag-stat-val"><?= $totals['total_count'] ?? 0 ?></div>
                    <div class="ag-stat-delta">Successful</div>
                </div>
            </div>

            <div class="ag-card">
                <div class="ag-card-header">
                    <span class="ag-card-title">Recent transactions</span>
                    <span class="ag-pill"><?= count($transactions) ?> records</span>
                </div>
                <table class="ag-table">
                    <thead>
                        <tr>
                            <th>Ref #</th>
                            <th>Buyer</th>
                            <th>Item</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $txn): 
                            $status = strtolower($txn['status']);
                            $class = ($status == 'completed') ? 'ok' : (($status == 'pending') ? 'warn' : 'info');
                        ?>
                        <tr>
                            <td class="muted">#TXN-<?= str_pad($txn['order_id'], 5, "0", STR_PAD_LEFT) ?></td>
                            <td><?= htmlspecialchars($txn['user_first_name'] . ' ' . $txn['user_last_name']) ?></td>
                            <td class="muted"><?= htmlspecialchars($txn['category_name']) ?></td>
                            <td class="strong">₱<?= number_format($txn['total_price'], 2) ?></td>
                            <td class="muted"><?= date('M d, Y', strtotime($txn['order_date'])) ?></td>
                            <td>
                                <span class="ag-tag <?= $class ?>"><?= ucfirst($status) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding:30px;">No transactions recorded.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>

        <aside class="ag-sidebar">
            <div class="ag-side-card">
                <div class="ag-side-title">Revenue split</div>
                <?php
                // Revenue split by livestock category
                $splitStmt = $pdo->prepare("
                    SELECT c.category_name, SUM(o.total_price) as amt 
                    FROM `order` o 
                    JOIN livestock l ON o.livestock_id = l.livestock_id 
                    JOIN category c ON l.category_id = c.category_id
                    WHERE l.farmer_id = ? AND o.status = 'Completed' 
                    GROUP BY c.category_name
                ");
                $splitStmt->execute([$farmer_id]);
                while($row = $splitStmt->fetch()):
                ?>
                <div class="ag-meta-row">
                    <span class="ag-meta-lbl"><?= htmlspecialchars($row['category_name']) ?></span>
                    <span class="ag-meta-val">₱<?= number_format($row['amt']) ?></span>
                </div>
                <?php endwhile; ?>
            </div>
        </aside>
    </div>

</body>

</html>