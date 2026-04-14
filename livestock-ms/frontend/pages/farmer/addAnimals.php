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

// 2. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize input
    $animal_type   = $_POST['animal_type'] ?? '';
    $breed         = $_POST['breed'] ?? '';
    $tag_id        = $_POST['tag_id'] ?? '';
    $dob           = $_POST['dob'] ?? null;
    $sex           = $_POST['sex'] ?? '';
    $weight        = $_POST['weight'] ?? 0;
    $health_status = $_POST['health_status'] ?? 'healthy';
    $notes         = $_POST['notes'] ?? '';
    $farmer_id     = $_SESSION['user_id'] ?? 1; // Assuming user_id is in session

    try {
        $sql = "INSERT INTO livestock (tag_number, species, breed_name, date_of_birth, sex, weight, health_status, notes, farmer_id, date_registered) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = $conn->prepare($sql);
$stmt->execute([$tag_id, $animal_type, $breed, $dob, $sex, $weight, $health_status, $notes, $farmer_id]);

        $message = "Animal registered successfully!";
        $messageType = "ok";
    } catch (PDOException $e) {
        $message = "Error: Could not register animal. " . $e->getMessage();
        $messageType = "danger";
    }
}

$page_title   = 'Add Animals';
$current_page = 'addAnimals';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AgriHub — Add Animals</title>
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
                <h1 class="ag-page-title">Register a <em>new animal.</em></h1>
            </div>

            <?php if ($message): ?>
            <div class="ag-tag <?= $messageType ?>"
                style="margin-bottom: 20px; width: 100%; padding: 10px; text-align: center;">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>

            <div class="ag-card">
                <div class="ag-card-header">
                    <span class="ag-card-title">Animal details</span>
                </div>
                <div class="ag-card-body">
                    <form method="POST" action="">
                        <div class="ag-form-grid ag-mb-md">
                            <div class="ag-form-group">
                                <label class="ag-label">Animal type</label>
                                <select class="ag-select" name="animal_type" required>
                                    <option value="">Select type…</option>
                                    <option value="Cattle">Cattle</option>
                                    <option value="Goat">Goat</option>
                                    <option value="Pig">Pig</option>
                                    <option value="Chicken">Chicken</option>
                                    <option value="Sheep">Sheep</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="ag-form-group">
                                <label class="ag-label">Breed</label>
                                <input class="ag-input" type="text" name="breed" placeholder="e.g. Brown Angus"
                                    required />
                            </div>
                        </div>

                        <div class="ag-form-grid ag-mb-md">
                            <div class="ag-form-group">
                                <label class="ag-label">Tag / ID</label>
                                <input class="ag-input" type="text" name="tag_id" placeholder="e.g. C-043" required />
                            </div>
                            <div class="ag-form-group">
                                <label class="ag-label">Date of birth</label>
                                <input class="ag-input" type="date" name="dob" />
                            </div>
                        </div>

                        <div class="ag-form-grid ag-mb-md">
                            <div class="ag-form-group">
                                <label class="ag-label">Sex</label>
                                <select class="ag-select" name="sex">
                                    <option value="">Select…</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="ag-form-group">
                                <label class="ag-label">Initial weight (kg)</label>
                                <input class="ag-input" type="number" step="0.1" name="weight" placeholder="0.0" />
                            </div>
                        </div>

                        <div class="ag-form-group ag-mb-md">
                            <label class="ag-label">Health status</label>
                            <select class="ag-select" name="health_status">
                                <option value="Healthy">Healthy</option>
                                <option value="Monitor">Needs monitoring</option>
                                <option value="Sick">Sick</option>
                            </select>
                        </div>

                        <div class="ag-form-group ag-mb-md">
                            <label class="ag-label">Notes</label>
                            <textarea class="ag-textarea" name="notes"
                                placeholder="Any additional notes about this animal…"></textarea>
                        </div>

                        <div class="ag-flex ag-gap-sm">
                            <button type="submit" class="ag-btn ag-btn-primary">Register animal</button>
                            <a href="mylivestock.php" class="ag-btn ag-btn-ghost">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>

        <aside class="ag-sidebar">
            <div class="ag-side-card">
                <div class="ag-side-title">Tips</div>
                <div class="ag-activity-item">
                    <div class="ag-activity-dot new"></div>
                    <div>
                        <div class="ag-activity-text">Tag IDs help you track animals across logs and orders.</div>
                    </div>
                </div>
                <div class="ag-activity-item">
                    <div class="ag-activity-dot"></div>
                    <div>
                        <div class="ag-activity-text">Record initial weight right after purchase or birth.</div>
                    </div>
                </div>
            </div>

            <div class="ag-side-card">
                <div class="ag-side-title">Recently Added</div>
                <?php
                // Fetch last 3 added animals for the sidebar
                $recentStmt = $conn->query("SELECT animal_id, species, created_at FROM livestock ORDER BY created_at DESC LIMIT 3");
                while ($row = $recentStmt->fetch()):
                ?>
                <div class="ag-meta-row">
                    <span class="ag-meta-lbl"><?= htmlspecialchars($row['animal_id']) ?> —
                        <?= htmlspecialchars($row['species']) ?></span>
                    <span class="ag-meta-val">New</span>
                </div>
                <?php endwhile; ?>
            </div>
        </aside>
    </div>

</body>

</html>