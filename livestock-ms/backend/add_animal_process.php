<?php
require_once 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_animal'])) {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        die("Error: You must be logged in to add livestock.");
    }

    try {
        $conn->beginTransaction();

        // 1. GET THE FARMER_ID for the current logged-in user
        $user_id = $_SESSION['user_id'];
        $stmtFarmer = $conn->prepare("SELECT farmer_id FROM farmers WHERE user_id = ?");
        $stmtFarmer->execute([$user_id]);
        $farmer = $stmtFarmer->fetch(PDO::FETCH_ASSOC);

        if (!$farmer) {
            throw new Exception("Farmer profile not found for this user. Please complete your farm profile first.");
        }

        $farmer_id = $farmer['farmer_id'];

        // 2. INSERT INTO 'livestock' 
        // We use null for category and breed if they aren't handled yet to avoid FK errors there too
        $sqlLivestock = "INSERT INTO livestock (farmer_id, category_id, breed_id, gender, health_status, date_of_birth, sale_status) 
                         VALUES (:farmer_id, :cat_id, :breed_id, :gender, :health, :dob, :sale)";
        
        $stmt1 = $conn->prepare($sqlLivestock);
        $stmt1->execute([
            ':farmer_id' => $farmer_id,
            ':cat_id'    => 1, // Ensure category ID 1 exists in your 'category' table!
            ':breed_id'  => null, 
            ':gender'    => $_POST['gender'],
            ':health'    => $_POST['health'],
            ':dob'       => !empty($_POST['dob']) ? $_POST['dob'] : null,
            ':sale'      => $_POST['sale_status']
        ]);

        $newLivestockId = $conn->lastInsertId();

        // 3. INSERT INTO 'livestock_weight'
        if (!empty($_POST['weight'])) {
            $sqlWeight = "INSERT INTO livestock_weight (livestock_id, weight) 
                          VALUES (:livestock_id, :weight)";
            $stmt2 = $conn->prepare($sqlWeight);
            $stmt2->execute([
                ':livestock_id' => $newLivestockId,
                ':weight'       => $_POST['weight']
            ]);
        }

        $conn->commit();
        header("Location: ../frontend/pages/farmer/mylivestock.php?status=success");
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        die("Database Error: " . $e->getMessage());
    }
}