<?php
require_once '../../../backend/db_config.php';
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'farmer') {
    header("Location: ../auth/login.php"); exit();
}
$page_title   = 'Transactions';
$current_page = 'transaction';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AgriHub — Transactions</title>
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
                <h1 class="ag-page-title">Transaction <em>history.</em></h1>
            </div>

            <div class="ag-stats ag-stats-4 ag-mb-md">
                <div class="ag-stat">
                    <div class="ag-stat-lbl">This month</div>
                    <div class="ag-stat-val">₱84k</div>
                    <div class="ag-stat-delta">+11% vs last mo.</div>
                </div>
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Last month</div>
                    <div class="ag-stat-val">₱75k</div>
                    <div class="ag-stat-delta ok">Settled</div>
                </div>
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Total sales</div>
                    <div class="ag-stat-val">₱612k</div>
                    <div class="ag-stat-delta">All time</div>
                </div>
                <div class="ag-stat">
                    <div class="ag-stat-lbl">Transactions</div>
                    <div class="ag-stat-val">248</div>
                    <div class="ag-stat-delta">All time</div>
                </div>
            </div>

            <div class="ag-card">
                <div class="ag-card-header">
                    <span class="ag-card-title">Recent transactions</span>
                    <span class="ag-pill">84 this month</span>
                </div>
                <table class="ag-table">
                    <thead>
                        <tr>
                            <th>Ref #</th>
                            <th>Order</th>
                            <th>Buyer</th>
                            <th>Item</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="muted">TXN-8841</td>
                            <td class="muted">ORD-548</td>
                            <td>Ana Ramos</td>
                            <td class="muted">20x Chicken</td>
                            <td class="strong">₱3,200</td>
                            <td class="muted">Apr 12</td>
                            <td><span class="ag-tag ok">Paid</span></td>
                        </tr>
                        <tr>
                            <td class="muted">TXN-8840</td>
                            <td class="muted">ORD-547</td>
                            <td>Ben Torres</td>
                            <td class="muted">2x Goat</td>
                            <td class="strong">₱4,200</td>
                            <td class="muted">Apr 11</td>
                            <td><span class="ag-tag ok">Paid</span></td>
                        </tr>
                        <tr>
                            <td class="muted">TXN-8839</td>
                            <td class="muted">ORD-545</td>
                            <td>Rose Villanueva</td>
                            <td class="muted">1x Cattle</td>
                            <td class="strong">₱12,000</td>
                            <td class="muted">Apr 10</td>
                            <td><span class="ag-tag ok">Paid</span></td>
                        </tr>
                        <tr>
                            <td class="muted">TXN-8838</td>
                            <td class="muted">ORD-543</td>
                            <td>Mark Lim</td>
                            <td class="muted">4x Pig</td>
                            <td class="strong">₱16,000</td>
                            <td class="muted">Apr 9</td>
                            <td><span class="ag-tag ok">Paid</span></td>
                        </tr>
                        <tr>
                            <td class="muted">TXN-8835</td>
                            <td class="muted">ORD-540</td>
                            <td>Joy Fernandez</td>
                            <td class="muted">3x Goat</td>
                            <td class="strong">₱6,000</td>
                            <td class="muted">Apr 7</td>
                            <td><span class="ag-tag warn">Pending</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>

        <aside class="ag-sidebar">
            <div class="ag-side-card">
                <div class="ag-side-title">Monthly revenue</div>
                <div class="ag-bar-group">
                    <div class="ag-bar-col">
                        <div class="ag-bar" style="height:30px;"></div>
                        <div class="ag-bar-lbl">N</div>
                    </div>
                    <div class="ag-bar-col">
                        <div class="ag-bar" style="height:40px;"></div>
                        <div class="ag-bar-lbl">D</div>
                    </div>
                    <div class="ag-bar-col">
                        <div class="ag-bar" style="height:34px;"></div>
                        <div class="ag-bar-lbl">J</div>
                    </div>
                    <div class="ag-bar-col">
                        <div class="ag-bar" style="height:38px;"></div>
                        <div class="ag-bar-lbl">F</div>
                    </div>
                    <div class="ag-bar-col">
                        <div class="ag-bar hi" style="height:42px;"></div>
                        <div class="ag-bar-lbl">M</div>
                    </div>
                    <div class="ag-bar-col">
                        <div class="ag-bar hi" style="height:54px;"></div>
                        <div class="ag-bar-lbl">A</div>
                    </div>
                </div>
            </div>

            <div class="ag-side-card">
                <div class="ag-side-title">Revenue split</div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">Cattle sales</span><span
                        class="ag-meta-val ok">₱38,400</span></div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">Goat sales</span><span
                        class="ag-meta-val">₱22,100</span></div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">Pig sales</span><span
                        class="ag-meta-val">₱16,500</span></div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">Chicken sales</span><span
                        class="ag-meta-val">₱7,200</span></div>
            </div>

            <div class="ag-side-card">
                <div class="ag-side-title">Top buyers</div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">Juan Santos</span><span
                        class="ag-meta-val ok">₱24k</span></div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">Mark Lim</span><span class="ag-meta-val">₱16k</span>
                </div>
                <div class="ag-meta-row"><span class="ag-meta-lbl">Rose Villanueva</span><span
                        class="ag-meta-val">₱12k</span></div>
            </div>
        </aside>
    </div>

</body>

</html>