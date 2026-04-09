<?php
/**
 * Verificación de Base de Datos
 * Subir a: /jats-assistant/public/verificar-bd.php
 * Acceder: https://fcjp.derecho.unap.edu.pe/catg/jats-assistant/public/verificar-bd.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificación de Base de Datos</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; max-width: 800px; margin: 0 auto; }
        h1 { color: #2563eb; }
        .ok { color: #10b981; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .warning { color: #f59e0b; font-weight: bold; }
        .test { margin: 15px 0; padding: 15px; border-radius: 4px; }
        .test.success { background: #d1fae5; border-left: 4px solid #10b981; }
        .test.fail { background: #fee2e2; border-left: 4px solid #ef4444; }
        .test.info { background: #dbeafe; border-left: 4px solid #2563eb; }
        code { background: #f3f4f6; padding: 2px 6px; border-radius: 3px; }
        pre { background: #1f2937; color: #f3f4f6; padding: 15px; border-radius: 6px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Verificación de Base de Datos</h1>
        
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
        echo 'Puerto: ' . $dbConfig['port'] . '<br>';
        echo 'Base de datos: ' . $dbConfig['database'] . '<br>';
        echo 'Usuario: ' . $dbConfig['username'] . '<br>';
        echo 'Contraseña: ' . str_repeat('*', strlen($dbConfig['password'])) . '<br>';
        echo '</div>';
        
        // Test 1: Conexión a MySQL
        echo '<h2>1. Conexión a MySQL</h2>';
        try {
            $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};charset={$dbConfig['charset']}";
            $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo '<div class="test success"><span class="ok">✓</span> Conexión a MySQL exitosa</div>';
        } catch (PDOException $e) {
            echo '<div class="test fail">';
            echo '<span class="error">✗ Error de conexión a MySQL:</span><br>';
            echo 'Mensaje: ' . $e->getMessage() . '<br><br>';
            echo '<strong>Posibles causas:</strong><br>';
            echo '• Usuario o contraseña incorrectos<br>';
            echo '• MySQL no está corriendo<br>';
            echo '• Host incorrecto (¿debería ser 127.0.0.1 en lugar de localhost?)<br>';
            echo '</div>';
            exit;
        }
        
        // Test 2: Verificar si la base de datos existe
        echo '<h2>2. Base de Datos</h2>';
        try {
            $stmt = $pdo->query("SHOW DATABASES LIKE '{$dbConfig['database']}'");
            $dbExists = $stmt->rowCount() > 0;
            
            if ($dbExists) {
                echo '<div class="test success"><span class="ok">✓</span> Base de datos <code>' . $dbConfig['database'] . '</code> existe</div>';
                
                // Conectar a la base de datos
                $pdo->exec("USE {$dbConfig['database']}");
                
            } else {
                echo '<div class="test fail">';
                echo '<span class="error">✗</span> Base de datos <code>' . $dbConfig['database'] . '</code> NO existe<br><br>';
                echo '<strong>Solución:</strong><br>';
                echo '1. Accede a phpMyAdmin<br>';
                echo '2. Crea la base de datos: <code>' . $dbConfig['database'] . '</code><br>';
                echo '3. Importa el archivo: <code>database-fixed.sql</code><br>';
                echo '</div>';
                exit;
            }
        } catch (PDOException $e) {
            echo '<div class="test fail"><span class="error">✗ Error:</span> ' . $e->getMessage() . '</div>';
            exit;
        }
        
        // Test 3: Verificar tablas
        echo '<h2>3. Tablas de la Base de Datos</h2>';
        try {
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (count($tables) > 0) {
                echo '<div class="test success"><span class="ok">✓</span> Se encontraron ' . count($tables) . ' tablas</div>';
                echo '<div class="test info">';
                echo '<strong>Tablas encontradas:</strong><br>';
                echo implode(', ', $tables);
                echo '</div>';
                
                // Verificar tablas críticas
                $requiredTables = ['users', 'articles', 'article_references'];
                $missingTables = array_diff($requiredTables, $tables);
                
                if (!empty($missingTables)) {
                    echo '<div class="test fail">';
                    echo '<span class="error">✗</span> Faltan tablas críticas: ' . implode(', ', $missingTables) . '<br><br>';
                    echo '<strong>Solución:</strong><br>';
                    echo 'Importa el archivo <code>database-fixed.sql</code> en phpMyAdmin';
                    echo '</div>';
                }
                
            } else {
                echo '<div class="test fail">';
                echo '<span class="error">✗</span> No hay tablas en la base de datos<br><br>';
                echo '<strong>Solución:</strong><br>';
                echo '1. Accede a phpMyAdmin<br>';
                echo '2. Selecciona la base de datos: <code>' . $dbConfig['database'] . '</code><br>';
                echo '3. Click en "Importar"<br>';
                echo '4. Sube el archivo: <code>database-fixed.sql</code><br>';
                echo '</div>';
                exit;
            }
        } catch (PDOException $e) {
            echo '<div class="test fail"><span class="error">✗ Error:</span> ' . $e->getMessage() . '</div>';
            exit;
        }
        
        // Test 4: Verificar tabla users
        echo '<h2>4. Tabla de Usuarios</h2>';
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $userCount = $result['total'];
            
            if ($userCount > 0) {
                echo '<div class="test success"><span class="ok">✓</span> Se encontraron ' . $userCount . ' usuario(s)</div>';
                
                // Verificar usuario admin
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'admin'");
                $stmt->execute();
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($admin) {
                    echo '<div class="test success"><span class="ok">✓</span> Usuario <code>admin</code> existe</div>';
                    echo '<div class="test info">';
                    echo '<strong>Información del usuario admin:</strong><br>';
                    echo 'ID: ' . $admin['id'] . '<br>';
                    echo 'Username: ' . $admin['username'] . '<br>';
                    echo 'Email: ' . $admin['email'] . '<br>';
                    echo 'Full Name: ' . $admin['full_name'] . '<br>';
                    echo 'Role: ' . $admin['role'] . '<br>';
                    echo 'Active: ' . ($admin['active'] ? 'Sí' : 'No') . '<br>';
                    echo 'Password Hash: ' . substr($admin['password_hash'], 0, 20) . '...<br>';
                    echo '</div>';
                    
                    // Verificar que el hash es correcto
                    $testPassword = 'admin123';
                    if (password_verify($testPassword, $admin['password_hash'])) {
                        echo '<div class="test success"><span class="ok">✓</span> Password hash es válido para: <code>admin123</code></div>';
                    } else {
                        echo '<div class="test fail">';
                        echo '<span class="error">✗</span> Password hash NO coincide con <code>admin123</code><br><br>';
                        echo '<strong>Solución - Actualizar password:</strong><br>';
                        echo 'Ejecuta este SQL en phpMyAdmin:<br>';
                        $newHash = password_hash('admin123', PASSWORD_DEFAULT);
                        echo '<pre>UPDATE users SET password_hash = \'' . $newHash . '\' WHERE username = \'admin\';</pre>';
                        echo '</div>';
                    }
                    
                } else {
                    echo '<div class="test fail">';
                    echo '<span class="error">✗</span> Usuario <code>admin</code> NO existe<br><br>';
                    echo '<strong>Solución - Crear usuario admin:</strong><br>';
                    echo 'Ejecuta este SQL en phpMyAdmin:<br>';
                    $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
                    echo '<pre>';
                    echo "INSERT INTO users (username, email, password_hash, full_name, role, active)\n";
                    echo "VALUES ('admin', 'admin@revista.edu', '{$passwordHash}', 'Administrador', 'admin', 1);";
                    echo '</pre>';
                    echo '</div>';
                }
                
            } else {
                echo '<div class="test fail">';
                echo '<span class="error">✗</span> No hay usuarios en la base de datos<br><br>';
                echo '<strong>Solución - Crear usuario admin:</strong><br>';
                echo 'Ejecuta este SQL en phpMyAdmin:<br>';
                $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
                echo '<pre>';
                echo "INSERT INTO users (username, email, password_hash, full_name, role, active)\n";
                echo "VALUES ('admin', 'admin@revista.edu', '{$passwordHash}', 'Administrador', 'admin', 1);";
                echo '</pre>';
                echo '</div>';
            }
            
        } catch (PDOException $e) {
            echo '<div class="test fail">';
            echo '<span class="error">✗ Error al verificar usuarios:</span><br>';
            echo $e->getMessage();
            echo '</div>';
        }
        
        // Test 5: Probar autenticación
        echo '<h2>5. Prueba de Autenticación</h2>';
        
        if (isset($admin) && $admin) {
            require_once '../src/models/Database.php';
            require_once '../src/models/User.php';
            
            try {
                $userModel = new User();
                $loginTest = $userModel->login('admin', 'admin123');
                
                if ($loginTest) {
                    echo '<div class="test success">';
                    echo '<span class="ok">✓</span> Autenticación funciona correctamente<br><br>';
                    echo '<strong>¡El login debería funcionar!</strong><br>';
                    echo 'Usuario: <code>admin</code><br>';
                    echo 'Contraseña: <code>admin123</code><br>';
                    echo '</div>';
                } else {
                    echo '<div class="test fail">';
                    echo '<span class="error">✗</span> La autenticación falló<br><br>';
                    echo 'El modelo de autenticación tiene un problema.';
                    echo '</div>';
                }
            } catch (Exception $e) {
                echo '<div class="test fail">';
                echo '<span class="error">✗ Error en prueba de autenticación:</span><br>';
                echo $e->getMessage();
                echo '</div>';
            }
        }
        
        // Resumen
        echo '<h2>📊 Resumen</h2>';
        echo '<div class="test info">';
        echo '<strong>Si todos los tests pasaron:</strong><br>';
        echo '• Accede a: <a href="login.php">login.php</a><br>';
        echo '• Usuario: <code>admin</code><br>';
        echo '• Contraseña: <code>admin123</code><br><br>';
        echo '<strong>Si hay errores:</strong><br>';
        echo '• Sigue las instrucciones de "Solución" en cada test<br>';
        echo '• Recarga esta página después de corregir<br>';
        echo '</div>';
        ?>
        
        <hr style="margin: 30px 0;">
        <p><small><strong>Nota:</strong> Elimina este archivo después de verificar: <code>rm verificar-bd.php</code></small></p>
    </div>
</body>
</html>
