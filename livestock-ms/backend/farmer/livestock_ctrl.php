<?php
session_start();

// 1. SECURITY: Ensure only logged-in Farmers can access this file
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Farmer') {
    header("Location: ../../frontend/pages/auth/login.php?error=Unauthorized");
    exit();
}

require_once '../shared/db_config.php';

// Get the farmer's ID from the session
$farmer_id = $_SESSION['farmer_id'];

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Determine which action to perform (add, update, or delete)
    $action = $_POST['action'] ?? '';

    try {
        // =========================================================
        // CREATE: Add New Livestock
        // =========================================================
        if ($action === 'add') {
            $category_id = $_POST['category_id'];
            $breed_id    = $_POST['breed_id'];
            $location_id = $_POST['location_id']; // The specific pen/area
            $gender      = $_POST['gender'];
            $health      = $_POST['health_status'];
            $dob         = $_POST['date_of_birth'];
            $weight      = $_POST['initial_weight']; 

            $pdo->beginTransaction();

            // Insert animal
            $sql = "INSERT INTO livestock (farmer_id, location_id, category_id, breed_id, gender, health_status, date_of_birth, sale_status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Available')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$farmer_id, $location_id, $category_id, $breed_id, $gender, $health, $dob]);
            
            $livestock_id = $pdo->lastInsertId();

            // Insert initial weight log
            $sqlWeight = "INSERT INTO livestock_weight (livestock_id, weight) VALUES (?, ?)";
            $stmtWeight = $pdo->prepare($sqlWeight);
            $stmtWeight->execute([$livestock_id, $weight]);

            $pdo->commit();
            header("Location: ../../frontend/pages/farmer/mylivestock.php?msg=AnimalAdded");
            exit();
        }

        // =========================================================
        // UPDATE: Edit Health or Sale Status
        // =========================================================
        elseif ($action === 'update') {
            $livestock_id  = $_POST['livestock_id'];
            $health_status = $_POST['health_status'];
            $sale_status   = $_POST['sale_status'];

            // Security: "AND farmer_id = ?" ensures a farmer can only edit their OWN animals
            $sql = "UPDATE livestock SET health_status = ?, sale_status = ? 
                    WHERE livestock_id = ? AND farmer_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$health_status, $sale_status, $livestock_id, $farmer_id]);

            header("Location: ../../frontend/pages/farmer/mylivestock.php?msg=AnimalUpdated");
            exit();
        }

        // =========================================================
        // DELETE: Remove Livestock completely
        // =========================================================
        elseif ($action === 'delete') {
            $livestock_id = $_POST['livestock_id'];

            // Security: "AND farmer_id = ?" ensures they can only delete their OWN animals
            // Note: Weight logs will delete automatically because we used ON DELETE CASCADE in the database
            $sql = "DELETE FROM livestock WHERE livestock_id = ? AND farmer_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$livestock_id, $farmer_id]);

            header("Location: ../../frontend/pages/farmer/mylivestock.php?msg=AnimalDeleted");
            exit();
        }

        // If action doesn't match anything
        else {
            header("Location: ../../frontend/pages/farmer/mylivestock.php?error=InvalidAction");
            exit();
        }

    } catch (Exception $e) {
        // If anything fails, rollback the transaction and send the error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // In production, you might not want to show the raw SQL error, but it's good for debugging
        header("Location: ../../frontend/pages/farmer/mylivestock.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    // If someone tries to visit this URL directly without submitting a form
    header("Location: ../../frontend/pages/farmer/mylivestock.php");
    exit();
}