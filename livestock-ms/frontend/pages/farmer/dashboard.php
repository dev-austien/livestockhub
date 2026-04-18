<?php
session_start();
// Point to the consolidated config in the shared folder
require_once '../../../backend/shared/db_config.php';

/* -----------------------------
   AUTH CHECK
------------------------------*/
// Use the 'Farmer' role with capitalized casing to match your auth logic
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Farmer') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id   = $_SESSION['user_id'];
$farmer_id = $_SESSION['farmer_id']; // Use the specific farmer_id stored in session

/* -----------------------------
   USER DATA
------------------------------*/
// Updated column names: user_first_name, user_last_name
$stmt = $pdo->prepare("SELECT user_first_name, user_last_name FROM user WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$display_name = htmlspecialchars(($user['user_first_name'] ?? 'Farmer') . ' ' . ($user['user_last_name'] ?? ''));
$initials = strtoupper(substr($user['user_first_name'] ?? 'F', 0, 1) . substr($user['user_last_name'] ?? 'A', 0, 1));

/* -----------------------------
   TOTAL ANIMALS (STAT)
------------------------------*/
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM livestock WHERE farmer_id = ?");
$stmt->execute([$farmer_id]);
$total_animals = $stmt->fetch()['total'] ?? 0;

/* -----------------------------
   PENDING ORDERS (STAT)
------------------------------*/
// Assuming orders link to livestock which links to farmer_id
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM `order` o
    JOIN livestock l ON o.livestock_id = l.livestock_id
    WHERE l.farmer_id = ? AND o.status = 'Pending'
");
$stmt->execute([$farmer_id]);
$pending_orders = $stmt->fetch()['total'] ?? 0;

/* -----------------------------
   MONTHLY REVENUE (STAT)
------------------------------*/
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(o.total_price), 0) as revenue
    FROM `order` o
    JOIN livestock l ON o.livestock_id = l.livestock_id
    WHERE l.farmer_id = ? 
    AND o.status = 'Completed'
    AND MONTH(o.created_at) = MONTH(CURRENT_DATE())
");
$stmt->execute([$farmer_id]);
$revenue = $stmt->fetch()['revenue'] ?? 0;

/* -----------------------------
   RECENT LIVESTOCK (READ)
------------------------------*/
// Joins with category and breeds to show names instead of IDs
$stmt = $pdo->prepare("
    SELECT 
        l.livestock_id, 
        c.category_name, 
        b.breed_name, 
        l.health_status,
        (SELECT weight FROM livestock_weight WHERE livestock_id = l.livestock_id ORDER BY date_recorded DESC LIMIT 1) as current_weight
    FROM livestock l
    JOIN category c ON l.category_id = c.category_id
    JOIN breeds b ON l.breed_id = b.breed_id
    WHERE l.farmer_id = ?
    ORDER BY l.livestock_id DESC
    LIMIT 5
");
$stmt->execute([$farmer_id]);
$recent_livestock = $stmt->fetchAll();
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AgriHub — Farmer Dashboard</title>
    <link rel="stylesheet" href="../../css/agrihub.css">
</head>

<body>

    <?php
    $page_title = "Farmer Dashboard"; 
    include '../../css/include.css/nav.php'; 
    ?>

    <div class="ag-page">
        <main class="ag-main">
            <div class="ag-page-header">
                <div class="ag-eyebrow">Good morning, <?= $display_name ?></div>
                <h1 class="ag-page-title">Your farm, <em>at a glance.</em></h1>
            </div>

            <div class="ag-stats ag-mb-md">
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Total Animals</div>
                    <div class="ag-stat-val"><?= $total_animals ?></div>
                </div>
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Pending Orders</div>
                    <div class="ag-stat-val"><?= $pending_orders ?></div>
                </div>
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Revenue (Mo.)</div>
                    <div class="ag-stat-val">₱<?= number_format($revenue, 2) ?></div>
                </div>
            </div>

            <div class="ag-card">
                <div class="ag-card-header">
                    <span class="ag-card-title">Recent Inventory</span>
                    <a href="mylivestock.php" class="ag-btn ag-btn-ghost" style="font-size: 12px;">View All</a>
                </div>
                <div class="ag-card-body">
                    <table class="ag-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Type</th>
                                <th>Breed</th>
                                <th>Latest Weight</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_livestock)): ?>
                            <?php foreach ($recent_livestock as $row): ?>
                            <tr>
                                <td>#<?= $row['livestock_id'] ?></td>
                                <td><?= htmlspecialchars($row['category_name']) ?></td>
                                <td><?= htmlspecialchars($row['breed_name']) ?></td>
                                <td><?= $row['current_weight'] ?? '0.00' ?> kg</td>
                                <td>
                                    <span class="ag-tag <?= ($row['health_status'] == 'Healthy') ? 'ok' : 'danger' ?>">
                                        <?= htmlspecialchars($row['health_status']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align:center; padding: 40px;">No livestock registered yet.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <aside class="ag-sidebar">
            <div class="ag-side-card">
                <div class="ag-side-title">Farmer Profile</div>
                <div class="ag-profile-row">
                    <div class="ag-profile-av"><?= $initials ?></div>
                    <div>
                        <div class="ag-profile-name"><?= $display_name ?></div>
                        <div class="ag-profile-role">BiPSU-CSS Verified</div>
                    </div>
                </div>
            </div>

            <div class="ag-side-card">
                <div class="ag-side-title">Quick Actions</div>
                <a href="addAnimals.php" class="ag-btn ag-btn-primary"
                    style="width: 100%; display: block; text-align: center; margin-bottom: 10px;">Add New Animal</a>
            </div>
        </aside>
    </div>

</body>

</html>