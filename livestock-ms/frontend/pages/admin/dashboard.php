<?php
require_once '../../../backend/db_config.php';

// Access Control
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// =========================================================
// PHP Logic: Fetch Real Data from your ERD Tables
// =========================================================
try {
    // 1. Total Users
    $totalUsers = $conn->query("SELECT COUNT(*) FROM user")->fetchColumn();
    
    // 2. Total Livestock
    $totalLivestock = $conn->query("SELECT COUNT(*) FROM livestock")->fetchColumn();
    
    // 3. Active Orders (matching the image text)
    $activeOrders = $conn->query("SELECT COUNT(*) FROM `order` WHERE status != 'completed' AND status != 'cancelled'")->fetchColumn();
    
    // 4. Revenue (Sum of paid transactions, matching your ERD)
    $revenueRaw = $conn->query("SELECT SUM(total_price) FROM transaction WHERE payment_status = 'paid'")->fetchColumn() ?? 0;
    
    // Formatting revenue for display (e.g., ₱1.2M or ₱210K)
    if ($revenueRaw >= 1000000) {
        $revenueFormatted = "₱" . round($revenueRaw / 1000000, 1) . "M";
    } elseif ($revenueRaw >= 1000) {
        $revenueFormatted = "₱" . round($revenueRaw / 1000, 1) . "K";
    } else {
        $revenueFormatted = "₱" . number_format($revenueRaw);
    }
    
    // Placeholder sub-text data (This needs more complex SQL to be dynamic)
    $usersThisWeek = 5;
    $livestockThisMonth = 18;
    $ordersValue = "₱210K value"; // Example text
    $revenueVsLastMo = "23%";     // Example percentage

} catch (PDOException $e) {
    die("Error fetching dashboard data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriStock - Admin Dashboard</title>
    
    <link rel="stylesheet" href="../../css/main.css">
    <link rel="stylesheet" href="../../css/sidebar.css">
    <link rel="stylesheet" href="../../css/admin_dashboard.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <div class="dashboard-wrapper">
        
        <nav class="sidebar">
            <div class="sidebar-header">
                <h1 class="logo-text">AgriStock</h1>
                <p class="panel-desc">Admins Panel</p>
            </div>
            
            <ul class="nav-links">
                <li><a href="dashboard.php" class="active"><i class="fas fa-th-large"></i> Dashboard</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> All Users</a></li>
                <li><a href="livestock.php"><i class="fas fa-cow"></i> All Livestock</a></li>
                <li><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
                <li><a href="breeds.php"><i class="fas fa-dna"></i> Breeds</a></li>
                <li><a href="orders.php"><i class="fas fa-receipt"></i> Order</a></li>
                <li><a href="transactions.php"><i class="fas fa-money-bill-wave"></i> Transactions</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a></li>
            </ul>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="pfp-circle">AJ</div>
                    <div class="user-details">
                        <p class="full-name"><?php echo $_SESSION['username'] ?? 'Austien James'; ?></p>
                        <p class="role-desc">Admin</p>
                    </div>
                </div>
            </div>
        </nav>
        
        <main class="main-content">
            
            <header class="top-navbar">
                <h2 class="page-title">Dashboard</h2>
                <div class="top-nav-actions">
                    <span class="role-badge farmer-btn">farmer</span>
                    <span class="role-badge admin-badge active">admin</span>
                    <div class="top-pfp">AJ</div>
                </div>
            </header>
            
            <section class="stats-grid">
                <div class="stat-card">
                    <div class="card-icon-wrapper user-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="card-data">
                        <p class="card-label">Total Users</p>
                        <h3 class="card-value"><?php echo $totalUsers; ?></h3>
                        <p class="card-subtext positive">+<?php echo $usersThisWeek; ?> this week</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="card-icon-wrapper livestock-icon">
                        <i class="fas fa-cow"></i>
                    </div>
                    <div class="card-data">
                        <p class="card-label">Total Livestock</p>
                        <h3 class="card-value"><?php echo $totalLivestock; ?></h3>
                        <p class="card-subtext positive">+<?php echo $livestockThisMonth; ?> this month</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="card-icon-wrapper orders-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="card-data">
                        <p class="card-label">Active Orders</p>
                        <h3 class="card-value"><?php echo $activeOrders; ?></h3>
                        <p class="card-subtext neutral"><?php echo $ordersValue; ?></p>
                    </div>
                </div>
                
                <div class="stat-card highlighted">
                    <div class="card-icon-wrapper revenue-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="card-data">
                        <p class="card-label highlighted-text">Revenue</p>
                        <h3 class="card-value"><?php echo $revenueFormatted; ?></h3>
                        <p class="card-subtext positive">+<?php echo $revenueVsLastMo; ?> vs last mo</p>
                    </div>
                </div>
            </section>
            
            <section class="panels-grid">
                <div class="panel recent-users">
                    <div class="panel-header">
                        <h4>Recent users</h4>
                        <a href="users.php" class="view-all">View all</a>
                    </div>
                    <div class="panel-content empty-panel">
                        </div>
                </div>
                
                <div class="panel orders-overview">
                    <div class="panel-header">
                        <h4>Orders overview</h4>
                        <a href="orders.php" class="view-all">View all</a>
                    </div>
                    <div class="panel-content empty-panel">
                        </div>
                </div>
            </section>
            
        </main>
    </div>
    
</body>
</html>