<?php
session_start();
require_once '../../../backend/db_config.php';

// 1. Security & Role Check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'farmer') {
    header("Location: ../auth/login.php"); 
    exit();
}

$farmer_id = $_SESSION['user_id'] ?? 1;

// 2. Fetch Farm Information
$farmStmt = $conn->prepare("SELECT * FROM farms WHERE farmer_id = ? LIMIT 1");
$farmStmt->execute([$farmer_id]);
$farm = $farmStmt->fetch(PDO::FETCH_ASSOC);

// 3. Fetch Pen Breakdown
$penStmt = $conn->prepare("SELECT * FROM pens WHERE farm_id = ?");
$penStmt->execute([$farm['id'] ?? 0]);
$pens = $penStmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Calculate Stats for Sidebar
$totalCapacity = 0;
$totalOccupied = 0;
foreach ($pens as $p) {
    $totalCapacity += $p['capacity'];
    $totalOccupied += $p['occupied'];
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
                <h1 class="ag-page-title">My <em>farm.</em></h1>
            </div>

            <div class="ag-card ag-mb-md">
                <div class="ag-card-header">
                    <span class="ag-card-title">Farm information</span>
                    <button class="ag-btn ag-btn-secondary" style="font-size:12px;padding:6px 14px;">Edit
                        details</button>
                </div>
                <div class="ag-card-body">
                    <div class="ag-form-grid ag-mb-md">
                        <div class="ag-form-group">
                            <label class="ag-label">Farm name</label>
                            <input class="ag-input" type="text"
                                value="<?= htmlspecialchars($farm['farm_name'] ?? 'Not Set') ?>" readonly />
                        </div>
                        <div class="ag-form-group">
                            <label class="ag-label">Farm ID</label>
                            <input class="ag-input" type="text"
                                value="<?= htmlspecialchars($farm['farm_code'] ?? 'N/A') ?>" readonly />
                        </div>
                    </div>
                    <div class="ag-form-grid ag-mb-md">
                        <div class="ag-form-group">
                            <label class="ag-label">Location</label>
                            <input class="ag-input" type="text"
                                value="<?= htmlspecialchars($farm['location'] ?? 'Philippines') ?>" readonly />
                        </div>
                        <div class="ag-form-group">
                            <label class="ag-label">Farm size</label>
                            <input class="ag-input" type="text"
                                value="<?= htmlspecialchars($farm['size'] ?? '0') ?> hectares" readonly />
                        </div>
                    </div>
                </div>
            </div>

            <div class="ag-card">
                <div class="ag-card-header">
                    <span class="ag-card-title">Pen / area breakdown</span>
                </div>
                <table class="ag-table">
                    <thead>
                        <tr>
                            <th>Pen name</th>
                            <th>Type</th>
                            <th>Capacity</th>
                            <th>Occupied</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pens)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:20px;">No pens registered.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($pens as $pen): 
                                $isNearFull = ($pen['occupied'] / $pen['capacity']) > 0.85;
                            ?>
                        <tr>
                            <td class="strong"><?= htmlspecialchars($pen['pen_name']) ?></td>
                            <td class="muted"><?= htmlspecialchars($pen['livestock_type']) ?></td>
                            <td><?= $pen['capacity'] ?></td>
                            <td><?= $pen['occupied'] ?></td>
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
        </main>

        <aside class="ag-sidebar">
            <div class="ag-side-card">
                <div class="ag-side-title">Farm stats</div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">Total capacity</span><span
                        class="ag-meta-val"><?= $totalCapacity ?></span></div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">Occupied</span><span
                        class="ag-meta-val <?= $utilization > 85 ? 'warn' : '' ?>"><?= $totalOccupied ?></span></div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">Available</span><span
                        class="ag-meta-val ok"><?= $totalCapacity - $totalOccupied ?></span></div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">Utilization</span><span
                        class="ag-meta-val"><?= $utilization ?>%</span></div>
            </div>

            <div class="ag-side-card">
                <div class="ag-side-title">Pen occupancy</div>
                <div class="ag-bar-group">
                    <?php foreach ($pens as $pen): 
                        $height = ($pen['occupied'] / $pen['capacity']) * 60; // Max height 60px
                    ?>
                    <div class="ag-bar-col">
                        <div class="ag-bar <?= $height > 50 ? 'hi' : '' ?>" style="height:<?= $height ?>px;"></div>
                        <div class="ag-bar-lbl"><?= substr($pen['pen_name'], -1) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>
    </div>

</body>

</html>