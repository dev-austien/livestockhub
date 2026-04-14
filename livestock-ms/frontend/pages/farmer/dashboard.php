<?php
session_start();
require_once '../../../backend/db_config.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'farmer') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;

/* -----------------------------
   USER INFO
------------------------------*/
$stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$display_name = $user['full_name'] ?? 'Farmer';

$words = explode(' ', $display_name);
$initials = strtoupper(
    substr($words[0] ?? 'F', 0, 1) .
    substr($words[1] ?? '', 0, 1)
);

/* -----------------------------
   FARM INFO
------------------------------*/
$stmt = $pdo->prepare("SELECT farm_id FROM farms WHERE user_id = :id LIMIT 1");
$stmt->execute([':id' => $user_id]);
$farm = $stmt->fetch(PDO::FETCH_ASSOC);

$farm_id = $farm['farm_id'] ?? null;

/* -----------------------------
   TOTAL ANIMALS
------------------------------*/
$total_animals = 0;
if ($farm_id) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM livestock l
        JOIN pens p ON l.pen_id = p.pen_id
        WHERE p.farm_id = :farm_id
    ");
    $stmt->execute([':farm_id' => $farm_id]);
    $total_animals = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
}

/* -----------------------------
   PENDING ORDERS
------------------------------*/
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM orders
    WHERE seller_id = :id AND status = 'Pending'
");
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
    $stmt = $pdo->prepare("
        SELECT
            l.livestock_id,
            l.tag_number,
            l.name,
            l.species,
            l.breed,
            l.weight,
            l.health_status,
            l.date_registered
        FROM livestock l
        JOIN pens p ON l.pen_id = p.pen_id
        WHERE p.farm_id = :farm_id
        ORDER BY l.date_registered DESC
        LIMIT 5
    ");
    $stmt->execute([':farm_id' => $farm_id]);
    $recent_livestock = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$page_title = 'Dashboard';
$current_page = 'dashboard';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AgriHub — Dashboard</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500&family=Playfair+Display:ital,wght@0,600;1,500&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="<?= BASE_URL ?>css/agrihub.css">
</head>

<body>

    <?php include '../includes/nav.php'; ?>

    <div class="ag-page">
        <main class="ag-main">

            <div class="ag-page-header">
                <div class="ag-eyebrow">
                    Good morning, <?= htmlspecialchars($display_name) ?>
                </div>
                <h1 class="ag-page-title">Your farm, <em>at a glance.</em></h1>
            </div>

            <!-- STATS -->
            <div class="ag-stats ag-mb-md">

                <div class="ag-stat">
                    <div class="ag-stat-lbl">Total Animals</div>
                    <div class="ag-stat-val"><?= $total_animals ?></div>
                    <div class="ag-stat-delta">Live count</div>
                </div>

                <div class="ag-stat">
                    <div class="ag-stat-lbl">Pending Orders</div>
                    <div class="ag-stat-val"><?= $pending_orders ?></div>
                    <div class="ag-stat-delta warn">Needs attention</div>
                </div>

                <div class="ag-stat">
                    <div class="ag-stat-lbl">Revenue (Mo.)</div>
                    <div class="ag-stat-val">₱<?= number_format($revenue) ?></div>
                    <div class="ag-stat-delta">Completed orders</div>
                </div>

            </div>

            <!-- TABLE -->
            <div class="ag-card">
                <div class="ag-card-header">
                    <span class="ag-card-title">Recent livestock</span>
                    <span class="ag-pill">Live</span>
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
                            <td class="muted"><?= htmlspecialchars($row['tag_number']) ?></td>
                            <td class="strong"><?= htmlspecialchars($row['species']) ?></td>
                            <td class="muted"><?= htmlspecialchars($row['breed']) ?></td>
                            <td><?= htmlspecialchars($row['weight']) ?> kg</td>
                            <td>
                                <span class="ag-tag">
                                    <?= htmlspecialchars($row['health_status']) ?>
                                </span>
                            </td>
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

        <!-- SIDEBAR -->
        <aside class="ag-sidebar">

            <div class="ag-side-card">
                <div class="ag-side-title">Farmer profile</div>

                <div class="ag-profile-row">
                    <div class="ag-profile-av">
                        <?= htmlspecialchars($initials) ?>
                    </div>

                    <div>
                        <div class="ag-profile-name">
                            <?= htmlspecialchars($display_name) ?>
                        </div>
                        <div class="ag-profile-role">Verified Farmer</div>
                    </div>
                </div>

                <div class="ag-divider"></div>

                <div class="ag-meta-row">
                    <span class="ag-meta-lbl">Farm ID</span>
                    <span class="ag-meta-val"><?= $farm_id ?? 'N/A' ?></span>
                </div>

                <div class="ag-meta-row">
                    <span class="ag-meta-lbl">Status</span>
                    <span class="ag-meta-val">Active</span>
                </div>

            </div>

        </aside>

    </div>

</body>

</html>