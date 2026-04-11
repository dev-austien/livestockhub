<?php
require_once 'db_config.php';

if (isset($_POST['register'])) {
    $fname   = $_POST['first_name'];
    $lname   = $_POST['last_name'];
    $uname   = $_POST['username'];
    $email   = $_POST['email'];
    $phone   = $_POST['phone'];
    $pass    = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    // --- THIS IS INSTRUCTION #3 (The Security Check) ---
    $role = $_POST['role']; 
    
    // If someone tries to force 'admin' via the browser console, 
    // we force them back to 'buyer' or stop the script.
    if ($role === 'admin') {
        die("Error: Unauthorized role selection. Admin accounts must be created manually.");
    }
    // ---------------------------------------------------

    if ($pass !== $confirm) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        exit();
    }

    $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);

    try {
        $conn->beginTransaction();

        // 1. Insert into 'user' table
        $sql = "INSERT INTO user (username, user_email, user_phone_number, password_hash, user_role, user_last_name, user_first_name, user_status) 
                VALUES (:uname, :email, :phone, :pass, :role, :lname, :fname, 'active')";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':uname' => $uname,
            ':email' => $email,
            ':phone' => $phone,
            ':pass'  => $hashed_pass,
            ':role'  => $role, // This is now safe because of the check above
            ':lname' => $lname,
            ':fname' => $fname
        ]);

        $new_user_id = $conn->lastInsertId();

        // 2. Automatically create Farmer Profile if they chose the farmer role
        if ($role === 'farmer') {
            $farm_name = isset($_POST['farm_name']) ? $_POST['farm_name'] : $fname . "'s Farm";
            
            $sqlFarmer = "INSERT INTO farmers (user_id, farm_name, farm_location_brgy, farm_location_city_muni, farm_location_province, farm_location_latitude, farm_location_longitude) 
                          VALUES (:uid, :farm, 'Pending', 'Pending', 'Pending', 0.0, 0.0)";
            
            $stmtFarmer = $conn->prepare($sqlFarmer);
            $stmtFarmer->execute([
                ':uid'  => $new_user_id,
                ':farm' => $farm_name
            ]);
        }

        $conn->commit();
        echo "<script>alert('Registration Successful!'); window.location.href='../frontend/pages/auth/login.php';</script>";

    } catch(PDOException $e) {
        if ($conn->inTransaction()) { $conn->rollBack(); }
        // ... rest of your error handling ...
    }
}