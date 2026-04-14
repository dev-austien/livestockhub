<?php
session_start();
require_once '../../../backend/shared/db_config.php';

// 1. Security & Role Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Farmer') {
    header("Location: ../auth/login.php"); 
    exit();
}

$farmer_id = $_SESSION['farmer_id'];
$message = "";
$messageType = "";

// 2. Handle Weight Entry Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['livestock_id'])) {
    $livestock_id = $_POST['livestock_id'];
    $new_weight   = $_POST['weight'];
    $log_date     = $_POST['log_date'] ?: date('Y-m-d');

    try {
        $pdo->beginTransaction();

        // Verify livestock belongs to this farmer and get previous weight
        $prevStmt = $pdo->prepare("SELECT weight FROM livestock WHERE livestock_id = ? AND farmer_id = ?");
        $prevStmt->execute([$livestock_id, $farmer_id]);
        $livestock = $prevStmt->fetch();

        if ($livestock) {
            $prev_weight = $livestock['weight'] ?: 0;
            $weight_change = $new_weight - $prev_weight;

            // Log into history (Assumes weight_logs table exists)
            $logSql = "INSERT INTO weight_logs (livestock_id, previous_weight, current_weight, weight_change, log_date) 
                       VALUES (?, ?, ?, ?, ?)";
            $pdo->prepare($logSql)->execute([$livestock_id, $prev_weight, $new_weight, $weight_change, $log_date]);

            // Update main livestock table
            $updateSql = "UPDATE livestock SET weight = ? WHERE livestock_id = ?";
            $pdo->prepare($updateSql)->execute([$new_weight, $livestock_id]);

            $pdo->commit();
            $message = "Weight entry for #LIV-{$livestock_id} recorded.";
            $messageType = "ok";
        } else {
            throw new Exception("Livestock ID not found in your inventory.");
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error: " . $e->getMessage();
        $messageType = "danger";
    }
}

// 3. Fetch History (Join with Category for context)
try {
    $historySql = "
        SELECT wl.*, c.category_name 
        FROM weight_logs wl 
        JOIN livestock l ON wl.livestock_id = l.livestock_id 
        JOIN category c ON l.category_id = c.category_id
        WHERE l.farmer_id = ?
        ORDER BY wl.log_date DESC, wl.weight_log_id DESC LIMIT 10";
    $historyStmt = $pdo->prepare($historySql);
    $historyStmt->execute([$farmer_id]);
    $logs = $historyStmt->fetchAll();
    
    $total_records = $pdo->prepare("SELECT COUNT(*) FROM weight_logs wl JOIN livestock l ON wl.livestock_id = l.livestock_id WHERE l.farmer_id = ?");
    $total_records->execute([$farmer_id]);
    $count = $total_records->fetchColumn();
} catch (PDOException $e) {
    $logs = [];
    $count = 0;
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
    <link rel="stylesheet" href="../../css/agrihub.css" />
</head>

<body>

    <?php include '../../css/include.css/nav.php'; ?>

    <div class="ag-page">
        <main class="ag-main">
            <div class="ag-page-header">
                <div class="ag-eyebrow">Farmer portal</div>
                <h1 class="ag-page-title">Growth <em>tracking.</em></h1>
            </div>

            <?php if ($message): ?>
            <div class="ag-tag <?= $messageType ?>"
                style="margin-bottom: 20px; width: 100%; padding: 12px; text-align: center;">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>

            <div class="ag-card ag-mb-md">
                <div class="ag-card-header">
                    <span class="ag-card-title">New entry</span>
                </div>
                <div class="ag-card-body">
                    <form method="POST">
                        <div class="ag-form-grid"
                            style="grid-template-columns:1fr 1fr 1fr 140px;gap:12px;align-items:flex-end;">
                            <div class="ag-form-group" style="margin-bottom:0;">
                                <label class="ag-label">Livestock ID</label>
                                <input class="ag-input" type="number" name="livestock_id" placeholder="e.g. 42"
                                    required />
                            </div>
                            <div class="ag-form-group" style="margin-bottom:0;">
                                <label class="ag-label">New Weight (kg)</label>
                                <input class="ag-input" type="number" step="0.01" name="weight" placeholder="0.00"
                                    required />
                            </div>
                            <div class="ag-form-group" style="margin-bottom:0;">
                                <label class="ag-label">Date</label>
                                <input class="ag-input" type="date" name="log_date" value="<?= date('Y-m-d') ?>" />
                            </div>
                            <button type="submit" class="ag-btn ag-btn-primary" style="height:42px;">Log Weight</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="ag-card">
                <div class="ag-card-header">
                    <span class="ag-card-title">Recent History</span>
                    <span class="ag-pill"><?= $count ?> logs total</span>
                </div>
                <table class="ag-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>ID</th>
                            <th>Category</th>
                            <th>Prev (kg)</th>
                            <th>New (kg)</th>
                            <th>Growth</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): 
                            $change = $log['weight_change'];
                            $class = ($change >= 0) ? 'ok' : 'danger';
                        ?>
                        <tr>
                            <td class="muted"><?= date('M d, Y', strtotime($log['log_date'])) ?></td>
                            <td class="strong">#LIV-<?= $log['livestock_id'] ?></td>
                            <td class="muted"><?= htmlspecialchars($log['category_name']) ?></td>
                            <td><?= number_format($log['previous_weight'], 2) ?></td>
                            <td class="strong"><?= number_format($log['current_weight'], 2) ?></td>
                            <td>
                                <span
                                    class="ag-tag <?= $class ?>"><?= ($change >= 0 ? '+' : '') . number_format($change, 2) ?>
                                    kg</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding:30px;">No growth data found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>

        <aside class="ag-sidebar">
            <div class="ag-side-card">
                <div class="ag-side-title">Farming Insight</div>
                <div class="ag-activity-item">
                    <div class="ag-activity-dot new"></div>
                    <div>
                        <div class="ag-activity-text">Bi-weekly weighing is the gold standard for tracking livestock
                            health.</div>
                    </div>
                </div>
                <div class="ag-activity-item">
                    <div class="ag-activity-dot"></div>
                    <div>
                        <div class="ag-activity-text">Negative growth may indicate a need for a dietary review.</div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</body>

</html>