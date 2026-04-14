<?php
require_once __DIR__ . '/../shared/db_config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'];
    $last_name  = $_POST['last_name'];
    $email      = $_POST['email'];
    $password   = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role       = $_POST['role']; // 'Farmer' or 'Buyer'

    try {
        $pdo->beginTransaction();

        // 1. Insert into 'user' table
        $sqlUser = "INSERT INTO user (user_first_name, user_last_name, user_email, password_hash, user_role) 
                    VALUES (?, ?, ?, ?, ?)";
        $stmtUser = $pdo->prepare($sqlUser);
        $stmtUser->execute([$first_name, $last_name, $email, $password, $role]);
        
        $userId = $pdo->lastInsertId();

        // 2. If the user is a Farmer, create their Farmer profile
        if ($role === 'Farmer') {
            $farm_name = $_POST['farm_name'];
            $sqlFarmer = "INSERT INTO farmers (user_id, farm_name) VALUES (?, ?)";
            $stmtFarmer = $pdo->prepare($sqlFarmer);
            $stmtFarmer->execute([$userId, $farm_name]);
        }

        $pdo->commit();
        header("Location: ../frontend/pages/auth/login.php?msg=RegistrationSuccess");
    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: ../../frontend/pages/auth/register.php?error=" . urlencode($e->getMessage()));
    }
}