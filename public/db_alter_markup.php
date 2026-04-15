<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/models/Database.php';

try {
    $db = Database::getInstance();
    $results = $db->fetchAll("SHOW INDEX FROM article_markup");
    if ($results) {
        foreach ($results as $row) {
            if ($row['Key_name'] !== 'PRIMARY' && $row['Non_unique'] == 0 && $row['Column_name'] === 'article_id') {
                echo "Removing UNIQUE KEY {$row['Key_name']}...\n";
                $db->query("ALTER TABLE article_markup DROP KEY {$row['Key_name']}");
                $db->query("ALTER TABLE article_markup ADD INDEX {$row['Key_name']}_idx (article_id)");
                echo "✅ Index removed and recreated as non-unique. Ready!\n";
            }
        }
    }
    echo "Done checking indexes.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
