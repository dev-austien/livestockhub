<?php
// Fix 1: Correct path to config
require_once __DIR__ . '/../shared/db_config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Fix 2: You missed 'username' in your PHP variables
    $username   = $_POST['username']; 
    $first_name = $_POST['first_name'];
    $last_name  = $_POST['last_name'];
    $email      = $_POST['email'];
    $password   = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role       = $_POST['role']; 

    try {
        $pdo->beginTransaction();

        // Fix 3: Added username to the SQL insert
        $sqlUser = "INSERT INTO user (username, user_first_name, user_last_name, user_email, password_hash, user_role) 
                    VALUES (?, ?, ?, ?, ?, ?)";
        $stmtUser = $pdo->prepare($sqlUser);
        $stmtUser->execute([$username, $first_name, $last_name, $email, $password, $role]);
        
        $userId = $pdo->lastInsertId();

        if ($role === 'farmer') { // Match the lowercase value from your <select>
            $farm_name = $_POST['farm_name'];
            $sqlFarmer = "INSERT INTO farmers (user_id, farm_name) VALUES (?, ?)";
            $stmtFarmer = $pdo->prepare($sqlFarmer);
            $stmtFarmer->execute([$userId, $farm_name]);
        }

        $pdo->commit();
        
        // Fix 4: Go up TWO levels (../../) to reach root, then enter frontend
        header("Location: ../../frontend/pages/auth/login.php?msg=RegistrationSuccess");
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // Fix 5: Path correction for error redirect
        header("Location: ../../frontend/pages/auth/register.php?error=" . urlencode($e->getMessage()));
        exit();
    }
}