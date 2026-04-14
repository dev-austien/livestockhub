<?php
session_start();
require_once '../../../backend/db_config.php';

// 1. Security & Role Check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'farmer') {
    header("Location: ../auth/login.php"); 
    exit();
}

$message = "";
$messageType = "";

// 2. Handle Weight Entry Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tag_id'])) {
    $tag_id   = $_POST['tag_id'];
    $new_weight = $_POST['weight'];
    $log_date = $_POST['log_date'] ?: date('Y-m-d');

    try {
        $conn->beginTransaction();

        // Get the current (previous) weight before updating
        $prevStmt = $conn->prepare("SELECT weight FROM livestock WHERE animal_id = ?");
        $prevStmt->execute([$tag_id]);
        $prev_weight = $prevStmt->fetchColumn() ?: 0;

        // Calculate change
        $weight_change = $new_weight - $prev_weight;

        // Insert into weight_logs table (Ensure this table exists in your DB)
        $logSql = "INSERT INTO weight_logs (animal_id, previous_weight, current_weight, weight_change, log_date) 
                   VALUES (?, ?, ?, ?, ?)";
        $logStmt = $conn->prepare($logSql);
        $logStmt->execute([$tag_id, $prev_weight, $new_weight, $weight_change, $log_date]);

        // Update the main livestock table with the latest weight
        $updateSql = "UPDATE livestock SET weight = ? WHERE animal_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->execute([$new_weight, $tag_id]);

        $conn->commit();
        $message = "Weight logged successfully!";
        $messageType = "ok";
    } catch (Exception $e) {
        $conn->rollBack();
        $message = "Error: " . $e->getMessage();
        $messageType = "danger";
    }
}

// 3. Fetch Weight History
try {
    $historySql = "SELECT wl.*, l.species 
                   FROM weight_logs wl 
                   JOIN livestock l ON wl.animal_id = l.animal_id 
                   ORDER BY wl.log_date DESC, wl.id DESC LIMIT 10";
    $historyStmt = $conn->query($historySql);
    $logs = $historyStmt->fetchAll();
    
    // Count total records for the pill
    $total_records = $conn->query("SELECT COUNT(*) FROM weight_logs")->fetchColumn();
} catch (PDOException $e) {
    $logs = [];
    $total_records = 0;
}

$page_title   = 'Weight Log';
$current_page = 'weightLog';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AgriHub — Weight Log</title>
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
                <h1 class="ag-page-title">Weight <em>log.</em></h1>
            </div>

            <?php if ($message): ?>
            <div class="ag-tag <?= $messageType ?>"
                style="margin-bottom: 20px; width: 100%; padding: 10px; text-align: center;">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>

            <div class="ag-card ag-mb-md">
                <div class="ag-card-header">
                    <span class="ag-card-title">Log a new weight entry</span>
                </div>
                <div class="ag-card-body">
                    <form method="POST" action="">
                        <div class="ag-form-grid"
                            style="grid-template-columns:1fr 1fr 1fr 140px;gap:12px;align-items:flex-end;">
                            <div class="ag-form-group" style="margin-bottom:0;">
                                <label class="ag-label">Animal ID / Tag</label>
                                <input class="ag-input" type="text" name="tag_id" placeholder="e.g. C-041" required />
                            </div>
                            <div class="ag-form-group" style="margin-bottom:0;">
                                <label class="ag-label">Weight (kg)</label>
                                <input class="ag-input" type="number" step="0.1" name="weight" placeholder="0.0"
                                    required />
                            </div>
                            <div class="ag-form-group" style="margin-bottom:0;">
                                <label class="ag-label">Date</label>
                                <input class="ag-input" type="date" name="log_date" value="<?= date('Y-m-d') ?>" />
                            </div>
                            <button type="submit" class="ag-btn ag-btn-primary" style="height:42px;">Save entry</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="ag-card">
                <div class="ag-card-header">
                    <span class="ag-card-title">Weight history</span>
                    <span class="ag-pill"><?= $total_records ?> records</span>
                </div>
                <table class="ag-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Animal ID</th>
                            <th>Type</th>
                            <th>Previous (kg)</th>
                            <th>Current (kg)</th>
                            <th>Change</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding:20px;">No weight logs found.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="muted"><?= date('M d, Y', strtotime($log['log_date'])) ?></td>
                            <td class="strong"><?= htmlspecialchars($log['animal_id']) ?></td>
                            <td class="muted"><?= htmlspecialchars($log['species']) ?></td>
                            <td><?= number_format($log['previous_weight'], 1) ?></td>
                            <td class="strong"><?= number_format($log['current_weight'], 1) ?></td>
                            <td>
                                <?php 
                                        $change = $log['weight_change'];
                                        $class = ($change >= 0) ? 'ok' : 'danger';
                                        $prefix = ($change >= 0) ? '+' : '';
                                    ?>
                                <span class="ag-tag <?= $class ?>"><?= $prefix . number_format($change, 1) ?> kg</span>
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
                <div class="ag-side-title">Tips for Growth</div>
                <div class="ag-activity-item">
                    <div class="ag-activity-dot new"></div>
                    <div>
                        <div class="ag-activity-text">Consistent weighing (e.g., every 2 weeks) helps track health
                            trends.</div>
                    </div>
                </div>
                <div class="ag-activity-item">
                    <div class="ag-activity-dot"></div>
                    <div>
                        <div class="ag-activity-text">Sudden weight loss may indicate illness or dietary issues.</div>
                    </div>
                </div>
            </div>

            <div class="ag-side-card">
                <div class="ag-side-title">Statistics</div>
                <div class="ag-meta-row">
                    <span class="ag-meta-lbl">Logged today</span>
                    <span
                        class="ag-meta-val"><?= count(array_filter($logs, function($l) { return $l['log_date'] == date('Y-m-d'); })) ?></span>
                </div>
                <div class="ag-meta-row">
                    <span class="ag-meta-lbl">Records this month</span>
                    <span class="ag-meta-val"><?= count($logs) ?></span>
                </div>
            </div>
        </aside>
    </div>

</body>

</html>