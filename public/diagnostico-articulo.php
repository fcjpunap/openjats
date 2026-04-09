<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo "No hay sesión. <a href='login-directo.php'>Login</a>";
    exit;
}

$articleId = $_GET['id'] ?? 5;

// Conectar a BD
$config = require __DIR__ . '/../config/config.php';
$db = $config['database'];

try {
    $pdo = new PDO(
        "mysql:host={$db['host']};dbname={$db['database']};charset={$db['charset']}",
        $db['username'],
        $db['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico Artículo #<?php echo $articleId; ?></title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #2563eb; }
        h2 { color: #374151; margin-top: 30px; }
        pre { background: #1f2937; color: #f3f4f6; padding: 15px; border-radius: 6px; overflow-x: auto; white-space: pre-wrap; word-wrap: break-word; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #e5e7eb; padding: 10px; text-align: left; }
        th { background: #f9fafb; font-weight: 600; }
        .ok { color: #10b981; }
        .fail { color: #ef4444; }
        .warn { color: #f59e0b; }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico Completo - Artículo #<?php echo $articleId; ?></h1>
    
    <div class="box">
        <h2>1. Datos en Base de Datos</h2>
        <?php
        try {
            $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
            $stmt->execute([$articleId]);
            $article = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($article) {
                echo "<p class='ok'>✅ Artículo encontrado en BD</p>";
                echo "<table>";
                foreach ($article as $key => $value) {
                    $displayValue = $value;
                    if ($key === 'html_content' && $value) {
                        $displayValue = substr($value, 0, 200) . '... (' . strlen($value) . ' caracteres total)';
                    } elseif (is_null($value)) {
                        $displayValue = '<span class="warn">NULL</span>';
                    } elseif ($value === '') {
                        $displayValue = '<span class="warn">(vacío)</span>';
                    }
                    echo "<tr><th>$key</th><td>" . htmlspecialchars($displayValue) . "</td></tr>";
                }
                echo "</table>";
                
                // Verificar campos críticos
                if (empty($article['html_content'])) {
                    echo "<p class='fail'>❌ <strong>PROBLEMA:</strong> html_content está vacío o NULL</p>";
                } else {
                    echo "<p class='ok'>✅ html_content tiene " . strlen($article['html_content']) . " caracteres</p>";
                }
                
                if (empty($article['original_file_path'])) {
                    echo "<p class='warn'>⚠️ original_file_path está vacío</p>";
                } else {
                    echo "<p class='ok'>✅ original_file_path: " . htmlspecialchars($article['original_file_path']) . "</p>";
                }
                
            } else {
                echo "<p class='fail'>❌ Artículo no encontrado en BD</p>";
            }
        } catch (PDOException $e) {
            echo "<p class='fail'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </div>
    
    <div class="box">
        <h2>2. Archivos en Servidor</h2>
        <?php
        $articlesDir = $config['paths']['articles'] ?? __DIR__ . '/articles/';
        $uploadsDir = $config['paths']['uploads'] ?? __DIR__ . '/uploads/';
        
        echo "<h3>Directorio articles/</h3>";
        if (is_dir($articlesDir)) {
            echo "<p class='ok'>✅ Directorio existe: $articlesDir</p>";
            $files = scandir($articlesDir);
            if (count($files) > 2) { // Más que . y ..
                echo "<p>Archivos encontrados:</p><ul>";
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $fullPath = $articlesDir . $file;
                        $size = is_file($fullPath) ? filesize($fullPath) : 'N/A';
                        echo "<li>$file ($size bytes)</li>";
                    }
                }
                echo "</ul>";
            } else {
                echo "<p class='warn'>⚠️ No hay archivos en articles/</p>";
            }
        } else {
            echo "<p class='fail'>❌ Directorio no existe: $articlesDir</p>";
        }
        
        echo "<h3>Directorio uploads/</h3>";
        if (is_dir($uploadsDir)) {
            echo "<p class='ok'>✅ Directorio existe: $uploadsDir</p>";
            $files = scandir($uploadsDir);
            if (count($files) > 2) {
                echo "<p>Archivos encontrados:</p><ul>";
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $fullPath = $uploadsDir . $file;
                        $size = is_file($fullPath) ? filesize($fullPath) : 'DIR';
                        echo "<li>$file ($size bytes)</li>";
                    }
                }
                echo "</ul>";
            } else {
                echo "<p class='warn'>⚠️ No hay archivos en uploads/</p>";
            }
        } else {
            echo "<p class='fail'>❌ Directorio no existe: $uploadsDir</p>";
        }
        
        echo "<h3>Directorio uploads/temp/</h3>";
        $tempDir = $uploadsDir . 'temp/';
        if (is_dir($tempDir)) {
            echo "<p class='ok'>✅ Directorio existe: $tempDir</p>";
            $files = scandir($tempDir);
            if (count($files) > 2) {
                echo "<p>Archivos/carpetas temporales:</p><ul>";
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $fullPath = $tempDir . $file;
                        if (is_dir($fullPath)) {
                            echo "<li><strong>DIR:</strong> $file/</li>";
                            // Listar contenido del directorio
                            $subFiles = scandir($fullPath);
                            foreach ($subFiles as $subFile) {
                                if ($subFile !== '.' && $subFile !== '..') {
                                    echo "<li style='margin-left: 30px;'>→ $subFile</li>";
                                }
                            }
                        } else {
                            $size = filesize($fullPath);
                            echo "<li>$file ($size bytes)</li>";
                        }
                    }
                }
                echo "</ul>";
            } else {
                echo "<p class='warn'>⚠️ No hay archivos temporales</p>";
            }
        }
        ?>
    </div>
    
    <div class="box">
        <h2>3. Verificar ArticleController</h2>
        <?php
        $controllerPath = __DIR__ . '/../src/controllers/ArticleController.php';
        if (file_exists($controllerPath)) {
            echo "<p class='ok'>✅ ArticleController.php existe</p>";
            echo "<p>Tamaño: " . filesize($controllerPath) . " bytes</p>";
            
            // Ver el método uploadZip
            $content = file_get_contents($controllerPath);
            if (strpos($content, 'function uploadZip') !== false) {
                echo "<p class='ok'>✅ Método uploadZip() existe</p>";
            } else {
                echo "<p class='fail'>❌ Método uploadZip() NO encontrado</p>";
            }
            
            if (strpos($content, 'html_content') !== false) {
                echo "<p class='ok'>✅ Código menciona 'html_content'</p>";
            } else {
                echo "<p class='warn'>⚠️ Código NO menciona 'html_content'</p>";
            }
        } else {
            echo "<p class='fail'>❌ ArticleController.php NO existe en: $controllerPath</p>";
        }
        ?>
    </div>
    
    <div class="box">
        <h2>4. Logs de Actividad</h2>
        <?php
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM activity_logs 
                WHERE article_id = ? 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $stmt->execute([$articleId]);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($logs) {
                echo "<p class='ok'>✅ " . count($logs) . " log(s) encontrado(s)</p>";
                echo "<table>";
                echo "<tr><th>Fecha</th><th>Acción</th><th>Detalles</th></tr>";
                foreach ($logs as $log) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($log['created_at']) . "</td>";
                    echo "<td>" . htmlspecialchars($log['action'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars(substr($log['details'] ?? '', 0, 100)) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='warn'>⚠️ No hay logs para este artículo</p>";
            }
        } catch (PDOException $e) {
            echo "<p class='fail'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </div>
    
    <div class="box">
        <h2>5. Conclusión y Próximos Pasos</h2>
        <?php
        if ($article && !empty($article['html_content'])) {
            echo "<p class='ok'><strong>✅ El artículo tiene contenido HTML</strong></p>";
            echo "<p>El problema puede estar en cómo el editor lo muestra.</p>";
        } elseif ($article) {
            echo "<p class='fail'><strong>❌ El artículo NO tiene contenido HTML</strong></p>";
            echo "<p><strong>Posibles causas:</strong></p>";
            echo "<ul>";
            echo "<li>El ZIP no contenía un archivo HTML</li>";
            echo "<li>El HTML no se extrajo correctamente</li>";
            echo "<li>El HTML no se guardó en la BD</li>";
            echo "<li>ArticleController tiene un bug</li>";
            echo "</ul>";
            echo "<p><strong>Recomendación:</strong></p>";
            echo "<ol>";
            echo "<li>Revisa qué hay en uploads/temp/ (arriba)</li>";
            echo "<li>Verifica que el ZIP contenga un archivo .html</li>";
            echo "<li>Sube el ArticleController corregido</li>";
            echo "</ol>";
        }
        ?>
    </div>
    
    <hr>
    <p><a href="index.php">← Volver al Dashboard</a> | <a href="editor.php?id=<?php echo $articleId; ?>">Ver en Editor</a></p>
</body>
</html>
