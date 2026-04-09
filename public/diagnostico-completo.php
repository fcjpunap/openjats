<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo "No hay sesión. <a href='login-directo.php'>Login</a>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico Completo</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .test { padding: 15px; margin: 10px 0; border-radius: 6px; }
        .ok { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .fail { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        .info { background: #dbeafe; color: #1e40af; border-left: 4px solid #2563eb; }
        pre { background: #1f2937; color: #f3f4f6; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; background: #2563eb; color: white; border: none; border-radius: 4px; }
        button:hover { background: #1e40af; }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico Completo del Sistema</h1>
    
    <h2>1. Sesión</h2>
    <div class="test ok">
        ✓ Sesión activa<br>
        Usuario: <?php echo $_SESSION['username']; ?><br>
        ID: <?php echo $_SESSION['user_id']; ?>
    </div>
    
    <h2>2. Archivos PHP Críticos</h2>
    <?php
    $files = [
        'api.php' => __DIR__ . '/api.php',
        'config-api.php' => __DIR__ . '/config-api.php',
        'login-directo.php' => __DIR__ . '/login-directo.php',
        'configuracion.php' => __DIR__ . '/configuracion.php',
        'editor.php' => __DIR__ . '/editor.php',
        'index.php' => __DIR__ . '/index.php'
    ];
    
    foreach ($files as $name => $path) {
        if (file_exists($path)) {
            echo "<div class='test ok'>✓ $name existe (" . filesize($path) . " bytes)</div>";
        } else {
            echo "<div class='test fail'>✗ $name NO EXISTE en: $path</div>";
        }
    }
    ?>
    
    <h2>3. Directorios</h2>
    <?php
    $dirs = [
        'uploads' => __DIR__ . '/uploads',
        'uploads/temp' => __DIR__ . '/uploads/temp',
        'articles' => __DIR__ . '/articles',
        'css' => __DIR__ . '/css',
        'js' => __DIR__ . '/js'
    ];
    
    foreach ($dirs as $name => $path) {
        if (is_dir($path)) {
            $perms = substr(sprintf('%o', fileperms($path)), -4);
            $writable = is_writable($path) ? 'escribible' : 'NO escribible';
            echo "<div class='test ok'>✓ $name existe (permisos: $perms, $writable)</div>";
        } else {
            echo "<div class='test fail'>✗ $name NO EXISTE: $path</div>";
        }
    }
    ?>
    
    <h2>4. Base de Datos</h2>
    <?php
    try {
        $config = require __DIR__ . '/../config/config.php';
        $db = $config['database'];
        $pdo = new PDO("mysql:host={$db['host']};dbname={$db['database']}", $db['username'], $db['password']);
        echo "<div class='test ok'>✓ Conexión a BD exitosa</div>";
        
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<div class='test ok'>✓ Tablas encontradas: " . count($tables) . "<br><small>" . implode(', ', $tables) . "</small></div>";
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM articles");
        $count = $stmt->fetchColumn();
        echo "<div class='test info'>ℹ Artículos en BD: $count</div>";
        
    } catch (Exception $e) {
        echo "<div class='test fail'>✗ Error BD: " . $e->getMessage() . "</div>";
    }
    ?>
    
    <h2>5. Pruebas de API</h2>
    
    <button onclick="testAPI('check_auth')">Test check_auth</button>
    <button onclick="testAPI('get_config')">Test get_config (config-api)</button>
    <button onclick="testAPI('list_articles')">Test list_articles</button>
    
    <div id="apiResults"></div>
    
    <h2>6. Prueba de Upload</h2>
    <input type="file" id="testFile" accept=".zip">
    <button onclick="testUpload()">Probar Upload</button>
    <div id="uploadResults"></div>
    
    <h2>7. JavaScript</h2>
    <div class="test info">
        <button onclick="alert('JavaScript funciona')">Test JS</button>
        <button onclick="testFetch()">Test Fetch API</button>
    </div>
    <div id="jsResults"></div>
    
    <h2>8. Logs de Consola</h2>
    <div class="test info">
        Abre la consola del navegador (F12) para ver errores JavaScript
    </div>
    
    <script>
        async function testAPI(action) {
            const results = document.getElementById('apiResults');
            results.innerHTML = `<div class="test info">Probando ${action}...</div>`;
            
            try {
                let url = action === 'get_config' 
                    ? `config-api.php?action=${action}` 
                    : `api.php?action=${action}`;
                
                const response = await fetch(url);
                const data = await response.json();
                
                results.innerHTML = `
                    <div class="test ok">
                        <strong>✓ ${action} funciona</strong><br>
                        Status: ${response.status}<br>
                        <details>
                            <summary>Ver respuesta JSON</summary>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </details>
                    </div>
                `;
            } catch (error) {
                results.innerHTML = `
                    <div class="test fail">
                        <strong>✗ ${action} falló</strong><br>
                        Error: ${error.message}
                    </div>
                `;
            }
        }
        
        async function testUpload() {
            const fileInput = document.getElementById('testFile');
            const results = document.getElementById('uploadResults');
            
            if (!fileInput.files[0]) {
                results.innerHTML = '<div class="test fail">✗ Selecciona un archivo .zip</div>';
                return;
            }
            
            results.innerHTML = '<div class="test info">Subiendo archivo...</div>';
            
            const formData = new FormData();
            formData.append('article_zip', fileInput.files[0]);
            
            try {
                const response = await fetch('api.php?action=upload_article', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    results.innerHTML = `
                        <div class="test ok">
                            <strong>✓ Upload exitoso</strong><br>
                            Article ID: ${data.article_id || 'N/A'}<br>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;
                } else {
                    results.innerHTML = `
                        <div class="test fail">
                            <strong>✗ Upload falló</strong><br>
                            ${data.message || 'Error desconocido'}<br>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;
                }
            } catch (error) {
                results.innerHTML = `
                    <div class="test fail">
                        <strong>✗ Error de conexión</strong><br>
                        ${error.message}
                    </div>
                `;
            }
        }
        
        async function testFetch() {
            const results = document.getElementById('jsResults');
            try {
                const response = await fetch('api.php?action=check_auth');
                const data = await response.json();
                results.innerHTML = '<div class="test ok">✓ Fetch API funciona</div>';
            } catch (error) {
                results.innerHTML = `<div class="test fail">✗ Fetch falló: ${error.message}</div>`;
            }
        }
        
        // Log de todos los errores
        window.addEventListener('error', function(e) {
            console.error('Error capturado:', e.message, e.filename, e.lineno);
        });
    </script>
    
    <hr style="margin: 30px 0;">
    <p><a href="index.php">← Volver al Dashboard</a></p>
</body>
</html>
