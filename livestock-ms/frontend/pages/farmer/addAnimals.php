<?php
session_start();
require_once '../../../backend/shared/db_config.php';

// Auth Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Farmer') {
    header("Location: ../auth/login.php");
    exit();
}

$farmer_id = $_SESSION['farmer_id'];

// ── Handle POST ──────────────────────────────────────────────────────────────
$action_msg = '';
$action_ok  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tag_number    = trim($_POST['tag_number']    ?? '');
    $location_id   = (int)($_POST['location_id']  ?? 0);
    $category_id   = (int)($_POST['category_id']  ?? 0);
    $breed_id      = !empty($_POST['breed_id'])    ? (int)$_POST['breed_id'] : null;
    $gender        = $_POST['gender']              ?? '';
    $health_status = trim($_POST['health_status']  ?? '');
    $date_of_birth = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
    $sale_status   = $_POST['sale_status']         ?? 'Available';
    $price         = !empty($_POST['price'])        ? (float)$_POST['price'] : 0.00;
    $current_weight= !empty($_POST['current_weight']) ? (float)$_POST['current_weight'] : null;
    $description   = trim($_POST['description']    ?? '');

    // Basic validation
    if (!$category_id || !$gender || !$location_id) {
        $action_msg = 'Please fill in all required fields.';
        $action_ok  = false;
    } else {
        // Verify location belongs to this farmer
        $checkStmt = $pdo->prepare("SELECT location_id FROM location WHERE location_id = ? AND farmer_id = ?");
        $checkStmt->execute([$location_id, $farmer_id]);
        if (!$checkStmt->fetch()) {
            $action_msg = 'Invalid location selected.';
            $action_ok  = false;
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO livestock
                    (tag_number, farmer_id, location_id, category_id, breed_id,
                     gender, health_status, date_of_birth, sale_status,
                     price, current_weight, description)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $tag_number ?: null,
                $farmer_id,
                $location_id,
                $category_id,
                $breed_id,
                $gender,
                $health_status ?: null,
                $date_of_birth,
                $sale_status,
                $price,
                $current_weight,
                $description ?: null,
            ]);
            $action_msg = 'Animal registered successfully.';
            $action_ok  = true;

            // Clear POST on success
            $_POST = [];
        }
    }
}

// ── Fetch dropdown data ──────────────────────────────────────────────────────

