<?php
session_start();
// Adjusted path to reach backend from frontend/pages/farmer/
require_once '../../../backend/db_config.php'; 

/**
 * 1. Security & Role Check
 */
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'farmer') {
    header("Location: ../auth/login.php"); 
    exit();
}

/**
 * 2. Identity Management
 */
$display_name = $_SESSION['user_name'] ?? 'Farmer'; 
$words        = explode(" ", $display_name);
$initials     = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ""));

/**
 * 3. Fetch Real Data from livestuchub_db
 */
try {
    // A. Fetch Total Animal Count
    $countStmt = $conn->query("SELECT COUNT(*) FROM livestock");
    $total_animals = $countStmt->fetchColumn();

    // B. Fetch Pending Orders Count (Assumes an 'orders' table exists)
    // If you don't have this table yet, it defaults to 0
    try {
        $orderStmt = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
        $pending_orders = $orderStmt->fetchColumn();
    } catch (Exception $e) { $pending_orders = 0; }

    // C. Fetch Recent Livestock for the Table
    $tableStmt = $conn->query("SELECT animal_id, species, breed, weight, health_status 
                               FROM livestock 
                               ORDER BY created_at DESC 
                               LIMIT 5");
    $recent_livestock = $tableStmt->fetchAll();

} catch (PDOException $e) {
    // Silently log error and provide empty fallbacks to keep UI from breaking
    error_log("Dashboard Data Error: " . $e->getMessage());
    $total_animals = 0;
    $recent_livestock = [];
}

$page_title   = 'Dashboard';
$current_page = 'dashboard';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AgriHub — <?= $page_title ?></title>
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
                <div class="ag-eyebrow">Good morning, <?= htmlspecialchars($display_name) ?></div>
                <h1 class="ag-page-title">Your farm, <em>at a glance.</em></h1>
            </div>

            <div class="ag-stats ag-mb-md">
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Total Animals</div>
                    <div class="ag-stat-val"><?= number_format($total_animals) ?></div>
                    <div class="ag-stat-delta">Live in system</div>
                </div>
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Pending Orders</div>
                    <div class="ag-stat-val"><?= $pending_orders ?></div>
                    <div class="ag-stat-delta warn">Requires action</div>
                </div>
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Revenue (Est.)</div>
                    <div class="ag-stat-val">₱0</div>
                    <div class="ag-stat-delta">Data syncing...</div>
                </div>
            </div>

            <div class="ag-card">
                <div class="ag-card-header">
                    <span class="ag-card-title">Recent livestock</span>
                    <span class="ag-pill">Live Update</span>
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
                        <?php if (empty($recent_livestock)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding: 20px;">No livestock recorded yet.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($recent_livestock as $animal): ?>
                        <tr>
                            <td class="muted"><?= htmlspecialchars($animal['animal_id']) ?></td>
                            <td class="strong"><?= htmlspecialchars($animal['species']) ?></td>
                            <td class="muted"><?= htmlspecialchars($animal['breed']) ?></td>
                            <td><?= htmlspecialchars($animal['weight']) ?> kg</td>
                            <td>
                                <?php 
                                        $statusClass = (strtolower($animal['health_status']) == 'healthy') ? 'ok' : 'warn';
                                    ?>
                                <span class="ag-tag <?= $statusClass ?>">
                                    <?= htmlspecialchars($animal['health_status']) ?>
                                </span>
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
                <div class="ag-side-title">Farmer profile</div>
                <div class="ag-profile-row">
                    <div class="ag-profile-av"><?= htmlspecialchars($initials) ?></div>
                    <div>
                        <div class="ag-profile-name"><?= htmlspecialchars($display_name) ?></div>
                        <div class="ag-profile-role">Verified Farmer</div>
                    </div>
                </div>
                <div class="ag-divider"></div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">Farm ID</span><span
                        class="ag-meta-val">FM-<?= str_pad($_SESSION['user_id'] ?? '0', 4, '0', STR_PAD_LEFT) ?></span>
                </div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">Location</span><span
                        class="ag-meta-val">Philippines</span></div>
            </div>

            <div class="ag-side-card">
                <div class="ag-side-title">Quick Actions</div>
                <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 10px;">
                    <a href="addAnimals.php" style="text-decoration: none; color: #2d5a27; font-weight: 500;">+ Register
                        New Animal</a>
                    <a href="mylivestock.php" style="text-decoration: none; color: #666;">View Inventory</a>
                </div>
            </div>
        </aside>
    </div>

</body>

</html>