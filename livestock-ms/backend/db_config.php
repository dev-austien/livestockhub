<?php
/**
 * Database Configuration - Stable Version
 * Optimized for Livestock Management System
 */

// 1. Connection Details
$host     = "127.0.0.1";  // Use IP instead of 'localhost' for speed
$port     = "3306";       // Default MySQL port (Change to 3307 if 3306 is blocked)
$dbname   = "livestock_db";
$username = "root";
$password = "";           // Default XAMPP password is empty
$charset  = "utf8mb4";    // Supports all characters and emojis

// 2. PDO Error and Performance Settings
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Helps you catch SQL errors easily
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Returns data as an associative array
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Uses real prepared statements for security
];

try {
    // 3. Construct Data Source Name (DSN)
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";
    
    // 4. Create the Connection
    $conn = new PDO($dsn, $username, $password, $options);

    // 5. Global Session Management
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

} catch (PDOException $e) {
    // If connection fails, show a clean error message
    // During production, you should log $e->getMessage() instead of echoing it
    error_log("Connection failed: " . $e->getMessage());
    die("The database is currently unavailable. Please check if MySQL is running in XAMPP.");
}

/**
 * Pro-tip: You can now use $conn in all your files.
 * Example: $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
 */
?>