// Locations (pens/areas) for this farmer
$locStmt = $pdo->prepare("
    SELECT location_id, location_name, location_type
    FROM location
    WHERE farmer_id = ?
    ORDER BY location_name ASC
");
$locStmt->execute([$farmer_id]);
$locations = $locStmt->fetchAll();

// Categories
$catStmt = $pdo->query("SELECT category_id, category_name FROM category ORDER BY category_name");
$categories = $catStmt->fetchAll();

// Recently added livestock
$recentStmt = $pdo->prepare("
    SELECT l.livestock_id, l.tag_number, l.date_created,
           c.category_name, loc.location_name
    FROM livestock l
    LEFT JOIN category c ON l.category_id = c.category_id
    LEFT JOIN location loc ON l.location_id = loc.location_id
    WHERE l.farmer_id = ?
    ORDER BY l.date_created DESC
    LIMIT 5
");
$recentStmt->execute([$farmer_id]);
$recent_added = $recentStmt->fetchAll();

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
    <link rel="stylesheet" href="../../css/nav.css" />
</head>

<body>

    <?php
    $page_title = "Add Animals"; 
    include '../../css/include.css/nav.php'; 
    ?>

    <div class="ag-page">
        <main class="ag-main">
            <div class="ag-page-header">
                <div class="ag-eyebrow">Farmer portal</div>
                <h1 class="ag-page-title">Register a <em>new animal.</em></h1>
            </div>

            <!-- Alert message -->
            <?php if ($action_msg): ?>
            <div style="padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:13px;
             background:<?= $action_ok ? '#1E3A18' : '#2A0F0E' ?>;
             color:<?= $action_ok ? '#6FB84A' : '#E24B4A' ?>;
             border:1px solid <?= $action_ok ? 'rgba(111,184,74,0.2)' : 'rgba(226,75,74,0.2)' ?>;">
                <?= htmlspecialchars($action_msg) ?>
                <?php if ($action_ok): ?>
                — <a href="mylivestock.php" style="color:inherit;text-decoration:underline;">View all livestock</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- No locations warning -->
            <?php if (empty($locations)): ?>
            <div style="padding:16px;border-radius:10px;background:#2A1F08;color:#EF9F27;
             border:1px solid rgba(239,159,39,0.2);margin-bottom:20px;font-size:13px;">
                You don't have any farm areas yet. Please
                <a href="myFarm.php" style="color:#EF9F27;text-decoration:underline;">set up your farm and add areas</a>
                before registering animals.
            </div>
            <?php endif; ?>

            <div class="ag-card">
                <div class="ag-card-header">
                    <span class="ag-card-title">Animal details</span>
                </div>
                <div class="ag-card-body">
                    <form method="POST" action="" enctype="multipart/form-data">

                        <!-- Row 1: Category + Breed -->
                        <div class="ag-form-grid ag-mb-md">
                            <div class="ag-form-group" style="margin-bottom:0;">
                                <label class="ag-label">
                                    Animal type <span style="color:#E24B4A;">*</span>
                                </label>
                                <select class="ag-select" name="category_id" id="category_id" required
                                    onchange="loadBreeds(this.value)">
                                    <option value="">Select type…</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['category_id'] ?>"
                                        <?= (($_POST['category_id'] ?? '') == $cat['category_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['category_name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="ag-form-group" style="margin-bottom:0;">
                                <label class="ag-label">Breed</label>
                                <select class="ag-select" name="breed_id" id="breed_id">
                                    <option value="">Select type first…</option>
                                </select>
                            </div>
                        </div>

                        <!-- Row 2: Location + Tag number -->
                        <div class="ag-form-grid ag-mb-md">
                            <div class="ag-form-group" style="margin-bottom:0;">
                                <label class="ag-label">
                                    Farm area / Location <span style="color:#E24B4A;">*</span>
                                </label>
                                <select class="ag-select" name="location_id" required>
                                    <option value="">Select area…</option>
                                    <?php foreach ($locations as $loc): ?>
                                    <option value="<?= $loc['location_id'] ?>"
                                        <?= (($_POST['location_id'] ?? '') == $loc['location_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($loc['location_name']) ?>
                                        <?php if ($loc['location_type']): ?>
                                        (<?= htmlspecialchars($loc['location_type']) ?>)
                                        <?php endif; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="ag-form-group" style="margin-bottom:0;">
                                <label class="ag-label">Tag number</label>
                                <input class="ag-input" type="text" name="tag_number" placeholder="e.g. C-043, TAG-001"
                                    value="<?= htmlspecialchars($_POST['tag_number'] ?? '') ?>" />
                            </div>
                        </div>

                        <!-- Row 3: Gender + Date of birth -->
                        <div class="ag-form-grid ag-mb-md">
                            <div class="ag-form-group" style="margin-bottom:0;">
                                <label class="ag-label">
                                    Gender <span style="color:#E24B4A;">*</span>
                                </label>
                                <select class="ag-select" name="gender" required>
                                    <option value="">Select…</option>
                                    <option value="Male" <?= (($_POST['gender'] ?? '') === 'Male') ? 'selected' : '' ?>>
                                        Male
                                    </option>
                                    <option value="Female"
                                        <?= (($_POST['gender'] ?? '') === 'Female') ? 'selected' : '' ?>>
                                        Female
                                    </option>
                                </select>
                            </div>

                            <div class="ag-form-group" style="margin-bottom:0;">
                                <label class="ag-label">Date of birth</label>
                                <input class="ag-input" type="date" name="date_of_birth"
                                    value="<?= htmlspecialchars($_POST['date_of_birth'] ?? '') ?>" />
                            </div>
                        </div>

                        <!-- Row 4: Health status + Current weight -->
                        <div class="ag-form-grid ag-mb-md">
                            <div class="ag-form-group" style="margin-bottom:0;">
                                <label class="ag-label">Health status</label>
                                <input class="ag-input" type="text" name="health_status"
                                    placeholder="e.g. Healthy, Vaccinated, Under treatment"
                                    value="<?= htmlspecialchars($_POST['health_status'] ?? '') ?>" />
                            </div>

                            <div class="ag-form-group" style="margin-bottom:0;">
                                <label class="ag-label">Current weight (kg)</label>
                                <input class="ag-input" type="number" step="0.01" min="0" name="current_weight"
                                    placeholder="0.00"
                                    value="<?= htmlspecialchars($_POST['current_weight'] ?? '') ?>" />
                            </div>
                        </div>

                        <!-- Row 5: Sale status + Price -->
                        <div class="ag-form-grid ag-mb-md">
                            <div class="ag-form-group" style="margin-bottom:0;">
                                <label class="ag-label">Sale status</label>
                                <select class="ag-select" name="sale_status">
                                    <?php foreach (['Available', 'Reserved', 'Sold'] as $s): ?>
                                    <option value="<?= $s ?>"
                                        <?= (($_POST['sale_status'] ?? 'Available') === $s) ? 'selected' : '' ?>>
                                        <?= $s ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="ag-form-group" style="margin-bottom:0;">
                                <label class="ag-label">Price (₱)</label>
                                <input class="ag-input" type="number" step="0.01" min="0" name="price"
                                    placeholder="0.00" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" />
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="ag-form-group ag-mb-md">
                            <label class="ag-label">Description / Notes</label>
                            <textarea class="ag-textarea" name="description"
                                placeholder="Vaccination history, purchase details, or any other notes…"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        </div>

                        <!-- Livestock image -->
                        <div class="ag-form-group ag-mb-md">
                            <label class="ag-label">Animal photo</label>
                            <input class="ag-input" type="file" name="livestock_image"
                                accept="image/jpeg,image/png,image/webp" style="padding:8px 14px;cursor:pointer;" />
                            <span style="font-size:11px;color:rgba(255,255,255,0.25);margin-top:2px;">
                                JPG, PNG or WEBP. Max 2MB.
                            </span>
                        </div>

                        <div class="ag-flex ag-gap-sm">
                            <button type="submit" class="ag-btn ag-btn-primary"
                                <?= empty($locations) ? 'disabled' : '' ?>>
                                Register animal
                            </button>
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
                        <div class="ag-activity-text">Tag numbers help you track animals across weight logs and sales.
                        </div>
                    </div>
                </div>
                <div class="ag-activity-item">
                    <div class="ag-activity-dot"></div>
                    <div>
                        <div class="ag-activity-text">Record the current weight right after purchase or birth.</div>
                    </div>
                </div>
                <div class="ag-activity-item">
                    <div class="ag-activity-dot"></div>
                    <div>
                        <div class="ag-activity-text">Set sale status to "Available" to list the animal for buyers.
                        </div>
                    </div>
                </div>
                <div class="ag-activity-item">
                    <div class="ag-activity-dot"></div>
                    <div>
                        <div class="ag-activity-text">Use the description for vaccination or medication history.</div>
                    </div>
                </div>
            </div>

            <?php if (!empty($recent_added)): ?>
            <div class="ag-side-card">
                <div class="ag-side-title">Recently added</div>
                <?php foreach ($recent_added as $a): ?>
                <div class="ag-meta-row">
                    <span class="ag-meta-lbl">
                        <?= htmlspecialchars(($a['tag_number'] ?: '#' . $a['livestock_id']) . ' — ' . ($a['category_name'] ?? '—')) ?>
                    </span>
                    <span class="ag-meta-val">
                        <?= htmlspecialchars($a['location_name'] ?? '—') ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </aside>
    </div>

    <script>
    function loadBreeds(categoryId) {
        var select = document.getElementById('breed_id');
        select.innerHTML = '<option value="">Loading…</option>';

        if (!categoryId) {
            select.innerHTML = '<option value="">Select type first…</option>';
            return;
        }

        fetch('../../backend/ajax.php?action=get_breeds&category_id=' + categoryId)
            .then(function(r) {
                return r.json();
            })
            .then(function(breeds) {
                if (!breeds.length) {
                    select.innerHTML = '<option value="">No breeds listed</option>';
                    return;
                }
                var html = '<option value="">Select breed…</option>';
                breeds.forEach(function(b) {
                    html += '<option value="' + b.breed_id + '">' + b.breed_name + '</option>';
                });
                select.innerHTML = html;
            })
            .catch(function() {
                select.innerHTML = '<option value="">Could not load breeds</option>';
            });
    }

    // Pre-load breeds if category already selected after a failed POST
    var catSelect = document.getElementById('category_id');
    if (catSelect.value) {
        loadBreeds(catSelect.value);
    }
    </script>

</body>

</html>