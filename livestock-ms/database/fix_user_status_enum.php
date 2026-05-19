<?php
require_once __DIR__ . '/../api/config/database.php';
header('Content-Type: text/plain; charset=utf-8');
$db = (new Database())->getConnection();
try {
    $db->exec("ALTER TABLE `user` MODIFY `user_status` ENUM('Pending','Active','Suspended','Inactive','Banned') NOT NULL DEFAULT 'Active'");
    echo "user_status enum updated.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
