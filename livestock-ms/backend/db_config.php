<?php
$host = "localhost";
$dbname = "livestock_db"; // Make sure to create this name in phpMyAdmin
$username = "root";       // Default XAMPP username
$password = "";           // Default XAMPP password is empty

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set error mode to exception to see mistakes clearly
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>