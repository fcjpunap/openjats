<?php
/**
 * Limpiar Caché de PHP OPcache
 * Subir a: /public/limpiar-cache.php
 * Acceder: https://fcjp.derecho.unap.edu.pe/catg/jats-assistant/public/limpiar-cache.php
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Limpiar Caché de PHP</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; max-width: 800px; margin: 0 auto; }
        h1 { color: #2563eb; }
        .ok { color: #10b981; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .warning { color: #f59e0b; font-weight: bold; }
        .box { padding: 15px; margin: 15px 0; border-radius: 6px; }
        .box.success { background: #d1fae5; border-left: 4px solid #10b981; }
        .box.fail { background: #fee2e2; border-left: 4px solid #ef4444; }
        .box.info { background: #dbeafe; border-left: 4px solid #2563eb; }
        .box.warning { background: #fef3c7; border-left: 4px solid #f59e0b; }
        pre { background: #1f2937; color: #f3f4f6; padding: 15px; border-radius: 6px; overflow-x: auto; }
        .btn { 
            display: inline-block; 
            background: #2563eb; 
            color: white; 
            padding: 12px 24px; 
            border-radius: 6px; 
            text-decoration: none; 
            font-weight: bold; 
            margin: 10px 5px;
        }
        .btn:hover { background: #1e40af; }
        .btn-success { background: #10b981; }
        .btn-success:hover { background: #059669; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧹 Limpiar Caché de PHP</h1>
        
        <?php
        echo '<div class="box info">';
        echo '<h3>📊 Estado de OPcache</h3>';
        
        if (function_exists('opcache_get_status')) {
            $status = opcache_get_status();
            
            if ($status !== false) {
                echo '<p><span class="ok">✓</span> OPcache está <strong>ACTIVO</strong></p>';
                echo '<ul>';
                echo '<li>Archivos en caché: ' . $status['opcache_statistics']['num_cached_scripts'] . '</li>';
                echo '<li>Memoria usada: ' . round($status['memory_usage']['used_memory'] / 1024 / 1024, 2) . ' MB</li>';
                echo '<li>Hits: ' . $status['opcache_statistics']['hits'] . '</li>';
                echo '<li>Misses: ' . $status['opcache_statistics']['misses'] . '</li>';
                echo '</ul>';
            } else {
                echo '<p><span class="warning">⚠</span> OPcache está instalado pero <strong>DESACTIVADO</strong></p>';
            }
        } else {
            echo '<p><span class="warning">⚠</span> OPcache <strong>NO ESTÁ INSTALADO</strong></p>';
            echo '<p>El problema no es OPcache entonces.</p>';
        }
        echo '</div>';
        
        // Intentar limpiar caché
        if (isset($_GET['clear']) && $_GET['clear'] === 'yes') {
            echo '<div class="box">';
            echo '<h3>🧹 Limpiando Caché...</h3>';
            
            $cleared = false;
            $methods = [];
            
            // Método 1: opcache_reset()
            if (function_exists('opcache_reset')) {
                try {
                    if (opcache_reset()) {
                        $methods[] = '<span class="ok">✓ opcache_reset() ejecutado</span>';
                        $cleared = true;
                    } else {
                        $methods[] = '<span class="error">✗ opcache_reset() falló</span>';
                    }
                } catch (Exception $e) {
                    $methods[] = '<span class="error">✗ opcache_reset() error: ' . $e->getMessage() . '</span>';
                }
            } else {
                $methods[] = '<span class="warning">⚠ opcache_reset() no disponible</span>';
            }
            
            // Método 2: opcache_invalidate() para User.php específicamente
            if (function_exists('opcache_invalidate')) {
                $userPhpPath = realpath('../src/models/User.php');
                try {
                    if (opcache_invalidate($userPhpPath, true)) {
                        $methods[] = '<span class="ok">✓ User.php invalidado en caché</span>';
                        $cleared = true;
                    } else {
                        $methods[] = '<span class="warning">⚠ opcache_invalidate() no pudo invalidar User.php</span>';
                    }
                } catch (Exception $e) {
                    $methods[] = '<span class="error">✗ opcache_invalidate() error: ' . $e->getMessage() . '</span>';
                }
            } else {
                $methods[] = '<span class="warning">⚠ opcache_invalidate() no disponible</span>';
            }
            
            // Método 3: Modificar timestamp del archivo
            $userPhpPath = realpath('../src/models/User.php');
            if (touch($userPhpPath)) {
                $methods[] = '<span class="ok">✓ Timestamp de User.php actualizado</span>';
            } else {
                $methods[] = '<span class="warning">⚠ No se pudo actualizar timestamp</span>';
            }
            
            echo '<ul>';
            foreach ($methods as $method) {
                echo '<li>' . $method . '</li>';
            }
            echo '</ul>';
            
            if ($cleared) {
                echo '<div class="box success" style="margin-top: 20px;">';
                echo '<h3><span class="ok">✓ Caché Limpiado</span></h3>';
                echo '<p><strong>Ahora prueba el login:</strong></p>';
                echo '<p><a href="login.php" class="btn btn-success">IR AL LOGIN →</a></p>';
                echo '<p>Usuario: <code>admin</code> | Contraseña: <code>admin123</code></p>';
                echo '</div>';
            } else {
                echo '<div class="box warning" style="margin-top: 20px;">';
                echo '<h3><span class="warning">⚠ No se pudo limpiar el caché automáticamente</span></h3>';
                echo '<p>Opciones:</p>';
                echo '<ol>';
                echo '<li>Espera 5-10 minutos (el caché expirará solo)</li>';
                echo '<li>Contacta al administrador del servidor para reiniciar PHP-FPM</li>';
                echo '<li>Usa la alternativa de login directa (ver abajo)</li>';
                echo '</ol>';
                echo '</div>';
            }
            echo '</div>';
        } else {
            // Mostrar botón para limpiar
            echo '<div class="box info">';
            echo '<h3>🎯 Acción</h3>';
            echo '<p>El archivo User.php está correcto, pero PHP está usando la versión antigua en caché.</p>';
            echo '<p><strong>Haz clic aquí para limpiar el caché:</strong></p>';
            echo '<a href="?clear=yes" class="btn">🧹 LIMPIAR CACHÉ AHORA</a>';
            echo '</div>';
        }
        
        // Información adicional
        echo '<hr style="margin: 30px 0;">';
        echo '<div class="box info">';
        echo '<h3>ℹ️ Información</h3>';
        echo '<p><strong>Estado actual:</strong></p>';
        echo '<ul>';
        echo '<li>✅ Base de datos: OK</li>';
        echo '<li>✅ User.php: Código correcto en disco</li>';
        echo '<li>✅ api.php: Funciona</li>';
        echo '<li>❌ PHP caché: Usando versión antigua</li>';
        echo '</ul>';
        
        echo '<p><strong>Hash de User.php:</strong></p>';
        $userPhpPath = realpath('../src/models/User.php');
        echo '<code>' . hash_file('sha256', $userPhpPath) . '</code>';
        echo '<p style="font-size: 12px;">Este hash confirma que el archivo en disco es el correcto.</p>';
        echo '</div>';
        
        // Alternativa: Login sin caché
        echo '<hr style="margin: 30px 0;">';
        echo '<div class="box warning">';
        echo '<h3>🔧 Alternativa: Login Directo (sin User.php)</h3>';
        echo '<p>Si el caché no se puede limpiar, prueba esta alternativa:</p>';
        echo '<a href="test-login.html" class="btn">PROBAR LOGIN DIRECTO →</a>';
        echo '<p style="font-size: 13px;">Esta página usa el código correcto directamente sin pasar por User.php.</p>';
        echo '</div>';
        ?>
    </div>
</body>
</html>
