<?php
session_start();
require_once '../../../backend/shared/db_config.php';

// 1. Auth Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Farmer') {
    header("Location: ../auth/login.php"); 
    exit();
}

$farmer_id = $_SESSION['farmer_id'];

// 2. Fetch Locations (Pens/Areas) for this Farmer
// We JOIN with livestock to calculate 'occupied' counts dynamically
$locStmt = $pdo->prepare("
    SELECT 
        l.location_id, 
        l.location_name, 
        l.location_type, 
        l.capacity,
        (SELECT COUNT(*) FROM livestock WHERE location_id = l.location_id) as occupied_count
    FROM location l
    WHERE l.farmer_id = ?
");
$locStmt->execute([$farmer_id]);
$locations = $locStmt->fetchAll();

// 3. Calculate Stats for Sidebar
$totalCapacity = 0;
$totalOccupied = 0;
foreach ($locations as $loc) {
    $totalCapacity += $loc['capacity'];
    $totalOccupied += $loc['occupied_count'];
}
$utilization = ($totalCapacity > 0) ? round(($totalOccupied / $totalCapacity) * 100) : 0;

$page_title   = 'My Farm';
$current_page = 'myFarm';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AgriHub — My Farm</title>
    <link rel="stylesheet" href="../../css/agrihub.css" />
</head>

<body>

    <?php include '../../css/include.css/nav.php'; ?>

    <div class="ag-page">
        <main class="ag-main">
            <div class="ag-page-header">
                <div class="ag-eyebrow">Farmer portal</div>
                <h1 class="ag-page-title">Farm <em>infrastructure.</em></h1>
            </div>

            <div class="ag-card">
                <div class="ag-card-header">
                    <span class="ag-card-title">Pen / Area breakdown</span>
                    <button class="ag-btn ag-btn-secondary" style="font-size:12px;padding:6px 14px;">+ Add New
                        Area</button>
                </div>
                <div class="ag-card-body">
                    <table class="ag-table">
                        <thead>
                            <tr>
                                <th>Area Name</th>
                                <th>Type</th>
                                <th>Capacity</th>
                                <th>Occupied</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($locations)): ?>
                            <tr>
                                <td colspan="5" style="text-align:center; padding:40px;">No farm areas registered.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($locations as $loc): 
                                $isNearFull = ($loc['capacity'] > 0 && ($loc['occupied_count'] / $loc['capacity']) > 0.85);
                            ?>
                            <tr>
                                <td class="strong"><?= htmlspecialchars($loc['location_name']) ?></td>
                                <td class="muted"><?= htmlspecialchars($loc['location_type']) ?></td>
                                <td><?= $loc['capacity'] ?></td>
                                <td><?= $loc['occupied_count'] ?></td>
                                <td>
                                    <span class="ag-tag <?= $isNearFull ? 'warn' : 'ok' ?>">
                                        <?= $isNearFull ? 'Near full' : 'Good' ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <aside class="ag-sidebar">
            <div class="ag-side-card">
                <div class="ag-side-title">Live Capacity</div>
                <div class="ag-meta-row">
                    <span class="ag-meta-lbl">Total capacity</span>
                    <span class="ag-meta-val"><?= $totalCapacity ?></span>
                </div>
                <div class="ag-meta-row">
                    <span class="ag-meta-lbl">Occupied</span>
                    <span class="ag-meta-val <?= $utilization > 85 ? 'warn' : '' ?>"><?= $totalOccupied ?></span>
                </div>
                <div class="ag-meta-row">
                    <span class="ag-meta-lbl">Utilization</span>
                    <span class="ag-meta-val"><?= $utilization ?>%</span>
                </div>
            </div>

            <div class="ag-side-card">
                <div class="ag-side-title">Occupancy Map</div>
                <div class="ag-bar-group"
                    style="display: flex; align-items: flex-end; gap: 8px; height: 100px; padding-top: 20px;">
                    <?php foreach ($locations as $loc): 
                        $pct = ($loc['capacity'] > 0) ? ($loc['occupied_count'] / $loc['capacity']) : 0;
                        $height = $pct * 60; // Max 60px
                    ?>
                    <div class="ag-bar-col" style="flex: 1; text-align: center;">
                        <div class="ag-bar <?= $pct > 0.85 ? 'hi' : '' ?>"
                            style="height:<?= max($height, 2) ?>px; background: <?= $pct > 0.85 ? '#ff4d4d' : '#2ecc71' ?>; border-radius: 2px;">
                        </div>
                        <div class="ag-bar-lbl" style="font-size: 10px; margin-top: 4px;">
                            <?= htmlspecialchars(substr($loc['location_name'], 0, 3)) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>
    </div>
</body>

</html>