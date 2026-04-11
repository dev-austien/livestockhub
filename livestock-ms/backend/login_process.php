<?php
session_start();
require_once 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM user WHERE username = :uname LIMIT 1");
    $stmt->execute([':uname' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        // Set Sessions
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_role'] = strtolower($user['user_role']);
        $_SESSION['username'] = $user['username'];

        $role = $_SESSION['user_role'];

        // DIRECT JAVASCRIPT REDIRECT (This works when header() fails)
        if ($role === 'admin') {
            echo "<script>window.location.replace('../frontend/pages/admin/dashboard.php');</script>";
        } elseif ($role === 'farmer') {
            echo "<script>window.location.replace('../frontend/pages/farmer/dashboard.php');</script>";
        } elseif ($role === 'buyer') {
            echo "<script>window.location.replace('../frontend/pages/buyer/dashboard.php');</script>";
        }
        exit();
    } else {
        echo "<script>alert('Login Failed'); window.location.href='../frontend/pages/auth/login.php';</script>";
    }
}