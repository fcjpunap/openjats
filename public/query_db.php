<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/models/Database.php';

try {
    $db = Database::getInstance();
    $stmt = $db->query("SELECT id, title, keywords, keywords_en FROM articles WHERE id = 1405 OR title LIKE '%fauna urbana%' LIMIT 1");
    if ($stmt && $stmt->num_rows > 0) {
        $row = $stmt->fetch_assoc();
        echo "=== ARTICLE DATA ===\n";
        print_r($row);

        $stmt2 = $db->query("SELECT given_names, surname FROM authors WHERE article_id = " . $row['id']);
        echo "\n=== AUTHORS ===\n";
        while($a = $stmt2->fetch_assoc()) {
            print_r($a);
        }
    } else {
        echo "No data found!";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
