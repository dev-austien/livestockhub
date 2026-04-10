<?php
require_once 'db_config.php';

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        $stmt = $conn->prepare("SELECT * FROM user WHERE username = :uname");
        $stmt->execute([':uname' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Store user data in Session
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['role']      = $user['user_role'];
            $_SESSION['full_name'] = $user['user_first_name'] . " " . $user['user_last_name'];

            // Redirect based on role
            if ($user['user_role'] == 'admin') {
                header("Location: ../frontend/pages/admin/dashboard.php");
            } elseif ($user['user_role'] == 'farmer') {
                header("Location: ../frontend/pages/farmer/dashboard.php");
            } else {
                header("Location: ../frontend/pages/buyer/dashboard.php");
            }
            exit();
        } else {
            echo "<script>alert('Invalid username or password!'); window.history.back();</script>";
        }
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>