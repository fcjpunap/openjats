<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/models/Database.php';

try {
    $db = Database::getInstance();
    $results = $db->fetchAll("SHOW INDEX FROM article_markup");
    print_r($results);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
