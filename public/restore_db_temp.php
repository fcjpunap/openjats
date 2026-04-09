<?php
$config = require __DIR__ . '/../config/config.php';
$dbConfig = $config['database'];
$dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset=utf8mb4";
$pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$sqlFile = __DIR__ . '/../jats_assistant.sql';
if (file_exists($sqlFile)) {
    try {
        $sql = file_get_contents($sqlFile);
        $pdo->exec($sql);
        echo "DB RESTORED SUCCESSFULLY.\n";
    } catch (Exception $e) {
        echo "ERROR RESTORING: " . $e->getMessage() . "\n";
    }
} else {
    echo "SQL FILE NOT FOUND on remote.\n";
}
