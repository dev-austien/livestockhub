<?php
// Use the direct IP to skip the slow DNS lookup that 'localhost' often triggers
$host     = "127.0.0.1"; 
$dbname   = "livestock_db";
$username = "root";
$password = "";
$charset  = "utf8mb4";

// Optimized PDO settings
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false, // Better security and performance
];

try {
    // Added charset to the DSN for better character handling
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $conn = new PDO($dsn, $username, $password, $options);

    // Start session if it hasn't started yet
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

} catch(PDOException $e) {
    // In production, log this to a file instead of showing the user
    die("Connection failed: " . $e->getMessage());
}
?>