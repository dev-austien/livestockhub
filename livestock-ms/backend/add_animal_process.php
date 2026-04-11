<?php
// Include your config which should have session_start() based on your db_config.php
require_once 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_animal'])) {
    
    // DEBUG: Uncomment the line below if you keep getting errors to see what is in your session
    // print_r($_SESSION); die();

    if (!isset($_SESSION['user_id'])) {
        die("Error: No user session found. Please log in again.");
    }

    try {
        $conn->beginTransaction();
        $user_id = $_SESSION['user_id'];

        // 1. Get the farmer_id that belongs to this user_id
        $stmtFarmer = $conn->prepare("SELECT farmer_id FROM farmers WHERE user_id = :uid");
        $stmtFarmer->execute([':uid' => $user_id]);
        $farmer = $stmtFarmer->fetch(PDO::FETCH_ASSOC);

        if (!$farmer) {
            throw new Exception("No Farmer profile exists for User ID: " . $user_id);
        }

        $farmer_id = $farmer['farmer_id'];

        // 2. Category Handling (Ensuring a category exists so FK doesn't fail)
        $stmtCat = $conn->query("SELECT category_id FROM category LIMIT 1");
        $category = $stmtCat->fetch(PDO::FETCH_ASSOC);
        
        if (!$category) {
            $conn->query("INSERT INTO category (category_name) VALUES ('General')");
            $category_id = $conn->lastInsertId();
        } else {
            $category_id = $category['category_id'];
        }

        // 3. Insert into 'livestock'
        // Using your exact DB columns: farmer_id, category_id, gender, health_status, date_of_birth, sale_status
        $sqlLivestock = "INSERT INTO livestock (farmer_id, category_id, gender, health_status, date_of_birth, sale_status) 
                         VALUES (:farmer_id, :cat_id, :gender, :health, :dob, :sale)";
        
        $stmt1 = $conn->prepare($sqlLivestock);
        $stmt1->execute([
            ':farmer_id' => $farmer_id,
            ':cat_id'    => $category_id,
            ':gender'    => $_POST['gender'], 
            ':health'    => $_POST['health_status'], 
            ':dob'       => !empty($_POST['dob']) ? $_POST['dob'] : null,
            ':sale'      => $_POST['sale_status']
        ]);

        $newLivestockId = $conn->lastInsertId();

        // 4. Insert into 'livestock_weight'
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