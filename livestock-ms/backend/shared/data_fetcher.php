<?php
require_once 'db_config.php';

function getFarmerLivestock($pdo, $farmer_id) {
    $sql = "SELECT l.*, c.category_name, b.breed_name, loc.location_name 
            FROM livestock l
            JOIN category c ON l.category_id = c.category_id
            JOIN breeds b ON l.breed_id = b.breed_id
            JOIN location loc ON l.location_id = loc.location_id
            WHERE l.farmer_id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$farmer_id]);
    return $stmt->fetchAll();
}

// For Admin to see everything
function getAllLivestock($pdo) {
    $stmt = $pdo->query("SELECT l.*, u.username as owner FROM livestock l JOIN farmers f ON l.farmer_id = f.farmer_id JOIN user u ON f.user_id = u.user_id");
    return $stmt->fetchAll();
}
?>