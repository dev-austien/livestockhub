<?php
require_once '../../../backend/db_config.php';
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'farmer') {
    header("Location: ../auth/login.php"); exit();
}
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
    <link rel="stylesheet" href="/livestock-ms/frontend/css/agrihub.css" />
</head>

<body>

    <?php include '../includes/nav.php'; ?>

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
                            <input class="ag-input" type="text" value="James Family Farm" readonly />
                        </div>
                        <div class="ag-form-group">
                            <label class="ag-label">Farm ID</label>
                            <input class="ag-input" type="text" value="FM-00491" readonly />
                        </div>
                    </div>
                    <div class="ag-form-grid ag-mb-md">
                        <div class="ag-form-group">
                            <label class="ag-label">Location</label>
                            <input class="ag-input" type="text" value="Cebu, Philippines" readonly />
                        </div>
                        <div class="ag-form-group">
                            <label class="ag-label">Farm size</label>
                            <input class="ag-input" type="text" value="4.5 hectares" readonly />
                        </div>
                    </div>
                    <div class="ag-form-grid">
                        <div class="ag-form-group">
                            <label class="ag-label">Primary livestock</label>
                            <input class="ag-input" type="text" value="Cattle, Goats" readonly />
                        </div>
                        <div class="ag-form-group">
                            <label class="ag-label">Operating since</label>
                            <input class="ag-input" type="text" value="March 2019" readonly />
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
                        <tr>
                            <td class="strong">Pen A</td>
                            <td class="muted">Cattle</td>
                            <td>50</td>
                            <td>42</td>
                            <td><span class="ag-tag ok">Good</span></td>
                        </tr>
                        <tr>
                            <td class="strong">Pen B</td>
                            <td class="muted">Goats</td>
                            <td>40</td>
                            <td>38</td>
                            <td><span class="ag-tag warn">Near full</span></td>
                        </tr>
                        <tr>
                            <td class="strong">Pen C</td>
                            <td class="muted">Pigs</td>
                            <td>40</td>
                            <td>34</td>
                            <td><span class="ag-tag ok">Good</span></td>
                        </tr>
                        <tr>
                            <td class="strong">Pen D</td>
                            <td class="muted">Chickens</td>
                            <td>60</td>
                            <td>34</td>
                            <td><span class="ag-tag ok">Good</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>

        <aside class="ag-sidebar">
            <div class="ag-side-card">
                <div class="ag-side-title">Farm stats</div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">Total capacity</span><span
                        class="ag-meta-val">190</span></div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">Occupied</span><span
                        class="ag-meta-val warn">148</span></div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">Available</span><span
                        class="ag-meta-val ok">42</span></div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">Utilisation</span><span
                        class="ag-meta-val">78%</span></div>
            </div>

            <div class="ag-side-card">
                <div class="ag-side-title">Pen occupancy</div>
                <div class="ag-bar-group">
                    <div class="ag-bar-col">
                        <div class="ag-bar hi" style="height:46px;"></div>
                        <div class="ag-bar-lbl">A</div>
                    </div>
                    <div class="ag-bar-col">
                        <div class="ag-bar hi" style="height:52px;"></div>
                        <div class="ag-bar-lbl">B</div>
                    </div>
                    <div class="ag-bar-col">
                        <div class="ag-bar" style="height:40px;"></div>
                        <div class="ag-bar-lbl">C</div>
                    </div>
                    <div class="ag-bar-col">
                        <div class="ag-bar" style="height:30px;"></div>
                        <div class="ag-bar-lbl">D</div>
                    </div>
                </div>
            </div>

            <div class="ag-side-card">
                <div class="ag-side-title">Activity</div>
                <div class="ag-activity-item">
                    <div class="ag-activity-dot new"></div>
                    <div>
                        <div class="ag-activity-text">Pen B nearing capacity — consider expanding</div>
                        <div class="ag-activity-time">Today</div>
                    </div>
                </div>
                <div class="ag-activity-item">
                    <div class="ag-activity-dot"></div>
                    <div>
                        <div class="ag-activity-text">Pen A last cleaned 3 days ago</div>
                        <div class="ag-activity-time">3d ago</div>
                    </div>
                </div>
            </div>
        </aside>
    </div>

</body>

</html>