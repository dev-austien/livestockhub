<?php
/**
 * One-time admin seed. Visit once in browser, then delete or restrict access.
 * Login email: admingroup5@gmail.com / password: group5
 */
require_once __DIR__ . '/../api/config/database.php';

header('Content-Type: text/plain; charset=utf-8');

$db    = (new Database())->getConnection();
$hash  = password_hash('group5', PASSWORD_BCRYPT);
$email = 'admingroup5@gmail.com';
$user  = 'admingroup5';

$stmt = $db->prepare(
    "SELECT user_id FROM user WHERE user_email IN (?, ?) OR username = ?"
);
$stmt->execute([$email, 'admingroup5', $user]);

if ($stmt->fetch()) {
    $db->prepare(
        "UPDATE user SET user_email = ?, username = ?, password_hash = ?,
         user_role = 'Admin', user_status = 'Active', ban_type = 'none'
         WHERE user_email IN (?, ?) OR username = ?"
    )->execute([$email, $user, $hash, $email, 'admingroup5', $user]);
    echo "Admin account updated.\nLogin: {$email} / group5\n";
} else {
    $db->prepare(
        "INSERT INTO user (username, user_email, password_hash, user_role, user_first_name, user_last_name, user_status, ban_type)
         VALUES (?, ?, ?, 'Admin', 'Admin', 'Group5', 'Active', 'none')"
    )->execute([$user, $email, $hash]);
    echo "Admin account created.\nLogin: {$email} / group5\n";
}

echo "Done. Remove or protect this file in production.\n";
