<?php
session_start();
include('../../../backend/db_config.php'); // Ensure this path is correct

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // 2. Fetch Recent Livestock
    // We JOIN with farms to ensure we only get livestock belonging to THIS farmer
    $query = "SELECT l.animal_id, l.species, l.breed_name, l.health_status, l.date_registered 
              FROM livestock l
              JOIN farms f ON l.farm_id = f.farm_id
              WHERE f.farmer_id = :user_id
              ORDER BY l.date_registered DESC 
              LIMIT 5";

    $stmt = $pdo->prepare($query);
    $stmt->execute([':user_id' => $user_id]);
    $recent_livestock = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<div class="card">
    <div class="card-header">
        <h3>Recent Livestock</h3>
    </div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Animal ID</th>
                    <th>Species</th>
                    <th>Breed</th>
                    <th>Health</th>
                    <th>Registered Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($recent_livestock) > 0): ?>
                <?php foreach ($recent_livestock as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['animal_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['species']); ?></td>
                    <td><?php echo htmlspecialchars($row['breed_name']); ?></td>
                    <td>
                        <span
                            class="badge <?php echo ($row['health_status'] == 'Healthy') ? 'bg-success' : 'bg-warning'; ?>">
                            <?php echo htmlspecialchars($row['health_status']); ?>
                        </span>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($row['date_registered'])); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align:center;">No livestock registered yet.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>