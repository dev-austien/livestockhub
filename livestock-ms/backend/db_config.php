<?php
$host = "localhost";
$dbname = "livestock_db";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Start session only if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
} catch(PDOException $e) {
    // During production, we'd log this, but for now, let's just die
    die("Connection failed: " . $e->getMessage());
}
// DO NOT ADD A CLOSING PHP TAG HERE