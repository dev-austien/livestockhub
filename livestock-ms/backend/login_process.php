<?php
ob_start(); // Buffer output to prevent "Headers already sent" errors
require_once 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    try {
        $stmt = $conn->prepare("SELECT * FROM user WHERE username = :uname LIMIT 1");
        $stmt->execute([':uname' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Set Sessions
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['user_role'];
            $_SESSION['username'] = $user['username'];

            // Determine redirect path
            $role = $user['user_role'];
            $path = "";

            if ($role === 'admin') {
                $path = "../frontend/pages/admin/dashboard.php";
            } elseif ($role === 'farmer') {
                $path = "../frontend/pages/farmer/dashboard.php";
            } elseif ($role === 'buyer') {
                $path = "../frontend/pages/buyer/dashboard.php";
            }

            if ($path !== "") {
                // Try PHP Redirect first
                header("Location: " . $path);
                // Backup JavaScript Redirect if header is blocked
                echo "<script>window.location.href='$path';</script>";
                exit();
            }

        } else {
            echo "<script>alert('Invalid username or password'); window.history.back();</script>";
        }
    } catch (PDOException $e) {
        die("System Error: " . $e->getMessage());
    }
} else {
    header("Location: ../frontend/pages/auth/login.php");
}
ob_end_flush();