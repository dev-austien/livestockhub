<?php
/**
 * Run Phase 1-4 migration. Visit once in browser.
 */
require_once __DIR__ . '/../api/config/database.php';

header('Content-Type: text/plain; charset=utf-8');

$db = (new Database())->getConnection();
$sql = file_get_contents(__DIR__ . '/migration_phase1_4.sql');

$statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));
$ok = 0;
$skip = 0;

foreach ($statements as $stmt) {
    if ($stmt === '' || str_starts_with($stmt, '--')) {
        continue;
    }
    try {
        $db->exec($stmt);
        $ok++;
        echo "OK: " . substr(str_replace("\n", ' ', $stmt), 0, 80) . "...\n";
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate column') || str_contains($e->getMessage(), 'already exists')) {
            $skip++;
            echo "SKIP (exists): " . substr($stmt, 0, 60) . "...\n";
        } else {
            echo "ERROR: " . $e->getMessage() . "\n";
            echo "Statement: " . substr($stmt, 0, 120) . "\n";
        }
    }
}

echo "\nMigration finished. Applied: {$ok}, skipped: {$skip}\n";
echo "Next: visit seed_admin.php to create admin account.\n";
