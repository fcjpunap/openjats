<?php
/**
 * Verificador de User.php
 * Subir a: /catg/jats-assistant/public/verificar-userphp.php
 * Acceder: https://fcjp.derecho.unap.edu.pe/catg/jats-assistant/public/verificar-userphp.php
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificar User.php</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; max-width: 800px; margin: 0 auto; }
        h1 { color: #2563eb; }
        .ok { color: #10b981; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .test { margin: 15px 0; padding: 15px; border-radius: 4px; }
        .test.success { background: #d1fae5; border-left: 4px solid #10b981; }
        .test.fail { background: #fee2e2; border-left: 4px solid #ef4444; }
        pre { background: #1f2937; color: #f3f4f6; padding: 15px; border-radius: 6px; overflow-x: auto; }
        code { background: #f3f4f6; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Verificador de User.php</h1>
        
        <?php
        $userPhpPath = '../src/models/User.php';
        
        // Verificar que el archivo existe
        if (!file_exists($userPhpPath)) {
            echo '<div class="test fail">';
            echo '<span class="error">✗ Error:</span> No se encontró el archivo User.php en:<br>';
            echo '<code>' . realpath('..') . '/src/models/User.php</code>';
            echo '</div>';
            exit;
        }
        
        echo '<div class="test success">';
        echo '<span class="ok">✓</span> Archivo User.php encontrado en:<br>';
        echo '<code>' . realpath($userPhpPath) . '</code>';
        echo '</div>';
        
        // Leer el contenido del archivo
        $content = file_get_contents($userPhpPath);
        
        // Verificar si tiene el código correcto
        $hasCorrectCode = strpos($content, ':identifier') !== false;
        $hasIncorrectCode = strpos($content, 'username = :username OR email = :username') !== false;
        
        if ($hasCorrectCode && !$hasIncorrectCode) {
            echo '<div class="test success">';
            echo '<h2><span class="ok">✓ User.php está CORRECTO</span></h2>';
            echo '<p>El archivo contiene el código corregido con <code>:identifier</code></p>';
            echo '<p><strong>El login debería funcionar ahora.</strong></p>';
            echo '</div>';
            
            // Mostrar la línea correcta
            echo '<div class="test success">';
            echo '<h3>Código encontrado (CORRECTO):</h3>';
            echo '<pre>';
            $lines = explode("\n", $content);
            foreach ($lines as $num => $line) {
                if (strpos($line, ':identifier') !== false) {
                    echo sprintf("%3d: %s\n", $num + 1, htmlspecialchars($line));
                }
            }
            echo '</pre>';
            echo '</div>';
            
            echo '<div class="test success">';
            echo '<h3>✅ Siguiente paso:</h3>';
            echo '<p>1. Accede a <a href="verificar-bd.php">verificar-bd.php</a></p>';
            echo '<p>2. La sección 5 (Prueba de Autenticación) debería pasar ✓</p>';
            echo '<p>3. Prueba el <a href="login.php">login</a></p>';
            echo '</div>';
            
        } else if ($hasIncorrectCode) {
            echo '<div class="test fail">';
            echo '<h2><span class="error">✗ User.php está INCORRECTO</span></h2>';
            echo '<p>El archivo todavía contiene el código antiguo con <code>:username</code></p>';
            echo '<p><strong>Por eso el login NO funciona.</strong></p>';
            echo '</div>';
            
            // Mostrar la línea incorrecta
            echo '<div class="test fail">';
            echo '<h3>Código encontrado (INCORRECTO):</h3>';
            echo '<pre>';
            $lines = explode("\n", $content);
            foreach ($lines as $num => $line) {
                if (strpos($line, 'username = :username OR email = :username') !== false) {
                    echo sprintf("%3d: %s\n", $num + 1, htmlspecialchars($line));
                }
            }
            echo '</pre>';
            echo '</div>';
            
            echo '<div class="test fail">';
            echo '<h3>🔧 Solución:</h3>';
            echo '<p><strong>1. Descarga el archivo User.php corregido</strong></p>';
            echo '<p><strong>2. Súbelo a:</strong> <code>/catg/jats-assistant/src/models/User.php</code></p>';
            echo '<p><strong>3. SOBRESCRIBE el archivo existente</strong></p>';
            echo '<p><strong>4. Recarga esta página para verificar</strong></p>';
            echo '</div>';
            
            echo '<div class="test fail">';
            echo '<h3>📝 Código que debe tener (línea 17):</h3>';
            echo '<pre>';
            echo 'Debe decir:' . "\n";
            echo '"SELECT * FROM users WHERE (username = :identifier OR email = :identifier) AND active = TRUE",' . "\n\n";
            echo 'NO debe decir:' . "\n";
            echo '"SELECT * FROM users WHERE (username = :username OR email = :username) AND active = TRUE",';
            echo '</pre>';
            echo '</div>';
            
        } else {
            echo '<div class="test fail">';
            echo '<h2><span class="error">⚠ Estado desconocido</span></h2>';
            echo '<p>No se pudo determinar si el archivo es correcto o no.</p>';
            echo '</div>';
        }
        
        // Mostrar más información
        echo '<hr style="margin: 30px 0;">';
        echo '<h3>📊 Información del archivo:</h3>';
        echo '<ul>';
        echo '<li><strong>Ruta:</strong> ' . realpath($userPhpPath) . '</li>';
        echo '<li><strong>Tamaño:</strong> ' . filesize($userPhpPath) . ' bytes</li>';
        echo '<li><strong>Última modificación:</strong> ' . date('Y-m-d H:i:s', filemtime($userPhpPath)) . '</li>';
        echo '<li><strong>Permisos:</strong> ' . substr(sprintf('%o', fileperms($userPhpPath)), -4) . '</li>';
        echo '</ul>';
        
        // Botón para mostrar código completo
        echo '<details>';
        echo '<summary style="cursor: pointer; padding: 10px; background: #f3f4f6; border-radius: 4px; margin: 10px 0;">Ver código completo del método login() (click para expandir)</summary>';
        echo '<pre style="font-size: 12px;">';
        $lines = explode("\n", $content);
        $inLoginMethod = false;
        $braceCount = 0;
        foreach ($lines as $num => $line) {
            if (strpos($line, 'public function login(') !== false) {
                $inLoginMethod = true;
            }
            if ($inLoginMethod) {
                echo sprintf("%3d: %s\n", $num + 1, htmlspecialchars($line));
                
                // Contar llaves para saber cuándo termina el método
                $braceCount += substr_count($line, '{') - substr_count($line, '}');
                if ($braceCount == 0 && strpos($line, '}') !== false) {
                    break;
                }
            }
        }
        echo '</pre>';
        echo '</details>';
        ?>
        
        <hr style="margin: 30px 0;">
        <p><small><strong>Nota:</strong> Elimina este archivo después de verificar: <code>rm verificar-userphp.php</code></small></p>
    </div>
</body>
</html>
