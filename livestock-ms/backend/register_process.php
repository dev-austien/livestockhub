<?php
require_once 'db_config.php';

if (isset($_POST['register'])) {
    $fname   = $_POST['first_name'];
    $lname   = $_POST['last_name'];
    $uname   = $_POST['username'];
    $email   = $_POST['email'];
    $phone   = $_POST['phone'];
    $role    = $_POST['role']; // admin, farmer, buyer
    $pass    = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    
    // Optional: Get farm name from form if it exists, otherwise use a default
    $farm_name = isset($_POST['farm_name']) ? $_POST['farm_name'] : $fname . "'s Farm";

    if ($pass !== $confirm) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        exit();
    }

    $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);

    try {
        // Start Transaction
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
            ':role'  => $role,
            ':lname' => $lname,
            ':fname' => $fname
        ]);

        // 2. Get the new User's ID
        $new_user_id = $conn->lastInsertId();

        // 3. If the role is 'farmer', create the Farmer profile automatically
        if ($role === 'farmer') {
            $sqlFarmer = "INSERT INTO farmers (user_id, farm_name, farm_location_brgy, farm_location_city_muni, farm_location_province, farm_location_latitude, farm_location_longitude) 
                          VALUES (:uid, :farm, 'Pending', 'Pending', 'Pending', 0.0, 0.0)";
            
            $stmtFarmer = $conn->prepare($sqlFarmer);
            $stmtFarmer->execute([
                ':uid'  => $new_user_id,
                ':farm' => $farm_name
            ]);
        }

        // Commit all changes
        $conn->commit();

        echo "<script>alert('Registration Successful!'); window.location.href='../frontend/pages/auth/login.php';</script>";

    } catch(PDOException $e) {
        // Rollback if anything goes wrong
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        if ($e->getCode() == 23000) {
            echo "<script>alert('Username or Email already taken!'); window.history.back();</script>";
        } else {
            echo "Error: " . $e->getMessage();
        }
    }
}
?>