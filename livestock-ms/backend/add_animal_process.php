<?php
require_once 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_animal'])) {
    try {
        // Start a transaction so both tables update or neither does
        $conn->beginTransaction();

        // 1. Insert into 'livestock' table
        // Note: I'm using fixed IDs for category/breed/farmer for now
        // In a real app, you'd get these from the session or dropdowns
        $sqlLivestock = "INSERT INTO livestock (farmer_id, category_id, breed_id, gender, health_status, date_of_birth, sale_status) 
                         VALUES (:farmer_id, :cat_id, :breed_id, :gender, :health, :dob, :sale)";
        
        $stmt1 = $conn->prepare($sqlLivestock);

        // For this example, we assume farmer_id 1, category 1, breed 1 
        // You should eventually change these to dynamic IDs
        $stmt1->execute([
            ':farmer_id' => 1, 
            ':cat_id'    => 1, 
            ':breed_id'  => 1,
            ':gender'    => $_POST['gender'],
            ':health'    => $_POST['health'],
            ':dob'       => $_POST['dob'],
            ':sale'      => $_POST['sale_status']
        ]);

        // Get the ID of the livestock we just created
        $newLivestockId = $conn->lastInsertId();

        // 2. Insert into 'livestock_weight' table
        $sqlWeight = "INSERT INTO livestock_weight (livestock_id, weight) 
                      VALUES (:livestock_id, :weight)";
        
        $stmt2 = $conn->prepare($sqlWeight);
        $stmt2->execute([
            ':livestock_id' => $newLivestockId,
            ':weight'       => $_POST['weight']
        ]);

        // Commit the changes
        $conn->commit();

        header("Location: ../frontend/pages/farmer/mylivestock.php?status=success");
        exit();

    } catch (PDOException $e) {
        $conn->rollBack();
        die("Database Error: " . $e->getMessage());
    }
}
?>