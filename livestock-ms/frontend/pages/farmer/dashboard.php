<?php
require_once '../../../backend/db_config.php';
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'farmer') {
    header("Location: ../auth/login.php"); exit();
}
$page_title   = 'Dashboard';
$current_page = 'dashboard';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AgriHub — Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500&family=Playfair+Display:ital,wght@0,600;1,500&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="/.../.../css/agrihub.css" />
</head>

<body>

    <?php include '../includes/nav.php'; ?>

    <div class="ag-page">
        <main class="ag-main">
            <div class="ag-page-header">
                <div class="ag-eyebrow">Good morning, <?= htmlspecialchars($display_name) ?></div>
                <h1 class="ag-page-title">Your farm, <em>at a glance.</em></h1>
            </div>

            <div class="ag-stats ag-mb-md">
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Total Animals</div>
                    <div class="ag-stat-val">148</div>
                    <div class="ag-stat-delta">+4 this month</div>
                </div>
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Pending Orders</div>
                    <div class="ag-stat-val">12</div>
                    <div class="ag-stat-delta warn">3 new today</div>
                </div>
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Revenue (Mo.)</div>
                    <div class="ag-stat-val">₱84k</div>
                    <div class="ag-stat-delta">+11% vs last mo.</div>
                </div>
            </div>

            <div class="ag-card">
                <div class="ag-card-header">
                    <span class="ag-card-title">Recent livestock</span>
                    <span class="ag-pill">Live</span>
                </div>
                <table class="ag-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Animal</th>
                            <th>Breed</th>
                            <th>Weight</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="muted">C-041</td>
                            <td class="strong">Cattle</td>
                            <td class="muted">Brown Angus</td>
                            <td>312 kg</td>
                            <td><span class="ag-tag ok">Healthy</span></td>
                        </tr>
                        <tr>
                            <td class="muted">G-018</td>
                            <td class="strong">Goat</td>
                            <td class="muted">Nubian</td>
                            <td>48 kg</td>
                            <td><span class="ag-tag warn">Monitor</span></td>
                        </tr>
                        <tr>
                            <td class="muted">P-029</td>
                            <td class="strong">Pig</td>
                            <td class="muted">Large White</td>
                            <td>90 kg</td>
                            <td><span class="ag-tag ok">Healthy</span></td>
                        </tr>
                        <tr>
                            <td class="muted">H-102</td>
                            <td class="strong">Chicken</td>
                            <td class="muted">Broiler</td>
                            <td>2.4 kg</td>
                            <td><span class="ag-tag info">Weigh Due</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>

        <aside class="ag-sidebar">
            <div class="ag-side-card">
                <div class="ag-side-title">Farmer profile</div>
                <div class="ag-profile-row">
                    <div class="ag-profile-av"><?= htmlspecialchars($initials) ?></div>
                    <div>
                        <div class="ag-profile-name"><?= htmlspecialchars($display_name) ?></div>
                        <div class="ag-profile-role">Verified Farmer</div>
                    </div>
                </div>
                <div class="ag-divider"></div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">Farm ID</span><span
                        class="ag-meta-val">FM-00491</span></div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">Location</span><span class="ag-meta-val">Cebu,
                        PH</span></div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">Alerts</span><span class="ag-meta-val warn">2
                        active</span></div>
            </div>

            <div class="ag-side-card">
                <div class="ag-side-title">Weight trend</div>
                <div class="ag-bar-group">
                    <div class="ag-bar-col">
                        <div class="ag-bar" style="height:30px;"></div>
                        <div class="ag-bar-lbl">N</div>
                    </div>
                    <div class="ag-bar-col">
                        <div class="ag-bar" style="height:38px;"></div>
                        <div class="ag-bar-lbl">D</div>
                    </div>
                    <div class="ag-bar-col">
                        <div class="ag-bar" style="height:34px;"></div>
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
                        <div class="ag-bar hi" style="height:52px;"></div>
                        <div class="ag-bar-lbl">A</div>
                    </div>
                </div>
            </div>

            <div class="ag-side-card">
                <div class="ag-side-title">Activity</div>
                <div class="ag-activity-item">
                    <div class="ag-activity-dot new"></div>
                    <div>
                        <div class="ag-activity-text">Goat #G-018 flagged for monitoring</div>
                        <div class="ag-activity-time">2 hrs ago</div>
                    </div>
                </div>
                <div class="ag-activity-item">
                    <div class="ag-activity-dot"></div>
                    <div>
                        <div class="ag-activity-text">Order #ORD-552 received from buyer</div>
                        <div class="ag-activity-time">5 hrs ago</div>
                    </div>
                </div>
                <div class="ag-activity-item">
                    <div class="ag-activity-dot"></div>
                    <div>
                        <div class="ag-activity-text">Pig #P-029 weight updated — 90 kg</div>
                        <div class="ag-activity-time">Yesterday</div>
                    </div>
                </div>
            </div>
        </aside>
    </div>

</body>

</html>