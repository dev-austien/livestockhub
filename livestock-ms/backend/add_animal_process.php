<?php
require_once 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_animal'])) {
    
    if (!isset($_SESSION['user_id'])) {
        die("Error: Session expired.");
    }

    try {
        $conn->beginTransaction();
        $user_id = $_SESSION['user_id'];

        // Get the farmer_id
        $stmtFarmer = $conn->prepare("SELECT farmer_id FROM farmers WHERE user_id = ?");
        $stmtFarmer->execute([$user_id]);
        $farmer = $stmtFarmer->fetch(PDO::FETCH_ASSOC);

        if (!$farmer) { throw new Exception("Farmer profile not found."); }
        $farmer_id = $farmer['farmer_id'];

        // Get the specific Category ID from the form dropdown
        $category_id = $_POST['category_id']; 

        // Insert into livestock table
        $sqlLivestock = "INSERT INTO livestock (farmer_id, category_id, gender, health_status, date_of_birth, sale_status) 
                         VALUES (:farmer_id, :cat_id, :gender, :health, :dob, :sale)";
        
        $stmt1 = $conn->prepare($sqlLivestock);
        $stmt1->execute([
            ':farmer_id' => $farmer_id,
            ':cat_id'    => $category_id, // This will now be 1, 2, or 3
            ':gender'    => $_POST['gender'], 
            ':health'    => $_POST['health_status'], 
            ':dob'       => !empty($_POST['dob']) ? $_POST['dob'] : null,
            ':sale'      => $_POST['sale_status']
        ]);

        $newLivestockId = $conn->lastInsertId();

        // Insert weight into the separate weight table
        if (!empty($_POST['weight'])) {
            $stmt2 = $conn->prepare("INSERT INTO livestock_weight (livestock_id, weight) VALUES (?, ?)");
            $stmt2->execute([$newLivestockId, $_POST['weight']]);
        }

        $conn->commit();
        header("Location: ../frontend/pages/farmer/mylivestock.php?status=success");
        exit();

    } catch (Exception $e) {
        if ($conn->inTransaction()) { $conn->rollBack(); }
        die("Database Error: " . $e->getMessage());
    }
}