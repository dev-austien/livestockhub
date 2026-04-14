<?php
session_start();
require_once '../../../backend/shared/db_config.php';

// 1. Auth Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Farmer') {
    header("Location: ../auth/login.php");
    exit();
}

$farmer_id = $_SESSION['farmer_id'];

// 2. Fetch All Livestock for this Farmer (READ)
$stmt = $pdo->prepare("
    SELECT 
        l.livestock_id, 
        c.category_name, 
        b.breed_name, 
        l.gender, 
        l.health_status, 
        l.sale_status,
        (SELECT weight FROM livestock_weight WHERE livestock_id = l.livestock_id ORDER BY date_recorded DESC LIMIT 1) as current_weight
    FROM livestock l
    JOIN category c ON l.category_id = c.category_id
    JOIN breeds b ON l.breed_id = b.breed_id
    WHERE l.farmer_id = ?
    ORDER BY l.livestock_id DESC
");
$stmt->execute([$farmer_id]);
$inventory = $stmt->fetchAll();
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AgriHub — My Inventory</title>
    <link rel="stylesheet" href="../../css/agrihub.css">
</head>

<body>

    <?php include '../../css/include.css/nav.php'; ?>

    <div class="ag-page">
        <main class="ag-main">
            <div class="ag-page-header">
                <div class="ag-eyebrow">Inventory Management</div>
                <h1 class="ag-page-title">Manage your <em>livestock.</em></h1>
                <a href="addAnimals.php" class="ag-btn ag-btn-primary" style="margin-top: 10px;">+ Add New Animal</a>
            </div>

            <?php if (isset($_GET['msg'])): ?>
            <div class="ag-tag ok" style="margin-bottom: 20px; width: 100%; padding: 10px; text-align: center;">
                <?= htmlspecialchars($_GET['msg']) ?>
            </div>
            <?php endif; ?>

            <div class="ag-card">
                <div class="ag-card-body">
                    <table class="ag-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Species</th>
                                <th>Breed</th>
                                <th>Weight</th>
                                <th>Health</th>
                                <th>Market Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($inventory)): ?>
                            <?php foreach ($inventory as $row): ?>
                            <tr>
                                <td>#<?= $row['livestock_id'] ?></td>
                                <td><?= htmlspecialchars($row['category_name']) ?></td>
                                <td><?= htmlspecialchars($row['breed_name']) ?></td>
                                <td><?= $row['current_weight'] ?? '0.0' ?> kg</td>
                                <td>
                                    <span class="ag-tag <?= ($row['health_status'] == 'Healthy') ? 'ok' : 'danger' ?>">
                                        <?= $row['health_status'] ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?= $row['sale_status'] ?></strong>
                                </td>
                                <td class="ag-flex ag-gap-sm">
                                    <a href="editAnimal.php?id=<?= $row['livestock_id'] ?>" class="ag-btn ag-btn-ghost"
                                        style="padding: 5px 10px; font-size: 12px;">Edit</a>

                                    <form action="../../../backend/farmer/livestock_ctrl.php" method="POST"
                                        onsubmit="return confirm('Are you sure you want to delete this animal?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="livestock_id" value="<?= $row['livestock_id'] ?>">
                                        <button type="submit" class="ag-btn"
                                            style="background: #ff4d4d; color: white; border: none; padding: 5px 10px; font-size: 12px; cursor: pointer;">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding: 50px;">You haven't added any animals
                                    yet.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

</body>

</html>