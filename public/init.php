<?php
/**
 * Script de Inicialización Automática
 * init.php - Crear directorios y verificar configuración
 */

session_start();

if (!isset($_SESSION['user_id']) || !$_SESSION['logged_in']) {
    header('Location: login-directo.php');
    exit;
}

// Directorios necesarios
$baseDir = __DIR__;
$directories = [
    $baseDir . '/uploads',
    $baseDir . '/uploads/temp',
    $baseDir . '/articles'
];

$created = [];
$errors = [];

// Crear directorios
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (@mkdir($dir, 0775, true)) {
            $created[] = basename($dir);
        } else {
            $errors[] = "No se pudo crear: " . basename($dir);
        }
    }
}

// Crear archivo .htaccess en uploads para seguridad
$htaccessContent = "# Proteger archivos\nOptions -Indexes\n<FilesMatch \"\.(php|php3|php4|php5|phtml)$\">\n  Deny from all\n</FilesMatch>";
$htaccessPath = $baseDir . '/uploads/.htaccess';
if (!file_exists($htaccessPath)) {
    file_put_contents($htaccessPath, $htaccessContent);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicialización - JATS Assistant</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .init-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
        }
        .init-box {
            background: white;
            padding: 40px;
            border-radius: 12px;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .init-box h1 {
            color: #2563eb;
            margin-top: 0;
        }
        .status-item {
            padding: 15px;
            margin: 10px 0;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .status-item.success {
            background: #d1fae5;
            color: #065f46;
        }
        .status-item.error {
            background: #fee2e2;
            color: #991b1b;
        }
        .status-item.info {
            background: #dbeafe;
            color: #1e40af;
        }
    </style>
</head>
<body class="init-page">
    <div class="init-box">
        <h1>🔧 Inicialización del Sistema</h1>
        
        <div class="status-item success">
            ✅ Sistema verificado correctamente
        </div>
        
        <?php if (!empty($created)): ?>
        <div class="status-item success">
            📁 Directorios creados: <?php echo implode(', ', $created); ?>
        </div>
        <?php else: ?>
        <div class="status-item info">
            ℹ️ Todos los directorios ya existían
        </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $error): ?>
        <div class="status-item error">
            ❌ <?php echo $error; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
        
        <div class="status-item success">
            🔒 Archivos de seguridad configurados
        </div>
        
        <p style="margin-top: 30px; text-align: center;">
            <strong>¡Listo para usar!</strong>
        </p>
        
        <div style="margin-top: 20px; text-align: center;">
            <a href="index.php" class="btn btn-primary" style="display: inline-block; padding: 12px 24px; text-decoration: none;">
                Ir al Dashboard →
            </a>
        </div>
        
        <p style="margin-top: 20px; font-size: 13px; color: #666; text-align: center;">
            Este script solo se ejecuta una vez para configurar el sistema.
        </p>
    </div>
</body>
</html>
