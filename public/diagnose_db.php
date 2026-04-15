<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/models/Database.php';

echo "<pre>\n";
try {
    $db = Database::getInstance();
    $stmt = $db->query("SELECT markup_data FROM article_markup WHERE article_id = 1405 ORDER BY id DESC LIMIT 1");
    if ($stmt && $stmt->num_rows > 0) {
        $row = $stmt->fetch_assoc();
        $markup = json_decode($row['markup_data'], true);
        echo "=== TABLES ===\n";
        print_r($markup['tables'] ?? []);
    } else {
        echo "No markup found!";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
echo "</pre>\n";
