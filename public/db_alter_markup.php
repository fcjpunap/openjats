<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/models/Database.php';

try {
    $db = Database::getInstance();
    $db->query("ALTER TABLE article_markup ADD INDEX new_article_id_idx (article_id)");
    $db->query("ALTER TABLE article_markup DROP INDEX unique_article_markup");
    echo "Done.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
