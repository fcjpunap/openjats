<?php
/**
 * Configuración de la base de datos
 */

return [
    'database' => [
        'host' => 'localhost',
        'port' => '3306',
        'database' => 'jats_assistant',
        'username' => 'jats_assistant',
        'password' => 'havxiv-1cotxa-huzMig',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],
    
    'app' => [
        'name' => 'JATS Markup Assistant',
        'version' => '1.0.0',
        'url' => 'https://fcjp.derecho.unap.edu.pe/catg/jats-assistant/public',
        'timezone' => 'America/Lima',
        'locale' => 'es',
    ],
    
    'paths' => [
        'uploads' => __DIR__ . '/../public/uploads/',
        'articles' => __DIR__ . '/../public/articles/',
        'temp' => __DIR__ . '/../public/uploads/temp/',
    ],
    
    'upload' => [
        'max_size' => 50 * 1024 * 1024, // 50 MB
        'allowed_types' => ['zip'],
    ],
    
    'security' => [
        'session_lifetime' => 7200, // 2 horas
        'password_min_length' => 8,
        'max_login_attempts' => 5,
    ],
];
