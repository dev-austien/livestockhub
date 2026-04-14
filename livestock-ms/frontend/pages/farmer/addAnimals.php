<?php
require_once '../../../backend/db_config.php';
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'farmer') {
    header("Location: ../auth/login.php"); exit();
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
    <link rel="stylesheet" href="/livestock-ms/frontend/css/agrihub.css" />
</head>

<body>

    <?php include '../includes/nav.php'; ?>

    <div class="ag-page">
        <main class="ag-main">
            <div class="ag-page-header">
                <div class="ag-eyebrow">Farmer portal</div>
                <h1 class="ag-page-title">Register a <em>new animal.</em></h1>
            </div>

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
                                    <option>Cattle</option>
                                    <option>Goat</option>
                                    <option>Pig</option>
                                    <option>Chicken</option>
                                    <option>Sheep</option>
                                    <option>Other</option>
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
                                <input class="ag-input" type="text" name="tag_id" placeholder="e.g. C-043" />
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
                                    <option>Male</option>
                                    <option>Female</option>
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
                                <option value="healthy">Healthy</option>
                                <option value="monitor">Needs monitoring</option>
                                <option value="sick">Sick</option>
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
                        <div class="ag-activity-text">Record the initial weight right after purchase or birth.</div>
                    </div>
                </div>
                <div class="ag-activity-item">
                    <div class="ag-activity-dot"></div>
                    <div>
                        <div class="ag-activity-text">Use the notes field for vaccination or medication history.</div>
                    </div>
                </div>
            </div>

            <div class="ag-side-card">
                <div class="ag-side-title">Recent additions</div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">C-042 — Cattle</span><span class="ag-meta-val">2d
                        ago</span></div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">H-103 — Chicken</span><span class="ag-meta-val">5d
                        ago</span></div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">G-019 — Goat</span><span class="ag-meta-val">1w
                        ago</span></div>
            </div>
        </aside>
    </div>

</body>

</html>