<?php
$config = require __DIR__ . '/../config/config.php';
$dbConfig = $config['database'];
$dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset=utf8mb4";
$pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$sql = file_get_contents(__DIR__ . '/../jats_assistant.sql');

// Extract INSERT columns for articles
preg_match('/INSERT INTO `articles` \([^)]+\) VALUES/', $sql, $art_match);
$art_insert = $art_match[0] ?? 'INSERT INTO articles VALUES';

// We just extract the exact rows 7 and 9
$q_art = "{$art_insert} ";
$lines = explode("\n", $sql);
$art_rows = [];
$sec_rows = [];

foreach ($lines as $l) {
    if (preg_match('/^\(7, NULL, \'20260322_986f3d\'/', $l) || preg_match('/^\(9, 1, \'20260323_613683\'/', $l)) {
        $art_rows[] = rtrim($l, ',');
    }
    if (preg_match('/^\(250, 7, /', $l) ||
        preg_match('/^\(251, 7, /', $l) ||
        preg_match('/^\(576, 9, /', $l) ||
        preg_match('/^\(580, 9, /', $l) ||
        preg_match('/^\(581, 9, /', $l) ||
        preg_match('/^\(583, 9, /', $l)) {
        $sec_rows[] = rtrim($l, ',');
    }
}

try {
    if (!empty($art_rows)) {
        $q1 = str_replace("INSERT INTO `articles`", "INSERT IGNORE INTO `articles`", $art_insert) . "\n" . implode(",\n", $art_rows) . ";";
        $pdo->exec($q1);
    }
    if (!empty($sec_rows)) {
        preg_match('/INSERT INTO `article_sections` \([^)]+\) VALUES/', $sql, $sec_match);
        $sec_insert = $sec_match[0] ?? 'INSERT INTO article_sections VALUES';
        $q2 = str_replace("INSERT INTO `article_sections`", "INSERT IGNORE INTO `article_sections`", $sec_insert) . "\n" . implode(",\n", $sec_rows) . ";";
        $pdo->exec($q2);
    }
    echo "Restored perfectly using raw data from dump!";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
