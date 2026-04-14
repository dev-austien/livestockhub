<?php
// 1. Fix the path: Go up one level to backend, then into shared
require_once __DIR__ . '/../shared/db_config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 2. Variable Mismatch: Your login.php uses name="username"
    // If you want to login via Username, change the query below.
    // If you want to login via Email, change login.php input name to "email".
    $login_input = $_POST['username']; 
    $pass        = $_POST['password'];

    // 3. Query: Changed user_email to username to match your login.php form
    $stmt = $pdo->prepare("SELECT * FROM user WHERE username = ?");
    $stmt->execute([$login_input]);
    $user = $stmt->fetch();

    if ($user && password_verify($pass, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role']    = $user['user_role'];

        // 4. Redirect paths: Go up TWO levels to livestock-ms, then into frontend
        if ($user['user_role'] == 'Farmer') {
            $fStmt = $pdo->prepare("SELECT farmer_id FROM farmers WHERE user_id = ?");
            $fStmt->execute([$user['user_id']]);
            $farmer = $fStmt->fetch();
            $_SESSION['farmer_id'] = $farmer['farmer_id'];
            
            header("Location: ../../frontend/pages/farmer/dashboard.php");
        } elseif ($user['user_role'] == 'Admin') {
            header("Location: ../../frontend/pages/admin/dashboard.php");
        } else {
            header("Location: ../../frontend/pages/buyer/dashboard.php");
        }
        exit(); // Always exit after a header redirect
    } else {
        header("Location: ../../frontend/pages/auth/login.php?error=InvalidCredentials");
        exit();
    }
}