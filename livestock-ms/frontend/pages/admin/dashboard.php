<?php
require_once '../../../backend/db_config.php';

// Access Control
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Fetch Summary Data
try {
    $totalUsers = $conn->query("SELECT COUNT(*) FROM user")->fetchColumn();
    $activeFarmers = $conn->query("SELECT COUNT(*) FROM user WHERE user_role = 'farmer' AND user_status = 'active'")->fetchColumn();
    $availableLivestock = $conn->query("SELECT COUNT(*) FROM livestock WHERE sale_status = 'available'")->fetchColumn();
    $pendingOrders = $conn->query("SELECT COUNT(*) FROM `order` WHERE status = 'pending'")->fetchColumn();
    $totalSales = $conn->query("SELECT SUM(total_price) FROM transaction WHERE payment_status = 'paid'")->fetchColumn() ?? 0;
} catch (PDOException $e) {
    die("Error fetching dashboard data: " . $e->getMessage());
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AgriHub - Admin Dashboard</title>
    <link rel="stylesheet" href="../../css/main.css">
    <link rel="stylesheet" href="../../css/sidebar.css">
    <link rel="stylesheet" href="../../css/admin_dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Arvo:wght@400;700&display=swap" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="full-screen">
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
                    <li><a href="../../../backend/logout.php" class="side-link logout-btn">Logout</a></li>
                </ul>
            </nav>
            <div class="basic-profile">
                <div class="pfp"><?php echo substr($_SESSION['username'], 0, 2); ?></div>
                <div>
                    <div class="username"><?php echo $_SESSION['username']; ?></div>
                    <div class="designation">Admin</div>
                </div>
            </div>
        </div>

        <div class="main-body">
            <div class="content-title">
                <div class="panel-name">Dashboard Overview</div>
                <div class="des-pfp">
                    <div class="designation-icon">Admin</div>
                    <div class="pfp-2">AJ</div>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="stat-card">
                    <span class="label">Total Users</span>
                    <h2 class="value"><?php echo $totalUsers; ?></h2>
                </div>
                <div class="stat-card">
                    <span class="label">Active Farmers</span>
                    <h2 class="value"><?php echo $activeFarmers; ?></h2>
                </div>
                <div class="stat-card">
                    <span class="label">Available Livestock</span>
                    <h2 class="value"><?php echo $availableLivestock; ?></h2>
                </div>
                <div class="stat-card">
                    <span class="label">Pending Orders</span>
                    <h2 class="value" style="color: #e67e22;"><?php echo $pendingOrders; ?></h2>
                </div>
            </div>

            <div class="dashboard-middle">
                <div class="chart-container">
                    <h3>Livestock by Category</h3>
                    <canvas id="categoryChart"></canvas>
                </div>

                <div class="actions-panel">
                    <h3>Quick Actions</h3>
                    <div class="action-buttons">
                        <button onclick="location.href='users.php'">Approve Farmers</button>
                        <button onclick="location.href='categories.php'">Add Category</button>
                    </div>
                    
                    <h3 style="margin-top: 20px;">System Alerts</h3>
                    <div class="alert-box low-stock">Low Stock: Swine (Cebu)</div>
                    <div class="alert-box overdue">Overdue Transaction: #ORD-992</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Placeholder Chart.js Logic
        const ctx = document.getElementById('categoryChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Cattle', 'Swine', 'Goat', 'Poultry'],
                datasets: [{
                    data: [12, 19, 3, 5],
                    backgroundColor: ['#27ae60', '#2ecc71', '#f1c40f', '#e67e22']
                }]
            }
        });
    </script>
</body>
</html>