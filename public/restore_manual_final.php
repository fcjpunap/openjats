<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$config = require __DIR__ . '/../config/config.php';
$dbConfig = $config['database'];
$dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset=utf8mb4";
$pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    $sql = file_get_contents(__DIR__ . '/../jats_assistant.sql');
    
    // Some PDO versions don't like multi statements. We separate by ';' roughly.
    $stmts = explode(';', $sql);
    foreach($stmts as $stmt) {
        $trim = trim($stmt);
        if(!empty($trim)) {
            $pdo->exec($trim);
        }
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    echo "RESTORED!";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . " at " . substr($trim ?? '', 0, 100);
}
