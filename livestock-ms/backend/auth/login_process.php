<?php
require_once 'backend/shared/db_config';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $pass  = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM user WHERE user_email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($pass, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role']    = $user['user_role'];

        // If user is a farmer, fetch their farmer_id for the livestock logic
        if ($user['user_role'] == 'Farmer') {
            $fStmt = $pdo->prepare("SELECT farmer_id FROM farmers WHERE user_id = ?");
            $fStmt->execute([$user['user_id']]);
            $farmer = $fStmt->fetch();
            $_SESSION['farmer_id'] = $farmer['farmer_id'];
            header("Location: ../frontend/pages/farmer/dashboard.php");
        } elseif ($user['user_role'] == 'Admin') {
            header("Location: ../frontend/pages/admin/dashboard.php");
        } else {
            header("Location: ../frontend/pages/buyer/dashboard.php");
        }
    } else {
        header("Location: ../frontend/pages/auth/login.php?error=InvalidCredentials");
    }
}