<?php
require_once 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_animal'])) {
    
    if (!isset($_SESSION['user_id'])) {
        die("Error: Please log in again.");
    }

    try {
        $conn->beginTransaction();
        $user_id = $_SESSION['user_id'];

        // 1. Get the Farmer ID
        $stmtFarmer = $conn->prepare("SELECT farmer_id FROM farmers WHERE user_id = :uid");
        $stmtFarmer->execute([':uid' => $user_id]);
        $farmer = $stmtFarmer->fetch(PDO::FETCH_ASSOC);

        if (!$farmer) {
            throw new Exception("Farmer profile not found.");
        }

        $farmer_id = $farmer['farmer_id'];
        
        // 2. Get Category ID from the Form
        $category_id = $_POST['category_id']; 

        // 3. Insert into 'livestock'
        $sqlLivestock = "INSERT INTO livestock (farmer_id, category_id, gender, health_status, date_of_birth, sale_status) 
                         VALUES (:farmer_id, :cat_id, :gender, :health, :dob, :sale)";
        
        $stmt1 = $conn->prepare($sqlLivestock);
        $stmt1->execute([
            ':farmer_id' => $farmer_id,
            ':cat_id'    => $category_id, // Now uses 1, 2, or 3 based on selection
            ':gender'    => $_POST['gender'], 
            ':health'    => $_POST['health_status'], 
            ':dob'       => !empty($_POST['dob']) ? $_POST['dob'] : null,
            ':sale'      => $_POST['sale_status']
        ]);

        $newLivestockId = $conn->lastInsertId();

        // 4. Insert Weight
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
        if ($conn->inTransaction()) { $conn->rollBack(); }
        die("Database Error: " . $e->getMessage());
    }
}