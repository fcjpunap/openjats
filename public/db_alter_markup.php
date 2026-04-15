<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/models/Database.php';

try {
    $db = Database::getInstance();
    $stmt = $db->query("SHOW INDEX FROM article_markup");
    if ($stmt) {
        while ($row = $stmt->fetch_assoc()) {
            if ($row['Key_name'] !== 'PRIMARY' && $row['Non_unique'] == 0 && $row['Column_name'] === 'article_id') {
                echo "Removing UNIQUE KEY {$row['Key_name']}...\n";
                $db->query("ALTER TABLE article_markup DROP INDEX {$row['Key_name']}");
                $db->query("ALTER TABLE article_markup ADD INDEX {$row['Key_name']} (article_id)");
                echo "✅ Index removed and recreated as non-unique. Ready!\n";
            }
        }
    }
    echo "Done checking indexes.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
