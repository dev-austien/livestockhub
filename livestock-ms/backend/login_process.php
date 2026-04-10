<?php
// 1. Enable Error Reporting (Remove this once everything works)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2. Database Connection
require_once 'db_config.php'; 

// 3. Check if the form was actually submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        die("Please fill in both fields.");
    }

    try {
        // Search for the user using the exact column name from your ERD
        $stmt = $conn->prepare("SELECT * FROM user WHERE username = :uname LIMIT 1");
        $stmt->execute([':uname' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Check the password against the hash in the DB
            if (password_verify($password, $user['password_hash'])) {
                
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['user_role'];
                $_SESSION['username'] = $user['username'];

                // Redirect logic based on your folder structure
                $role = $user['user_role'];
                if ($role === 'admin') {
                    header("Location: ../frontend/pages/admin/dashboard.php");
                } elseif ($role === 'farmer') {
                    header("Location: ../frontend/pages/farmer/dashboard.php");
                } elseif ($role === 'buyer') {
                    header("Location: ../frontend/pages/buyer/dashboard.php");
                } else {
                    die("Unknown user role: " . htmlspecialchars($role));
                }
                exit();

            } else {
                echo "<script>alert('Incorrect password.'); window.history.back();</script>";
            }
        } else {
            echo "<script>alert('User not found.'); window.history.back();</script>";
        }
    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
} else {
    // If someone tries to access this file directly via URL
    header("Location: ../frontend/pages/auth/login.php");
    exit();
}