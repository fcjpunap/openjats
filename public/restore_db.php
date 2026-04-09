<?php
$config = require __DIR__ . '/public/../config/config.php';
$dbConfig = $config['database'];
$pdo = new PDO("mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}", $dbConfig['username'], $dbConfig['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = file_get_contents('/Users/imac/Downloads/jats_assistant.sql');
try {
    $pdo->exec($sql);
    echo "SQL importado correctamente.\n";
} catch (PDOException $e) {
    echo "Error importando SQL: " . $e->getMessage() . "\n";
}
?>
