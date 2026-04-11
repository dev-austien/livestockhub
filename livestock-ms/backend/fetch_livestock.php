<?php
require_once 'db_config.php';

// Check session
if (!isset($_SESSION['user_id'])) {
    header("Location: ../frontend/pages/auth/login.php");
    exit();
}

try {
    $user_id = $_SESSION['user_id'];

    // 1. Get the farmer_id for this user
    $stmtFarmer = $conn->prepare("SELECT farmer_id FROM farmers WHERE user_id = ?");
    $stmtFarmer->execute([$user_id]);
    $farmer = $stmtFarmer->fetch(PDO::FETCH_ASSOC);

    $livestock_list = [];

    if ($farmer) {
        $farmer_id = $farmer['farmer_id'];

        // 2. Fetch livestock with Category Name and the Latest Weight recorded
        $query = "SELECT l.livestock_id, c.category_name, l.gender, l.health_status, 
                         l.date_of_birth, l.sale_status,
                         (SELECT weight FROM livestock_weight lw 
                          WHERE lw.livestock_id = l.livestock_id 
                          ORDER BY date_recorded DESC LIMIT 1) as current_weight
                  FROM livestock l
                  JOIN category c ON l.category_id = c.category_id
                  WHERE l.farmer_id = :farmer_id
                  ORDER BY l.date_created DESC";

        $stmt = $conn->prepare($query);
        $stmt->execute([':farmer_id' => $farmer_id]);
        $livestock_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Fetch Error: " . $e->getMessage());
    $livestock_list = [];
}