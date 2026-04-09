<?php
/**
 * Lector de User.php - Muestra el código exacto del servidor
 * Subir a: /public/leer-userphp.php
 * Acceder: https://fcjp.derecho.unap.edu.pe/catg/jats-assistant/public/leer-userphp.php
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Leer User.php del Servidor</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; max-width: 1200px; margin: 0 auto; }
        h1 { color: #2563eb; }
        .ok { color: #10b981; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .warning { color: #f59e0b; font-weight: bold; }
        pre { background: #1f2937; color: #f3f4f6; padding: 15px; border-radius: 6px; overflow-x: auto; font-size: 13px; line-height: 1.5; }
        .highlight { background: yellow; color: black; padding: 2px 5px; }
        .box { padding: 15px; margin: 15px 0; border-radius: 6px; }
        .box.success { background: #d1fae5; border-left: 4px solid #10b981; }
        .box.fail { background: #fee2e2; border-left: 4px solid #ef4444; }
        .box.info { background: #dbeafe; border-left: 4px solid #2563eb; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Contenido de User.php en el Servidor</h1>
        
        <?php
        $userPhpPath = '../src/models/User.php';
        
        if (!file_exists($userPhpPath)) {
            echo '<div class="box fail">';
            echo '<span class="error">✗ User.php NO ENCONTRADO</span><br>';
            echo 'Ruta buscada: ' . realpath('..') . '/src/models/User.php';
            echo '</div>';
            exit;
        }
        
        echo '<div class="box success">';
        echo '<span class="ok">✓ User.php encontrado</span><br>';
        echo '<strong>Ruta:</strong> ' . realpath($userPhpPath) . '<br>';
        echo '<strong>Tamaño:</strong> ' . filesize($userPhpPath) . ' bytes<br>';
        echo '<strong>Última modificación:</strong> ' . date('Y-m-d H:i:s', filemtime($userPhpPath));
        echo '</div>';
        
        $content = file_get_contents($userPhpPath);
        $lines = explode("\n", $content);
        
        // Buscar la línea problemática
        $hasOldCode = false;
        $hasNewCode = false;
        $problemLine = null;
        
        foreach ($lines as $num => $line) {
            $lineNumber = $num + 1;
            if (strpos($line, 'username = :username OR email = :username') !== false) {
                $hasOldCode = true;
                $problemLine = $lineNumber;
            }
            if (strpos($line, 'username = :identifier OR email = :identifier') !== false) {
                $hasNewCode = true;
                $problemLine = $lineNumber;
            }
        }
        
        if ($hasNewCode && !$hasOldCode) {
            echo '<div class="box success">';
            echo '<h2><span class="ok">✓✓✓ User.php ESTÁ CORRECTO ✓✓✓</span></h2>';
            echo '<p>El archivo tiene el código corregido con <code>:identifier</code></p>';
            echo '<p><strong>Línea ' . $problemLine . ':</strong></p>';
            echo '<pre>' . htmlspecialchars($lines[$problemLine - 1]) . '</pre>';
            echo '<p>El login debería funcionar. Si no funciona, es problema de caché de PHP.</p>';
            echo '</div>';
        } else if ($hasOldCode) {
            echo '<div class="box fail">';
            echo '<h2><span class="error">✗✗✗ User.php TIENE EL BUG ✗✗✗</span></h2>';
            echo '<p>El archivo TODAVÍA contiene el código antiguo con <code>:username</code></p>';
            echo '<p><strong>Línea ' . $problemLine . ' (INCORRECTA):</strong></p>';
            echo '<pre>' . htmlspecialchars($lines[$problemLine - 1]) . '</pre>';
            echo '<p class="error"><strong>Por eso el login falla con error SQL.</strong></p>';
            echo '</div>';
            
            echo '<div class="box fail">';
            echo '<h3>🔧 SOLUCIÓN:</h3>';
            echo '<ol>';
            echo '<li><strong>Descarga User.php corregido nuevamente</strong></li>';
            echo '<li><strong>Verifica que el archivo descargado diga ":identifier" en la línea 17</strong></li>';
            echo '<li><strong>Sube a:</strong> <code>/catg/jats-assistant/src/models/User.php</code></li>';
            echo '<li><strong>SOBRESCRIBE el archivo existente (muy importante)</strong></li>';
            echo '<li><strong>Recarga esta página para verificar</strong></li>';
            echo '</ol>';
            echo '</div>';
        } else {
            echo '<div class="box fail">';
            echo '<h2><span class="warning">⚠ Estado desconocido</span></h2>';
            echo '<p>No se encontró ninguna de las dos versiones del código.</p>';
            echo '</div>';
        }
        
        // Mostrar el método login() completo
        echo '<hr style="margin: 30px 0;">';
        echo '<h2>📄 Código completo del método login()</h2>';
        echo '<p>Verificar manualmente la línea del SELECT:</p>';
        
        echo '<pre>';
        $inLoginMethod = false;
        $braceCount = 0;
        
        foreach ($lines as $num => $line) {
            $lineNumber = $num + 1;
            
            if (strpos($line, 'public function login(') !== false) {
                $inLoginMethod = true;
            }
            
            if ($inLoginMethod) {
                // Highlight líneas importantes
                $lineHtml = htmlspecialchars($line);
                
                if (strpos($line, 'username = :username OR email = :username') !== false) {
                    $lineHtml = '<span class="highlight" style="background: #fee2e2; color: #991b1b;">' . $lineHtml . '</span>';
                }
                if (strpos($line, 'username = :identifier OR email = :identifier') !== false) {
                    $lineHtml = '<span class="highlight" style="background: #d1fae5; color: #065f46;">' . $lineHtml . '</span>';
                }
                
                printf("%3d: %s\n", $lineNumber, $lineHtml);
                
                $braceCount += substr_count($line, '{') - substr_count($line, '}');
                if ($braceCount == 0 && strpos($line, '}') !== false && $lineNumber > 15) {
                    break;
                }
            }
        }
        echo '</pre>';
        
        // Instrucciones finales
        echo '<hr style="margin: 30px 0;">';
        echo '<div class="box info">';
        echo '<h3>📝 Código correcto (debe decir esto en la línea ~17):</h3>';
        echo '<pre>';
        echo '"SELECT * FROM users WHERE (username = :identifier OR email = :identifier) AND active = TRUE",';
        echo '</pre>';
        
        echo '<h3>❌ Código incorrecto (NO debe decir esto):</h3>';
        echo '<pre>';
        echo '"SELECT * FROM users WHERE (username = :username OR email = :username) AND active = TRUE",';
        echo '</pre>';
        echo '</div>';
        
        // Mostrar hash del archivo para verificar cambios
        echo '<hr style="margin: 30px 0;">';
        echo '<div class="box info">';
        echo '<p><strong>Hash SHA256 del archivo:</strong></p>';
        echo '<code>' . hash_file('sha256', $userPhpPath) . '</code>';
        echo '<p style="font-size: 12px; color: #666;">Este hash cambiará cuando actualices el archivo.</p>';
        echo '</div>';
        ?>
    </div>
</body>
</html>
