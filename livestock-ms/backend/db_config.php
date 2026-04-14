<?php
$host = "localhost";
$db   = "livestuchub_db"; // check this name in phpMyAdmin
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB ERROR: " . $e->getMessage());
}
?>