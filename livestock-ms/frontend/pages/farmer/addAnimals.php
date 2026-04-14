<?php
session_start();
require_once '../../../backend/shared/db_config.php';

// 1. Security & Role Check
// Note: Changed to match the 'Farmer' casing used in your session logic
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Farmer') {
    header("Location: ../auth/login.php"); 
    exit();
}

// 2. Fetch Categories for the Dropdown (Dynamic Read)
$categories = $pdo->query("SELECT * FROM category ORDER BY category_name ASC")->fetchAll();

// 3. Fetch Locations/Pens for the Farmer
$stmtLoc = $pdo->prepare("SELECT * FROM location WHERE farmer_id = ?");
$stmtLoc->execute([$_SESSION['farmer_id']]);
$locations = $stmtLoc->fetchAll();

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

    <script>
    function loadBreeds(categoryId) {
        if (categoryId == "") {
            document.getElementById("breed_select").innerHTML = "<option value=''>Select category first…</option>";
            return;
        }
        // You can implement an AJAX call here later. For now, we'll keep it simple.
        // Or fetch all breeds and filter via JS.
    }
    </script>
</head>

<body>
    <?php include '../../css/include.css/nav.php'; ?>

    <div class="ag-page">
        <main class="ag-main">
            <div class="ag-page-header">
                <div class="ag-eyebrow">Farmer portal</div>
                <h1 class="ag-page-title">Register a <em>new animal.</em></h1>
            </div>

            <?php if (isset($_GET['msg'])): ?>
            <div class="ag-tag ok" style="margin-bottom: 20px; width: 100%; padding: 10px; text-align: center;">
                Animal registered successfully!
            </div>
            <?php endif; ?>

            <div class="ag-card">
                <div class="ag-card-header">
                    <span class="ag-card-title">Animal details</span>
                </div>
                <div class="ag-card-body">
                    <form method="POST" action="../../../backend/farmer/livestock_ctrl.php">
                        <input type="hidden" name="action" value="add">

                        <div class="ag-form-grid ag-mb-md">
                            <div class="ag-form-group">
                                <label class="ag-label">Animal Category</label>
                                <select class="ag-select" name="category_id" required onchange="loadBreeds(this.value)">
                                    <option value="">Select type…</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['category_id'] ?>">
                                        <?= htmlspecialchars($cat['category_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="ag-form-group">
                                <label class="ag-label">Breed ID</label>
                                <input class="ag-input" type="number" name="breed_id" placeholder="Breed ID" required />
                            </div>
                        </div>

                        <div class="ag-form-grid ag-mb-md">
                            <div class="ag-form-group">
                                <label class="ag-label">Farm Location / Pen</label>
                                <select class="ag-select" name="location_id" required>
                                    <option value="">Select pen/location…</option>
                                    <?php foreach ($locations as $loc): ?>
                                    <option value="<?= $loc['location_id'] ?>">
                                        <?= htmlspecialchars($loc['location_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="ag-form-group">
                                <label class="ag-label">Date of birth</label>
                                <input class="ag-input" type="date" name="date_of_birth" required />
                            </div>
                        </div>

                        <div class="ag-form-grid ag-mb-md">
                            <div class="ag-form-group">
                                <label class="ag-label">Sex</label>
                                <select class="ag-select" name="gender">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="ag-form-group">
                                <label class="ag-label">Initial weight (kg)</label>
                                <input class="ag-input" type="number" step="0.01" name="initial_weight"
                                    placeholder="0.00" required />
                            </div>
                        </div>

                        <div class="ag-form-group ag-mb-md">
                            <label class="ag-label">Health status</label>
                            <input class="ag-input" type="text" name="health_status"
                                placeholder="e.g. Healthy, Vaccinated" required>
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
                    <div class="ag-activity-text">Ensure your Location/Pens are set up in "My Farm" before adding
                        animals.</div>
                </div>
            </div>

            <div class="ag-side-card">
                <div class="ag-side-title">Your Recent Livestock</div>
                <?php
                $recentStmt = $pdo->prepare("SELECT l.livestock_id, c.category_name, l.date_created 
                                            FROM livestock l 
                                            JOIN category c ON l.category_id = c.category_id 
                                            WHERE l.farmer_id = ? 
                                            ORDER BY l.date_created DESC LIMIT 3");
                $recentStmt->execute([$_SESSION['farmer_id']]);
                while ($row = $recentStmt->fetch()):
                ?>
                <div class="ag-meta-row">
                    <span class="ag-meta-lbl">#<?= $row['livestock_id'] ?> —
                        <?= htmlspecialchars($row['category_name']) ?></span>
                    <span class="ag-meta-val">Added</span>
                </div>
                <?php endwhile; ?>
            </div>
        </aside>
    </div>
</body>

</html>