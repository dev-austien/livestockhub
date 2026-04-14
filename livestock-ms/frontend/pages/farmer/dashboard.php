<?php
session_start();
require_once '../../../backend/db_config.php';

/* -----------------------------
   AUTH CHECK
------------------------------*/
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'farmer') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;

/* -----------------------------
   USER DATA
------------------------------*/
// Match your schema: 'user_id' is the primary key in your 'users' table
$stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE user_id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$display_name = ($user['first_name'] ?? 'Farmer') . ' ' . ($user['last_name'] ?? '');

$initials = strtoupper(
    substr($user['first_name'] ?? 'F', 0, 1) .
    substr($user['last_name'] ?? 'A', 0, 1)
);

/* -----------------------------
   FARM DATA
------------------------------*/
$stmt = $pdo->prepare("SELECT farm_id FROM farms WHERE farmer_id = :id LIMIT 1");
$stmt->execute([':id' => $user_id]);
$farm = $stmt->fetch(PDO::FETCH_ASSOC);

$farm_id = $farm['farm_id'] ?? null;

/* -----------------------------
   TOTAL ANIMALS
------------------------------*/
$total_animals = 0;
if ($farm_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM livestock WHERE farm_id = :farm_id");
    $stmt->execute([':farm_id' => $farm_id]);
    $total_animals = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
}

/* -----------------------------
   PENDING ORDERS
------------------------------*/
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE seller_id = :id AND status = 'Pending'");
$stmt->execute([':id' => $user_id]);
$pending_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

/* -----------------------------
   MONTHLY REVENUE
------------------------------*/
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(total_amount),0) as revenue
    FROM orders
    WHERE seller_id = :id
    AND status = 'Completed'
    AND MONTH(created_at) = MONTH(CURRENT_DATE())
");
$stmt->execute([':id' => $user_id]);
$revenue = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;

/* -----------------------------
   RECENT LIVESTOCK
------------------------------*/
$recent_livestock = [];
if ($farm_id) {
    // FIXED: Using 'animal_id' and 'breed_name' to match your schema
    $stmt = $pdo->prepare("
        SELECT
            animal_id,
            species,
            breed_name,
            weight,
            health_status,
            date_registered
        FROM livestock
        WHERE farm_id = :farm_id
        ORDER BY date_registered DESC
        LIMIT 5
    ");
    $stmt->execute([':farm_id' => $farm_id]);
    $recent_livestock = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AgriHub Dashboard</title>
    <link rel="stylesheet" href="/livestockhub/livestock-ms/frontend/css/agrihub.css">
</head>

<body>

    <?php
// FIXED NAV INCLUDE: Using relative path to find the file correctly
include '../../css/includes/nav.php';
?>

    <div class="ag-page">
        <main class="ag-main">
            <div class="ag-page-header">
                <div class="ag-eyebrow">Good morning, <?= htmlspecialchars($display_name) ?></div>
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
                    <div class="ag-stat-val">₱<?= number_format($revenue) ?></div>
                </div>
            </div>

            <div class="ag-card">
                <div class="ag-card-header">
                    <span class="ag-card-title">Recent Livestock</span>
                </div>
                <table class="ag-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Animal</th>
                            <th>Breed</th>
                            <th>Weight</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recent_livestock)): ?>
                        <?php foreach ($recent_livestock as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['animal_id']) ?></td>
                            <td><?= htmlspecialchars($row['species']) ?></td>
                            <td><?= htmlspecialchars($row['breed_name']) ?></td>
                            <td><?= htmlspecialchars($row['weight']) ?> kg</td>
                            <td><?= htmlspecialchars($row['health_status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center;">No livestock found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>

        <aside class="ag-sidebar">
            <div class="ag-side-card">
                <div class="ag-side-title">Farmer profile</div>
                <div class="ag-profile-row">
                    <div class="ag-profile-av"><?= htmlspecialchars($initials) ?></div>
                    <div>
                        <div class="ag-profile-name"><?= htmlspecialchars($display_name) ?></div>
                        <div class="ag-profile-role">Verified Farmer</div>
                    </div>
                </div>
            </div>
        </aside>
    </div>

</body>

</html>