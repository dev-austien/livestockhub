<?php 
// Include the logic from backend
require_once '../../../backend/fetch_livestock.php'; 
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AgriHub - My Livestock</title>

    <link rel="stylesheet" href="../../css/main.css">
    <link rel="stylesheet" href="../../css/sidebar.css">
    <style>
    .data-table {
        width: 95%;
        margin: 20px auto;
        border-collapse: collapse;
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
    }

    .data-table th {
        background: #2d6a4f;
        color: white;
        padding: 12px;
        text-align: left;
    }

    .data-table td {
        padding: 12px;
        border-bottom: 1px solid #eee;
    }

    .status-pill {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
    }

    .available {
        background: #d8f3dc;
        color: #1b4332;
    }

    .sold {
        background: #ffcccc;
        color: #a4161a;
    }

    .empty-state {
        text-align: center;
        padding: 40px;
        color: #666;
    }
    </style>
</head>

<body>
    <div class="full-screen">
        <aside class="left-wing">
            <div class="logo-panel">
                <div class="logo">AgriHub</div>
                <div class="panel-description">Farmers Portal</div>
            </div>

            <nav class="side-bar">
                <ul>
                    <li><a href="dashboard.php" class="side-link">Dashboard</a></li>
                    <li><a href="mylivestock.php" class="side-link">My Livestock</a></li>
                    <li><a href="addAnimals.php" class="side-link">Add Animals</a></li>
                    <li><a href="weightLog.php" class="side-link">Weight Log</a></li>
                    <li><a href="myFarm.php" class="side-link">My Farm</a></li>
                    <li><a href="orderRecieved.php" class="side-link">Order Received</a></li>
                    <li><a href="transaction.php" class="side-link">Transactions</a></li>
                </ul>
            </nav>

            <div class="basic-profile">
                <div class="pfp">AJ</div>
                <div>
                    <div class="username"><?php echo htmlspecialchars($_SESSION['user_first_name'] ?? 'Austien'); ?>
                    </div>
                    <div class="designation">Farmer</div>
                </div>
            </div>
        </aside>

        <main class="main-body">
            <header class="content-title">
                <div class="panel-name">My Livestock</div>
                <div class="des-pfp">
                    <div class="designation-icon">Farmer</div>
                    <div class="pfp-2">AJ</div>
                </div>
            </header>

            <section class="table-container">
                <?php if (!empty($livestock_list)): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Category</th>
                            <th>Gender</th>
                            <th>Latest Weight</th>
                            <th>Health</th>
                            <th>Sale Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($livestock_list as $animal): ?>
                        <tr>
                            <td>#<?php echo $animal['livestock_id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($animal['category_name']); ?></strong></td>
                            <td><?php echo ucfirst($animal['gender']); ?></td>
                            <td><?php echo $animal['current_weight'] ?? '0.00'; ?> kg</td>
                            <td><?php echo htmlspecialchars($animal['health_status'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="status-pill <?php echo $animal['sale_status']; ?>">
                                    <?php echo ucfirst($animal['sale_status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="weightLog.php?id=<?php echo $animal['livestock_id']; ?>"
                                    title="View Weight Log">⚖️</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <h3>No livestock added yet.</h3>
                    <p>Start by adding your animals in the <a href="addAnimals.php">Add Animals</a> tab.</p>
                </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>

</html>