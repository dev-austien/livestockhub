<?php
session_start();
require_once 'db_config.php';

echo "Step 1: File reached<br>";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo "Step 2: POST method detected<br>";
    
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM user WHERE username = :uname");
    $stmt->execute([':uname' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "Step 3: User found in database<br>";
        
        // IMPORTANT: Check if you are using password_verify or plain text
        if (password_verify($password, $user['password_hash'])) {
            echo "Step 4: Password matches!<br>";
            
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_role'] = $user['user_role'];
            $_SESSION['username'] = $user['username'];

            $role = $user['user_role'];
            echo "Step 5: Role is " . $role . "<br>";

            // The actual redirect
            if ($role === 'admin') {
                echo "Step 6: Redirecting to Admin Dashboard...<br>";
                header("Location: ../frontend/pages/admin/dashboard.php");
                echo "<script>window.location.href='../frontend/pages/admin/dashboard.php';</script>";
            } else {
                echo "Step 6: Role is not admin, checking others...<br>";
            }
            exit();

        } else {
            echo "STOP: Password does not match. <br>";
            echo "Input password: " . $password . "<br>";
            echo "DB Hash: " . $user['password_hash'];
        }
    } else {
        echo "STOP: No user found with username: " . $username;
    }
} else {
    echo "STOP: Not a POST request.";
}