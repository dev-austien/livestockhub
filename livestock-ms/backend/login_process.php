<?php
session_start();
require_once 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    try {
        $stmt = $conn->prepare("SELECT * FROM user WHERE username = :uname LIMIT 1");
        $stmt->execute([':uname' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Set session data
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_role'] = strtolower($user['user_role']);
            $_SESSION['username'] = $user['username'];

            $role = $_SESSION['user_role'];

            // Map roles to their specific dashboard paths
            $paths = [
                'admin'  => '../frontend/pages/admin/dashboard.php',
                'farmer' => '../frontend/pages/farmer/dashboard.php',
                'buyer'  => '../frontend/pages/buyer/dashboard.php'
            ];

            if (array_key_exists($role, $paths)) {
                $destination = $paths[$role];
                // Try PHP redirect, then JS fallback
                header("Location: " . $destination);
                echo "<script>window.location.href='$destination';</script>";
                exit();
            } else {
                echo "<script>alert('Role not recognized: $role'); window.history.back();</script>";
            }
        } else {
            echo "<script>alert('Invalid username or password'); window.history.back();</script>";
        }
    } catch (PDOException $e) {
        die("System Error: " . $e->getMessage());
    }
}