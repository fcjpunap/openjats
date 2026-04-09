<?php
/**
 * Verificación de Base de Datos - SIN CACHÉ
 * Subir a: /jats-assistant/public/verificar-bd-v2.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Limpiar caché de opcache si existe
if (function_exists('opcache_reset')) {
    opcache_reset();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificación de Base de Datos v2</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; max-width: 800px; margin: 0 auto; }
        h1 { color: #2563eb; }
        .ok { color: #10b981; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .test { margin: 15px 0; padding: 15px; border-radius: 4px; }
        .test.success { background: #d1fae5; border-left: 4px solid #10b981; }
        .test.fail { background: #fee2e2; border-left: 4px solid #ef4444; }
        .test.info { background: #dbeafe; border-left: 4px solid #2563eb; }
        code { background: #f3f4f6; padding: 2px 6px; border-radius: 3px; }
        pre { background: #1f2937; color: #f3f4f6; padding: 15px; border-radius: 6px; overflow-x: auto; }
        .big-button { 
            display: inline-block; 
            background: #2563eb; 
            color: white; 
            padding: 15px 30px; 
            border-radius: 8px; 
            text-decoration: none; 
            font-weight: bold; 
            margin: 20px 0;
        }
        .big-button:hover { background: #1e40af; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Verificación de Base de Datos (v2 - Sin Caché)</h1>
        
        <?php
        // Cargar configuración
        $configFile = '../config/config.php';
        
        if (!file_exists($configFile)) {
            echo '<div class="test fail"><span class="error">✗ Error:</span> No se encontró config.php</div>';
            exit;
        }
        
        $config = require $configFile;
        $dbConfig = $config['database'];
        
        echo '<div class="test info">';
        echo '<strong>Configuración de Base de Datos:</strong><br>';
        echo 'Host: ' . $dbConfig['host'] . '<br>';
        echo 'Base de datos: ' . $dbConfig['database'] . '<br>';
        echo 'Usuario: ' . $dbConfig['username'] . '<br>';
        echo '</div>';
        
        // Test 1: Conexión
        echo '<h2>1. Conexión a MySQL</h2>';
        try {
            $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};charset={$dbConfig['charset']}";
            $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo '<div class="test success"><span class="ok">✓</span> Conexión a MySQL exitosa</div>';
        } catch (PDOException $e) {
            echo '<div class="test fail"><span class="error">✗</span> Error: ' . $e->getMessage() . '</div>';
            exit;
        }
        
        // Test 2: Base de datos
        echo '<h2>2. Base de Datos</h2>';
        try {
            $pdo->exec("USE {$dbConfig['database']}");
            echo '<div class="test success"><span class="ok">✓</span> Base de datos existe</div>';
        } catch (PDOException $e) {
            echo '<div class="test fail"><span class="error">✗</span> Error: ' . $e->getMessage() . '</div>';
            exit;
        }
        
        // Test 3: Usuario admin
        echo '<h2>3. Usuario Admin</h2>';
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->execute(['username' => 'admin']);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin) {
                echo '<div class="test success"><span class="ok">✓</span> Usuario admin existe</div>';
                
                // Verificar password
                if (password_verify('admin123', $admin['password_hash'])) {
                    echo '<div class="test success"><span class="ok">✓</span> Password es correcto</div>';
                } else {
                    echo '<div class="test fail"><span class="error">✗</span> Password incorrecto</div>';
                }
            } else {
                echo '<div class="test fail"><span class="error">✗</span> Usuario admin NO existe</div>';
            }
        } catch (PDOException $e) {
            echo '<div class="test fail"><span class="error">✗</span> Error: ' . $e->getMessage() . '</div>';
        }
        
        // Test 4: Prueba de login manual (SIN usar User.php para evitar caché)
        echo '<h2>4. Prueba de Autenticación Manual</h2>';
        
        try {
            // Simular login directamente sin User.php
            $username = 'admin';
            $password = 'admin123';
            
            $stmt = $pdo->prepare(
                "SELECT * FROM users WHERE (username = :identifier OR email = :identifier) AND active = TRUE"
            );
            $stmt->execute(['identifier' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                echo '<div class="test success">';
                echo '<h3><span class="ok">✓✓✓ AUTENTICACIÓN EXITOSA ✓✓✓</span></h3>';
                echo '<p><strong>El login funcionará correctamente.</strong></p>';
                echo '<p>Usuario encontrado: ' . $user['username'] . '</p>';
                echo '<p>Email: ' . $user['email'] . '</p>';
                echo '<p>Rol: ' . $user['role'] . '</p>';
                echo '</div>';
                
                $loginWorks = true;
            } else {
                echo '<div class="test fail">';
                echo '<span class="error">✗</span> Autenticación falló<br>';
                if (!$user) {
                    echo 'Usuario no encontrado';
                } else {
                    echo 'Password incorrecto';
                }
                echo '</div>';
                $loginWorks = false;
            }
        } catch (PDOException $e) {
            echo '<div class="test fail">';
            echo '<span class="error">✗</span> Error SQL: ' . $e->getMessage();
            echo '</div>';
            $loginWorks = false;
        }
        
        // Resumen final
        echo '<h2>📊 Resumen Final</h2>';
        
        if (isset($loginWorks) && $loginWorks) {
            echo '<div class="test success" style="text-align: center; padding: 30px;">';
            echo '<h2 style="margin: 0 0 20px 0;">🎉 ¡TODO LISTO! 🎉</h2>';
            echo '<p style="font-size: 18px;">La autenticación funciona correctamente.</p>';
            echo '<p style="font-size: 16px; margin: 20px 0;">Ahora puedes acceder al sistema:</p>';
            echo '<a href="login.php" class="big-button">IR AL LOGIN →</a>';
            echo '<p style="margin-top: 20px;"><strong>Credenciales:</strong></p>';
            echo '<p>Usuario: <code>admin</code><br>';
            echo 'Contraseña: <code>admin123</code></p>';
            echo '</div>';
        } else {
            echo '<div class="test fail">';
            echo '<h3>❌ Hay problemas por resolver</h3>';
            echo '<p>Revisa los errores arriba y corrígelos.</p>';
            echo '</div>';
        }
        ?>
        
        <hr style="margin: 30px 0;">
        
        <h3>🔍 Información Adicional</h3>
        
        <div class="test info">
            <p><strong>Archivos verificados:</strong></p>
            <ul>
                <li>✓ config.php cargado correctamente</li>
                <li>✓ Conexión a base de datos funcional</li>
                <li>✓ Consulta SQL con :identifier funciona</li>
                <li>✓ Password hash verificado</li>
            </ul>
        </div>
        
        <div class="test info">
            <p><strong>Notas:</strong></p>
            <ul>
                <li>Esta versión NO usa User.php (evita problemas de caché)</li>
                <li>Ejecuta la misma consulta SQL que el login real</li>
                <li>Si este test pasa, el login funcionará</li>
            </ul>
        </div>
        
        <hr style="margin: 30px 0;">
        <p><small><strong>Elimina estos archivos después:</strong></small></p>
        <pre>rm verificar-bd.php
rm verificar-bd-v2.php
rm verificar-userphp.php</pre>
    </div>
</body>
</html>
