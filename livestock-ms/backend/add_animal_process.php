<?php
require_once 'db_config.php'; // Same folder, simple link

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_animal'])) {
    try {
        // Prepare the SQL based on your schema
        $sql = "INSERT INTO animals (animal_name, category, breed, gender, dob, weight, health_status, sale_status, location, notes) 
                VALUES (:name, :cat, :breed, :gender, :dob, :weight, :health, :sale, :loc, :notes)";
        
        $stmt = $conn->prepare($sql);

        // execute the query
        $stmt->execute([
            ':name'   => $_POST['animal_name'],
            ':cat'    => $_POST['category'],
            ':breed'  => $_POST['breed'],
            ':gender' => $_POST['gender'],
            ':dob'    => $_POST['dob'],
            ':weight' => $_POST['weight'],
            ':health' => $_POST['health'],
            ':sale'   => $_POST['sale_status'],
            ':loc'    => $_POST['location'],
            ':notes'  => $_POST['notes']
        ]);

        // Success redirect
        header("Location: ../frontend/pages/farmer/mylivestock.php?status=added");
        exit();

    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>