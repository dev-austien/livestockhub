<?php
// Fix 1: Added .php and used __DIR__ for stability
require_once __DIR__ . '/../shared/db_config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Fix 2: Matching login.php <input name="username">
    $login_input = $_POST['username']; 
    $pass        = $_POST['password'];

    // Fix 3: Querying by username
    $stmt = $pdo->prepare("SELECT * FROM user WHERE username = ?");
    $stmt->execute([$login_input]);
    $user = $stmt->fetch();

    if ($user && password_verify($pass, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role']    = $user['user_role'];

        if ($user['user_role'] == 'Farmer') {
            $fStmt = $pdo->prepare("SELECT farmer_id FROM farmers WHERE user_id = ?");
            $fStmt->execute([$user['user_id']]);
            $farmer = $fStmt->fetch();
            $_SESSION['farmer_id'] = $farmer['farmer_id'];
            
            // Fix 4: Corrected redirect path (Up 2 levels)
            header("Location: ../../frontend/pages/farmer/dashboard.php");
        } elseif ($user['user_role'] == 'Admin') {
            header("Location: ../../frontend/pages/admin/dashboard.php");
        } else {
            header("Location: ../../frontend/pages/buyer/dashboard.php");
        }
        exit();
    } else {
        header("Location: ../../frontend/pages/auth/login.php?error=InvalidCredentials");
        exit();
    }
}