<?php
require_once '../../../backend/db_config.php';
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'farmer') {
    header("Location: ../auth/login.php"); exit();
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
    <link rel="stylesheet" href="/livestock-ms/frontend/css/agrihub.css" />
</head>

<body>

    <?php include '../includes/nav.php'; ?>

    <div class="ag-page">
        <main class="ag-main">
            <div class="ag-page-header">
                <div class="ag-eyebrow">Farmer portal</div>
                <h1 class="ag-page-title">Weight <em>log.</em></h1>
            </div>

            <!-- Log new weight -->
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
                                <input class="ag-input" type="text" name="tag_id" placeholder="e.g. C-041" />
                            </div>
                            <div class="ag-form-group" style="margin-bottom:0;">
                                <label class="ag-label">Weight (kg)</label>
                                <input class="ag-input" type="number" step="0.1" name="weight" placeholder="0.0" />
                            </div>
                            <div class="ag-form-group" style="margin-bottom:0;">
                                <label class="ag-label">Date</label>
                                <input class="ag-input" type="date" name="log_date" />
                            </div>
                            <button type="submit" class="ag-btn ag-btn-primary" style="height:42px;">Save entry</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- History table -->
            <div class="ag-card">
                <div class="ag-card-header">
                    <span class="ag-card-title">Weight history</span>
                    <span class="ag-pill">148 records</span>
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
                        <tr>
                            <td class="muted">Apr 14, 2026</td>
                            <td class="strong">C-041</td>
                            <td class="muted">Cattle</td>
                            <td>300</td>
                            <td class="strong">312</td>
                            <td><span class="ag-tag ok">+12 kg</span></td>
                        </tr>
                        <tr>
                            <td class="muted">Apr 12, 2026</td>
                            <td class="strong">P-029</td>
                            <td class="muted">Pig</td>
                            <td>85</td>
                            <td class="strong">90</td>
                            <td><span class="ag-tag ok">+5 kg</span></td>
                        </tr>
                        <tr>
                            <td class="muted">Apr 10, 2026</td>
                            <td class="strong">G-018</td>
                            <td class="muted">Goat</td>
                            <td>50</td>
                            <td class="strong">48</td>
                            <td><span class="ag-tag danger">-2 kg</span></td>
                        </tr>
                        <tr>
                            <td class="muted">Apr 08, 2026</td>
                            <td class="strong">H-102</td>
                            <td class="muted">Chicken</td>
                            <td>2.1</td>
                            <td class="strong">2.4</td>
                            <td><span class="ag-tag ok">+0.3 kg</span></td>
                        </tr>
                        <tr>
                            <td class="muted">Apr 05, 2026</td>
                            <td class="strong">C-042</td>
                            <td class="muted">Cattle</td>
                            <td>274</td>
                            <td class="strong">280</td>
                            <td><span class="ag-tag ok">+6 kg</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>

        <aside class="ag-sidebar">
            <div class="ag-side-card">
                <div class="ag-side-title">Avg. growth this month</div>
                <div style="font-size:28px;font-weight:500;color:var(--accent);margin-bottom:4px;">+8.2 kg</div>
                <div class="ag-text-sm ag-text-muted">Per animal average</div>
                <div class="ag-divider"></div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">Best gainer</span><span
                        class="ag-meta-val ok">C-041</span></div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">Weight lost</span><span
                        class="ag-meta-val warn">G-018</span></div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">Weigh due</span><span class="ag-meta-val">2
                        animals</span></div>
            </div>

            <div class="ag-side-card">
                <div class="ag-side-title">Monthly total (kg)</div>
                <div class="ag-bar-group">
                    <div class="ag-bar-col">
                        <div class="ag-bar" style="height:28px;"></div>
                        <div class="ag-bar-lbl">N</div>
                    </div>
                    <div class="ag-bar-col">
                        <div class="ag-bar" style="height:36px;"></div>
                        <div class="ag-bar-lbl">D</div>
                    </div>
                    <div class="ag-bar-col">
                        <div class="ag-bar" style="height:32px;"></div>
                        <div class="ag-bar-lbl">J</div>
                    </div>
                    <div class="ag-bar-col">
                        <div class="ag-bar" style="height:44px;"></div>
                        <div class="ag-bar-lbl">F</div>
                    </div>
                    <div class="ag-bar-col">
                        <div class="ag-bar hi" style="height:40px;"></div>
                        <div class="ag-bar-lbl">M</div>
                    </div>
                    <div class="ag-bar-col">
                        <div class="ag-bar hi" style="height:54px;"></div>
                        <div class="ag-bar-lbl">A</div>
                    </div>
                </div>
            </div>
        </aside>
    </div>

</body>

</html>