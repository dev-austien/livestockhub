<?php
require_once 'db_config.php'; // This file already has session_start() based on our previous step

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        // 1. Search for the user in the database
        $stmt = $conn->prepare("SELECT * FROM user WHERE username = :uname LIMIT 1");
        $stmt->execute([':uname' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Verify if user exists and password matches the hash
        if ($user && password_verify($password, $user['password_hash'])) {
            
            // 3. Store user info in the Session
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['user_role'] = $user['user_role'];
            $_SESSION['full_name'] = $user['user_first_name'] . " " . $user['user_last_name'];

            // 4. Role-Based Redirect
            if ($user['user_role'] === 'admin') {
                header("Location: ../frontend/pages/admin/dashboard.php");
            } elseif ($user['user_role'] === 'farmer') {
                header("Location: ../frontend/pages/farmer/dashboard.php");
            } elseif ($user['user_role'] === 'buyer') {
                header("Location: ../frontend/pages/buyer/dashboard.php");
            }
            exit(); // Always exit after a header redirect

        } else {
            echo "<script>alert('Invalid username or password!'); window.history.back();</script>";
        }
    } catch(PDOException $e) {
        echo "Login Error: " . $e->getMessage();
    }
}