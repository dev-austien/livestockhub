<?php
require_once '../../../backend/db_config.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// ── Summary Cards ──────────────────────────────────────────────────────────────
$total_users       = $pdo->query("SELECT COUNT(*) FROM user")->fetchColumn();
$active_farmers    = $pdo->query("SELECT COUNT(*) FROM farmers f JOIN user u ON f.user_id = u.user_id WHERE u.user_status = 'active'")->fetchColumn();
$avail_livestock   = $pdo->query("SELECT COUNT(*) FROM livestock WHERE sale_status = 'available'")->fetchColumn();
$pending_orders    = $pdo->query("SELECT COUNT(*) FROM `order` WHERE status = 'pending'")->fetchColumn();
$completed_tx      = $pdo->query("SELECT COUNT(*) FROM transaction WHERE payment_status = 'paid'")->fetchColumn();

// ── Charts Data ────────────────────────────────────────────────────────────────
// Livestock by category
$livestock_by_cat = $pdo->query("
    SELECT c.category_name, COUNT(l.livestock_id) AS total
    FROM category c
    LEFT JOIN livestock l ON c.category_id = l.category_id
    GROUP BY c.category_id, c.category_name
")->fetchAll(PDO::FETCH_ASSOC);

// Orders by status
$orders_by_status = $pdo->query("
    SELECT status, COUNT(*) AS total
    FROM `order`
    GROUP BY status
")->fetchAll(PDO::FETCH_ASSOC);

// Sales (completed orders) by farmer
$sales_by_farmer = $pdo->query("
    SELECT f.farm_name, COUNT(o.order_id) AS total_sales, SUM(o.total_price) AS revenue
    FROM `order` o
    JOIN farmers f ON o.which_farmer = f.farmer_id
    WHERE o.status = 'completed'
    GROUP BY f.farmer_id, f.farm_name
    ORDER BY total_sales DESC
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

// ── Quick Actions Data ─────────────────────────────────────────────────────────
$pending_farmers = $pdo->query("
    SELECT f.farmer_id, u.user_first_name, u.user_last_name, f.farm_name, u.created_at
    FROM farmers f
    JOIN user u ON f.user_id = u.user_id
    WHERE u.user_status = 'inactive'
    ORDER BY u.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$recent_orders = $pdo->query("
    SELECT o.order_id, u.user_first_name, u.user_last_name, o.total_price, o.status, o.reservation_expiry
    FROM `order` o
    JOIN user u ON o.buyer_id = u.user_id
    ORDER BY o.order_id DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

// ── Alerts ─────────────────────────────────────────────────────────────────────
$low_stock_cats = $pdo->query("
    SELECT c.category_name, COUNT(l.livestock_id) AS total
    FROM category c
    LEFT JOIN livestock l ON c.category_id = l.category_id AND l.sale_status = 'available'
    GROUP BY c.category_id, c.category_name
    HAVING total < 5
")->fetchAll(PDO::FETCH_ASSOC);

$no_inventory_farmers = $pdo->query("
    SELECT f.farm_name, u.user_first_name, u.user_last_name
    FROM farmers f
    JOIN user u ON f.user_id = u.user_id
    WHERE f.farmer_id NOT IN (
        SELECT DISTINCT farmer_id FROM livestock WHERE sale_status = 'available'
    )
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$overdue_transactions = $pdo->query("
    SELECT o.order_id, u.user_first_name, u.user_last_name, o.reservation_expiry, o.total_price
    FROM `order` o
    JOIN user u ON o.buyer_id = u.user_id
    WHERE o.status = 'pending' AND o.reservation_expiry < CURDATE()
    ORDER BY o.reservation_expiry ASC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// ── Encode JSON for charts ─────────────────────────────────────────────────────
$cat_labels   = json_encode(array_column($livestock_by_cat, 'category_name'));
$cat_data     = json_encode(array_column($livestock_by_cat, 'total'));
$ord_labels   = json_encode(array_column($orders_by_status, 'status'));
$ord_data     = json_encode(array_column($orders_by_status, 'total'));
$farm_labels  = json_encode(array_column($sales_by_farmer, 'farm_name'));
$farm_sales   = json_encode(array_column($sales_by_farmer, 'total_sales'));
$farm_revenue = json_encode(array_column($sales_by_farmer, 'revenue'));

$alerts_count = count($low_stock_cats) + count($no_inventory_farmers) + count($overdue_transactions);
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AgriHub — Dashboard</title>
    <link rel="stylesheet" href="../../css/main.css">
    <link rel="stylesheet" href="../../css/sidebar.css">
    <link rel="stylesheet" href="../../css/admin_dashboard.css">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Arvo:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js" defer></script>
  </head>
  <body>
    <div class="full-screen">

      <!-- ── Sidebar ── -->
      <div class="left-wing">
        <div class="logo-panel">
          <div class="logo">AgriHub</div>
          <div class="panel-description">Admins Panel</div>
        </div>
        <nav class="side-bar">
          <ul>
            <li><a href="dashboard.php" class="side-link active">Dashboard</a></li>
            <li><a href="users.php" class="side-link">All Users</a></li>
            <li><a href="livestock.php" class="side-link">All Livestock</a></li>
            <li><a href="categories.php" class="side-link">Categories</a></li>
            <li><a href="breeds.php" class="side-link">Breeds</a></li>
            <li><a href="orders.php" class="side-link">Orders</a></li>
            <li><a href="transaction.php" class="side-link">Transactions</a></li>
            <li><a href="report.php" class="side-link">Reports</a></li>
          </ul>
        </nav>
        <div class="basic-profile">
          <div class="pfp">AJ</div>
          <div>
            <div class="username">Austien James</div>
            <div class="designation">Admin</div>
          </div>
        </div>
      </div>

      <!-- ── Main Content ── -->
      <div class="main-content">

        <!-- Top Bar -->
        <div class="content-title">
          <div class="panel-name">
            Dashboard
            <?php if ($alerts_count > 0): ?>
              <span class="alert-badge"><?= $alerts_count ?></span>
            <?php endif; ?>
          </div>
          <div class="des-pfp">
            <div class="designation-icon">Admin</div>
            <div class="pfp-2">AJ</div>
          </div>
        </div>

        <div class="dashboard-body">

          <!-- ── Alerts Banner ─────────────────────────────────────── -->
          <?php if ($alerts_count > 0): ?>
          <section class="alerts-section">
            <div class="alerts-header">
              <span class="alerts-icon">&#9888;</span>
              <h2>Alerts <span class="alert-count-label"><?= $alerts_count ?> issue<?= $alerts_count > 1 ? 's' : '' ?> need attention</span></h2>
            </div>
            <div class="alerts-grid">

              <?php foreach ($low_stock_cats as $cat): ?>
              <div class="alert-card alert-warning">
                <div class="alert-type">Low Stock</div>
                <div class="alert-msg">
                  <strong><?= htmlspecialchars($cat['category_name']) ?></strong>
                  only <?= $cat['total'] ?> head<?= $cat['total'] != 1 ? 's' : '' ?> available
                </div>
              </div>
              <?php endforeach; ?>

              <?php foreach ($no_inventory_farmers as $f): ?>
              <div class="alert-card alert-info">
                <div class="alert-type">No Inventory</div>
                <div class="alert-msg">
                  <strong><?= htmlspecialchars($f['farm_name']) ?></strong>
                  (<?= htmlspecialchars($f['user_first_name'] . ' ' . $f['user_last_name']) ?>) has no active listings
                </div>
              </div>
              <?php endforeach; ?>

              <?php foreach ($overdue_transactions as $t): ?>
              <div class="alert-card alert-danger">
                <div class="alert-type">Overdue</div>
                <div class="alert-msg">
                  Order #<?= $t['order_id'] ?> from
                  <strong><?= htmlspecialchars($t['user_first_name'] . ' ' . $t['user_last_name']) ?></strong>
                  expired <?= htmlspecialchars($t['reservation_expiry']) ?> — ₱<?= number_format($t['total_price'], 2) ?>
                </div>
              </div>
              <?php endforeach; ?>

            </div>
          </section>
          <?php endif; ?>

          <!-- ── Summary Cards ─────────────────────────────────────── -->
          <section class="summary-cards">
            <div class="stat-card">
              <div class="stat-icon stat-icon--users">&#128101;</div>
              <div class="stat-info">
                <div class="stat-label">Total Users</div>
                <div class="stat-value"><?= number_format($total_users) ?></div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon stat-icon--farmers">&#127807;</div>
              <div class="stat-info">
                <div class="stat-label">Active Farmers</div>
                <div class="stat-value"><?= number_format($active_farmers) ?></div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon stat-icon--livestock">&#128004;</div>
              <div class="stat-info">
                <div class="stat-label">Available Livestock</div>
                <div class="stat-value"><?= number_format($avail_livestock) ?></div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon stat-icon--orders">&#128203;</div>
              <div class="stat-info">
                <div class="stat-label">Pending Orders</div>
                <div class="stat-value"><?= number_format($pending_orders) ?></div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon stat-icon--tx">&#10003;</div>
              <div class="stat-info">
                <div class="stat-label">Completed Transactions</div>
                <div class="stat-value"><?= number_format($completed_tx) ?></div>
              </div>
            </div>
          </section>

          <!-- ── Charts Row ─────────────────────────────────────────── -->
          <section class="charts-row">

            <div class="chart-card">
              <div class="chart-card-header">
                <h3>Livestock by Category</h3>
              </div>
              <div class="chart-wrapper">
                <canvas id="chartCategory" role="img" aria-label="Doughnut chart of livestock by category"></canvas>
              </div>
            </div>

            <div class="chart-card">
              <div class="chart-card-header">
                <h3>Orders by Status</h3>
              </div>
              <div class="chart-wrapper">
                <canvas id="chartOrders" role="img" aria-label="Bar chart of orders grouped by status"></canvas>
              </div>
            </div>

            <div class="chart-card chart-card--wide">
              <div class="chart-card-header">
                <h3>Sales by Farmer</h3>
              </div>
              <div class="chart-wrapper chart-wrapper--tall">
                <canvas id="chartFarmer" role="img" aria-label="Horizontal bar chart of sales per farmer"></canvas>
              </div>
            </div>

          </section>

          <!-- ── Quick Actions + Recent Orders ────────────────────── -->
          <section class="bottom-row">

            <!-- Pending Farmer Approvals -->
            <div class="action-card">
              <div class="action-card-header">
                <h3>Pending Farmer Approvals</h3>
                <a href="users.php?filter=inactive" class="view-all-link">View all</a>
              </div>
              <?php if (empty($pending_farmers)): ?>
                <p class="empty-state">No pending approvals.</p>
              <?php else: ?>
                <ul class="approval-list">
                  <?php foreach ($pending_farmers as $f): ?>
                  <li class="approval-item">
                    <div class="approval-info">
                      <div class="approval-name"><?= htmlspecialchars($f['user_first_name'] . ' ' . $f['user_last_name']) ?></div>
                      <div class="approval-farm"><?= htmlspecialchars($f['farm_name']) ?></div>
                    </div>
                    <a href="users.php?approve=<?= $f['farmer_id'] ?>" class="btn-approve">Approve</a>
                  </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
              <div class="quick-action-btns">
                <a href="categories.php?action=add" class="btn-quick">+ Add Category</a>
              </div>
            </div>

            <!-- Recent Orders -->
            <div class="action-card action-card--wide">
              <div class="action-card-header">
                <h3>Recent Orders</h3>
                <a href="orders.php" class="view-all-link">View all</a>
              </div>
              <?php if (empty($recent_orders)): ?>
                <p class="empty-state">No orders yet.</p>
              <?php else: ?>
                <div class="table-wrap">
                  <table class="orders-table">
                    <thead>
                      <tr>
                        <th>Order #</th>
                        <th>Buyer</th>
                        <th>Amount</th>
                        <th>Expiry</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($recent_orders as $o): ?>
                      <tr>
                        <td>#<?= $o['order_id'] ?></td>
                        <td><?= htmlspecialchars($o['user_first_name'] . ' ' . $o['user_last_name']) ?></td>
                        <td>₱<?= number_format($o['total_price'], 2) ?></td>
                        <td><?= $o['reservation_expiry'] ? htmlspecialchars($o['reservation_expiry']) : '—' ?></td>
                        <td><span class="status-pill status--<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>

          </section>

        </div><!-- /dashboard-body -->
      </div><!-- /main-content -->
    </div><!-- /full-screen -->

    <!-- ── Charts Script ─────────────────────────────────────────────── -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {

      const catLabels  = <?= $cat_labels ?>;
      const catData    = <?= $cat_data ?>;
      const ordLabels  = <?= $ord_labels ?>;
      const ordData    = <?= $ord_data ?>;
      const farmLabels = <?= $farm_labels ?>;
      const farmSales  = <?= $farm_sales ?>;

      const palette = ['#2E7D32','#558B2F','#8BC34A','#CDDC39','#FFC107','#FF8F00','#E65100','#6D4C41'];

      // Livestock by Category — Doughnut
      new Chart(document.getElementById('chartCategory'), {
        type: 'doughnut',
        data: {
          labels: catLabels,
          datasets: [{
            data: catData,
            backgroundColor: palette,
            borderWidth: 2,
            borderColor: '#fff'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12, font: { size: 12 } } }
          }
        }
      });

      // Orders by Status — Bar
      const statusColors = {
        pending:   '#FFC107',
        confirmed: '#4CAF50',
        cancelled: '#F44336',
        completed: '#2196F3'
      };
      new Chart(document.getElementById('chartOrders'), {
        type: 'bar',
        data: {
          labels: ordLabels.map(l => l.charAt(0).toUpperCase() + l.slice(1)),
          datasets: [{
            label: 'Orders',
            data: ordData,
            backgroundColor: ordLabels.map(l => statusColors[l] || '#888'),
            borderRadius: 6,
            borderSkipped: false
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(0,0,0,0.06)' } },
            x: { grid: { display: false } }
          }
        }
      });

      // Sales by Farmer — Horizontal Bar
      new Chart(document.getElementById('chartFarmer'), {
        type: 'bar',
        data: {
          labels: farmLabels,
          datasets: [{
            label: 'Completed Sales',
            data: farmSales,
            backgroundColor: '#2E7D32',
            borderRadius: 4,
            borderSkipped: false
          }]
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            x: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(0,0,0,0.06)' } },
            y: { grid: { display: false } }
          }
        }
      });

    });
    </script>

  </body>
</html>