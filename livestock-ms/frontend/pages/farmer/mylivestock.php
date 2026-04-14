<?php
session_start();
require_once '../../../backend/db_config.php';

/**
 * 1. Security & Role Check
 */
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'farmer') {
    header("Location: ../auth/login.php"); 
    exit();
}

/**
 * 2. Fetch Real Data from livestuchub_db
 */
try {
    // A. Fetch Counts by Category (for the 4 stat cards)
    $categories = ['Cattle', 'Goat', 'Pig', 'Chicken'];
    $counts = [];
    foreach ($categories as $cat) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM livestock WHERE species = ?");
        $stmt->execute([$cat]);
        $counts[$cat] = $stmt->fetchColumn();
    }

    // B. Fetch Health Summary (for sidebar)
    $healthStmt = $conn->query("SELECT 
        SUM(CASE WHEN health_status = 'Healthy' THEN 1 ELSE 0 END) as healthy,
        SUM(CASE WHEN health_status = 'Monitor' THEN 1 ELSE 0 END) as monitor,
        SUM(CASE WHEN health_status = 'Critical' THEN 1 ELSE 0 END) as critical
        FROM livestock");
    $health = $healthStmt->fetch();

    // C. Fetch All Livestock for the Table
    $listStmt = $conn->query("SELECT animal_id, species, breed, age, weight, health_status 
                               FROM livestock 
                               ORDER BY created_at DESC");
    $all_livestock = $listStmt->fetchAll();

} catch (PDOException $e) {
    error_log("Livestock Page Error: " . $e->getMessage());
    $all_livestock = [];
}

$page_title   = 'My Livestock';
$current_page = 'mylivestock';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AgriHub — My Livestock</title>
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
                <h1 class="ag-page-title">My <em>livestock.</em></h1>
            </div>

            <div class="ag-stats ag-stats-4 ag-mb-md">
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Cattle</div>
                    <div class="ag-stat-val"><?= $counts['Cattle'] ?></div>
                    <div class="ag-stat-delta">Registered</div>
                </div>
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Goats</div>
                    <div class="ag-stat-val"><?= $counts['Goat'] ?></div>
                    <div class="ag-stat-delta">Registered</div>
                </div>
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Pigs</div>
                    <div class="ag-stat-val"><?= $counts['Pig'] ?></div>
                    <div class="ag-stat-delta">Registered</div>
                </div>
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Chickens</div>
                    <div class="ag-stat-val"><?= $counts['Chicken'] ?></div>
                    <div class="ag-stat-delta">Registered</div>
                </div>
            </div>

            <div class="ag-flex-between ag-mb-sm">
                <span class="ag-card-title" style="font-size:13px;color:var(--text-secondary);font-weight:500;">All
                    animals</span>
                <a href="addAnimals.php" class="ag-btn ag-btn-primary" style="font-size:12px;padding:7px 14px;">+ Add
                    animal</a>
            </div>

            <div class="ag-card">
                <table class="ag-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Breed</th>
                            <th>Age</th>
                            <th>Weight</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_livestock)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding: 20px;">No animals found.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($all_livestock as $animal): ?>
                        <tr>
                            <td class="muted"><?= htmlspecialchars($animal['animal_id']) ?></td>
                            <td class="strong"><?= htmlspecialchars($animal['species']) ?></td>
                            <td class="muted"><?= htmlspecialchars($animal['breed']) ?></td>
                            <td><?= htmlspecialchars($animal['age']) ?></td>
                            <td><?= htmlspecialchars($animal['weight']) ?> kg</td>
                            <td>
                                <?php 
                                        $status = strtolower($animal['health_status']);
                                        $tagClass = ($status == 'healthy') ? 'ok' : (($status == 'monitor') ? 'warn' : 'danger');
                                    ?>
                                <span
                                    class="ag-tag <?= $tagClass ?>"><?= htmlspecialchars($animal['health_status']) ?></span>
                            </td>
                            <td><a href="viewAnimal.php?id=<?= $animal['animal_id'] ?>" class="ag-btn ag-btn-ghost"
                                    style="font-size:11px;padding:4px 10px;">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>

        <aside class="ag-sidebar">
            <div class="ag-side-card">
                <div class="ag-side-title">Quick actions</div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <a href="addAnimals.php" class="ag-btn ag-btn-primary ag-w-full" style="justify-content:center;">+
                        Register animal</a>
                    <a href="weightLog.php" class="ag-btn ag-btn-secondary ag-w-full"
                        style="justify-content:center;">Log weight</a>
                </div>
            </div>

            <div class="ag-side-card">
                <div class="ag-side-title">Health summary</div>
                <div class="ag-meta-row">
                    <span class="ag-meta-lbl">Healthy</span>
                    <span class="ag-meta-val ok"><?= $health['healthy'] ?? 0 ?></span>
                </div>
                <div class="ag-meta-row">
                    <span class="ag-meta-lbl">Monitoring</span>
                    <span class="ag-meta-val warn"><?= $health['monitor'] ?? 0 ?></span>
                </div>
                <div class="ag-meta-row">
                    <span class="ag-meta-lbl">Critical</span>
                    <span class="ag-meta-val danger"><?= $health['critical'] ?? 0 ?></span>
                </div>
            </div>

            <div class="ag-side-card">
                <div class="ag-side-title">Inventory Split</div>
                <div class="ag-bar-group">
                    <?php 
                        $max = !empty($counts) ? max($counts) : 1; 
                        foreach ($counts as $label => $val): 
                            $height = ($val / $max) * 50; // Dynamic bar height
                    ?>
                    <div class="ag-bar-col">
                        <div class="ag-bar <?= $val == $max ? 'hi' : '' ?>" style="height:<?= $height ?>px;"></div>
                        <div class="ag-bar-lbl"><?= substr($label, 0, 4) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>
    </div>

</body>

</html>