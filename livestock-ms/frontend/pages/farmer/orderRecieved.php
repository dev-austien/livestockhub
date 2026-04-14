<?php
require_once '../../../backend/db_config.php';
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'farmer') {
    header("Location: ../auth/login.php"); exit();
}
$page_title   = 'Orders Received';
$current_page = 'orderRecieved';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AgriHub — Orders Received</title>
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
                <h1 class="ag-page-title">Orders <em>received.</em></h1>
            </div>

            <div class="ag-stats ag-mb-md">
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Pending</div>
                    <div class="ag-stat-val">12</div>
                    <div class="ag-stat-delta warn">3 new today</div>
                </div>
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Confirmed</div>
                    <div class="ag-stat-val">5</div>
                    <div class="ag-stat-delta ok">Awaiting pickup</div>
                </div>
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Completed</div>
                    <div class="ag-stat-val">84</div>
                    <div class="ag-stat-delta">This month</div>
                </div>
            </div>

            <div class="ag-card">
                <div class="ag-card-header">
                    <span class="ag-card-title">All orders</span>
                    <span class="ag-pill">12 pending</span>
                </div>
                <table class="ag-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Buyer</th>
                            <th>Animal</th>
                            <th>Qty</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="strong">ORD-552</td>
                            <td>Juan Santos</td>
                            <td class="muted">Cattle</td>
                            <td>2</td>
                            <td class="strong">₱24,000</td>
                            <td class="muted">Apr 14</td>
                            <td><span class="ag-tag warn">Pending</span></td>
                            <td><button class="ag-btn ag-btn-primary"
                                    style="font-size:11px;padding:4px 10px;">Confirm</button></td>
                        </tr>
                        <tr>
                            <td class="strong">ORD-551</td>
                            <td>Liza Reyes</td>
                            <td class="muted">Goat</td>
                            <td>5</td>
                            <td class="strong">₱9,500</td>
                            <td class="muted">Apr 14</td>
                            <td><span class="ag-tag warn">Pending</span></td>
                            <td><button class="ag-btn ag-btn-primary"
                                    style="font-size:11px;padding:4px 10px;">Confirm</button></td>
                        </tr>
                        <tr>
                            <td class="strong">ORD-549</td>
                            <td>Marco Dela Cruz</td>
                            <td class="muted">Pig</td>
                            <td>3</td>
                            <td class="strong">₱12,000</td>
                            <td class="muted">Apr 13</td>
                            <td><span class="ag-tag info">Confirmed</span></td>
                            <td><button class="ag-btn ag-btn-secondary"
                                    style="font-size:11px;padding:4px 10px;">View</button></td>
                        </tr>
                        <tr>
                            <td class="strong">ORD-548</td>
                            <td>Ana Ramos</td>
                            <td class="muted">Chicken</td>
                            <td>20</td>
                            <td class="strong">₱3,200</td>
                            <td class="muted">Apr 12</td>
                            <td><span class="ag-tag ok">Completed</span></td>
                            <td><button class="ag-btn ag-btn-secondary"
                                    style="font-size:11px;padding:4px 10px;">View</button></td>
                        </tr>
                        <tr>
                            <td class="strong">ORD-547</td>
                            <td>Ben Torres</td>
                            <td class="muted">Goat</td>
                            <td>2</td>
                            <td class="strong">₱4,200</td>
                            <td class="muted">Apr 11</td>
                            <td><span class="ag-tag ok">Completed</span></td>
                            <td><button class="ag-btn ag-btn-secondary"
                                    style="font-size:11px;padding:4px 10px;">View</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>

        <aside class="ag-sidebar">
            <div class="ag-side-card">
                <div class="ag-side-title">Revenue this month</div>
                <div style="font-size:28px;font-weight:500;color:var(--accent);margin-bottom:4px;">₱84,200</div>
                <div class="ag-text-sm ag-text-muted">From 84 completed orders</div>
                <div class="ag-divider"></div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">Avg. order value</span><span
                        class="ag-meta-val">₱7,850</span></div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">Top buyer</span><span class="ag-meta-val ok">Juan
                        Santos</span></div>
            </div>

            <div class="ag-side-card">
                <div class="ag-side-title">Orders by type</div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">Cattle</span><span class="ag-meta-val">38%</span>
                </div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">Goat</span><span class="ag-meta-val">28%</span></div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">Pig</span><span class="ag-meta-val">22%</span></div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">Chicken</span><span class="ag-meta-val">12%</span>
                </div>
            </div>
        </aside>
    </div>

</body>

</html>