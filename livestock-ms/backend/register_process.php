<?php
require_once 'db_config.php';

if (isset($_POST['register'])) {
    $fname    = $_POST['first_name'];
    $lname    = $_POST['last_name'];
    $uname    = $_POST['username'];
    $email    = $_POST['email'];
    $phone    = $_POST['phone'];
    $role     = $_POST['role']; // admin, farmer, buyer
    $pass     = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if ($pass !== $confirm) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        exit();
    }

    $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);

    try {
        $sql = "INSERT INTO user (username, user_email, user_phone_number, password_hash, user_role, user_last_name, user_first_name, user_status) 
                VALUES (:uname, :email, :phone, :pass, :role, :lname, :fname, 'active')";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':uname' => $uname,
            ':email' => $email,
            ':phone' => $phone,
            ':pass'  => $hashed_pass,
            ':role'  => $role,
            ':lname' => $lname,
            ':fname' => $fname
        ]);

        echo "<script>alert('Registration Successful!'); window.location.href='../frontend/pages/auth/login.php';</script>";

    } catch(PDOException $e) {
        if ($e->getCode() == 23000) {
            echo "<script>alert('Username or Email already taken!'); window.history.back();</script>";
        } else {
            echo "Error: " . $e->getMessage();
        }
    }
}
?>