<?php
require_once '../../../backend/db_config.php';
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'farmer') {
    header("Location: ../auth/login.php"); exit();
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
    <link rel="stylesheet" href="/livestock-ms/frontend/css/agrihub.css" />
</head>

<body>

    <?php include '../includes/nav.php'; ?>

    <div class="ag-page">
        <main class="ag-main">
            <div class="ag-page-header">
                <div class="ag-eyebrow">Farmer portal</div>
                <h1 class="ag-page-title">My <em>livestock.</em></h1>
            </div>

            <div class="ag-stats ag-stats-4 ag-mb-md">
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Cattle</div>
                    <div class="ag-stat-val">42</div>
                    <div class="ag-stat-delta">+2 this month</div>
                </div>
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Goats</div>
                    <div class="ag-stat-val">38</div>
                    <div class="ag-stat-delta ok">Stable</div>
                </div>
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Pigs</div>
                    <div class="ag-stat-val">34</div>
                    <div class="ag-stat-delta warn">1 monitoring</div>
                </div>
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Chickens</div>
                    <div class="ag-stat-val">34</div>
                    <div class="ag-stat-delta ok">Healthy</div>
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
                        <tr>
                            <td class="muted">C-041</td>
                            <td class="strong">Cattle</td>
                            <td class="muted">Brown Angus</td>
                            <td>3 yr</td>
                            <td>312 kg</td>
                            <td><span class="ag-tag ok">Healthy</span></td>
                            <td><a href="#" class="ag-btn ag-btn-ghost"
                                    style="font-size:11px;padding:4px 10px;">View</a></td>
                        </tr>
                        <tr>
                            <td class="muted">C-042</td>
                            <td class="strong">Cattle</td>
                            <td class="muted">Brahman</td>
                            <td>2 yr</td>
                            <td>280 kg</td>
                            <td><span class="ag-tag ok">Healthy</span></td>
                            <td><a href="#" class="ag-btn ag-btn-ghost"
                                    style="font-size:11px;padding:4px 10px;">View</a></td>
                        </tr>
                        <tr>
                            <td class="muted">G-018</td>
                            <td class="strong">Goat</td>
                            <td class="muted">Nubian</td>
                            <td>1 yr</td>
                            <td>48 kg</td>
                            <td><span class="ag-tag warn">Monitor</span></td>
                            <td><a href="#" class="ag-btn ag-btn-ghost"
                                    style="font-size:11px;padding:4px 10px;">View</a></td>
                        </tr>
                        <tr>
                            <td class="muted">P-029</td>
                            <td class="strong">Pig</td>
                            <td class="muted">Large White</td>
                            <td>8 mo</td>
                            <td>90 kg</td>
                            <td><span class="ag-tag ok">Healthy</span></td>
                            <td><a href="#" class="ag-btn ag-btn-ghost"
                                    style="font-size:11px;padding:4px 10px;">View</a></td>
                        </tr>
                        <tr>
                            <td class="muted">H-102</td>
                            <td class="strong">Chicken</td>
                            <td class="muted">Broiler</td>
                            <td>4 mo</td>
                            <td>2.4 kg</td>
                            <td><span class="ag-tag info">Weigh Due</span></td>
                            <td><a href="#" class="ag-btn ag-btn-ghost"
                                    style="font-size:11px;padding:4px 10px;">View</a></td>
                        </tr>
                        <tr>
                            <td class="muted">H-103</td>
                            <td class="strong">Chicken</td>
                            <td class="muted">Broiler</td>
                            <td>4 mo</td>
                            <td>2.2 kg</td>
                            <td><span class="ag-tag ok">Healthy</span></td>
                            <td><a href="#" class="ag-btn ag-btn-ghost"
                                    style="font-size:11px;padding:4px 10px;">View</a></td>
                        </tr>
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
                <div class="ag-meta-row"><span class="ag-meta-lbl">Healthy</span><span class="ag-meta-val ok">144</span>
                </div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">Monitoring</span><span
                        class="ag-meta-val warn">2</span></div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">Critical</span><span
                        class="ag-meta-val danger">0</span></div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">Weigh due</span><span class="ag-meta-val">2</span>
                </div>
            </div>

            <div class="ag-side-card">
                <div class="ag-side-title">By type</div>
                <div class="ag-bar-group">
                    <div class="ag-bar-col">
                        <div class="ag-bar hi" style="height:42px;"></div>
                        <div class="ag-bar-lbl">Cattle</div>
                    </div>
                    <div class="ag-bar-col">
                        <div class="ag-bar hi" style="height:36px;"></div>
                        <div class="ag-bar-lbl">Goat</div>
                    </div>
                    <div class="ag-bar-col">
                        <div class="ag-bar" style="height:32px;"></div>
                        <div class="ag-bar-lbl">Pig</div>
                    </div>
                    <div class="ag-bar-col">
                        <div class="ag-bar" style="height:32px;"></div>
                        <div class="ag-bar-lbl">Chkn</div>
                    </div>
                </div>
            </div>
        </aside>
    </div>

</body>

</html>