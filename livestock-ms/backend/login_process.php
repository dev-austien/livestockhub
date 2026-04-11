<?php
die("Backend is reached!"); 
require_once 'db_config.php';

// 1. Debugging - Show all errors
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2. Database Connection
require_once 'db_config.php'; 

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        $stmt = $conn->prepare("SELECT * FROM user WHERE username = :uname");
        $stmt->execute([':uname' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Verify Password
            if (password_verify($password, $user['password_hash'])) {
                
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['user_role'];

                // Redirect paths based on your structure
                $role = $user['user_role'];
                if ($role === 'admin') {
                    header("Location: ../frontend/pages/admin/dashboard.php");
                } elseif ($role === 'farmer') {
                    header("Location: ../frontend/pages/farmer/dashboard.php");
                } elseif ($role === 'buyer') {
                    header("Location: ../frontend/pages/buyer/dashboard.php");
                } else {
                    echo "Role not recognized: " . $role;
                }
                exit();

            } else {
                echo "Invalid password.";
            }
        } else {
            echo "User not found.";
        }
    } catch (PDOException $e) {
        echo "Database Error: " . $e->getMessage();
    }
} else {
    echo "Form not submitted correctly. Make sure your button has name='login'";
}
?>