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
    <title>Diagnóstico API - Ver Respuesta RAW</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .test { padding: 15px; margin: 10px 0; border-radius: 6px; }
        .ok { background: #d1fae5; color: #065f46; }
        .fail { background: #fee2e2; color: #991b1b; }
        .info { background: #dbeafe; color: #1e40af; }
        pre { background: #1f2937; color: #f3f4f6; padding: 15px; border-radius: 6px; overflow-x: auto; white-space: pre-wrap; word-wrap: break-word; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; background: #2563eb; color: white; border: none; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico API - Ver Respuesta RAW</h1>
    
    <h2>Test 1: Ver respuesta RAW de api.php</h2>
    <button onclick="testRaw('check_auth')">check_auth</button>
    <button onclick="testRaw('list_articles')">list_articles</button>
    <div id="rawResult"></div>
    
    <h2>Test 2: Verificar que api.php existe</h2>
    <?php
    if (file_exists(__DIR__ . '/api.php')) {
        echo '<div class="test ok">✓ api.php existe</div>';
        echo '<p>Tamaño: ' . filesize(__DIR__ . '/api.php') . ' bytes</p>';
        echo '<p>Última modificación: ' . date('Y-m-d H:i:s', filemtime(__DIR__ . '/api.php')) . '</p>';
    } else {
        echo '<div class="test fail">✗ api.php NO EXISTE</div>';
    }
    ?>
    
    <h2>Test 3: Ver primeras líneas de api.php</h2>
    <?php
    if (file_exists(__DIR__ . '/api.php')) {
        $lines = file(__DIR__ . '/api.php');
        echo '<pre>';
        echo htmlspecialchars(implode('', array_slice($lines, 0, 20)));
        echo "\n...\n(primeras 20 líneas)";
        echo '</pre>';
    }
    ?>
    
    <h2>Test 4: Verificar errores de PHP</h2>
    <div class="test info">
        Verifica los logs de error de PHP en tu servidor para ver si api.php tiene errores de sintaxis.
    </div>
    
    <h2>Test 5: Acceso directo</h2>
    <div class="test info">
        <p>Prueba acceder directamente a:</p>
        <a href="api.php?action=check_auth" target="_blank">api.php?action=check_auth</a><br>
        <a href="api.php?action=list_articles" target="_blank">api.php?action=list_articles</a>
        <p>Deberían mostrar JSON, NO HTML ni errores de PHP</p>
    </div>
    
    <script>
        async function testRaw(action) {
            const resultDiv = document.getElementById('rawResult');
            resultDiv.innerHTML = `<div class="test info">Probando ${action}...</div>`;
            
            try {
                const response = await fetch(`api.php?action=${action}`);
                const text = await response.text(); // Obtener como texto, NO como JSON
                
                let isJSON = false;
                let parsed = null;
                
                try {
                    parsed = JSON.parse(text);
                    isJSON = true;
                } catch (e) {
                    isJSON = false;
                }
                
                if (isJSON) {
                    resultDiv.innerHTML = `
                        <div class="test ok">
                            <h3>✓ ${action} retorna JSON válido</h3>
                            <p><strong>Status HTTP:</strong> ${response.status}</p>
                            <p><strong>Content-Type:</strong> ${response.headers.get('content-type')}</p>
                            <details>
                                <summary>Ver JSON parseado</summary>
                                <pre>${JSON.stringify(parsed, null, 2)}</pre>
                            </details>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="test fail">
                            <h3>✗ ${action} NO retorna JSON válido</h3>
                            <p><strong>Status HTTP:</strong> ${response.status}</p>
                            <p><strong>Content-Type:</strong> ${response.headers.get('content-type')}</p>
                            <p><strong>Problema:</strong> La respuesta no es JSON. Probablemente es HTML de un error de PHP.</p>
                            <details open>
                                <summary>Ver respuesta RAW (lo que realmente retorna)</summary>
                                <pre>${text.substring(0, 5000)}${text.length > 5000 ? '\n... (truncado)' : ''}</pre>
                            </details>
                            <p><strong>Solución:</strong></p>
                            <ul>
                                <li>Si ves un error de PHP, corrígelo en api.php</li>
                                <li>Si ves HTML, verifica que api.php tenga el código correcto</li>
                                <li>Revisa los logs de error de PHP en el servidor</li>
                            </ul>
                        </div>
                    `;
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="test fail">
                        <h3>✗ Error de red/fetch</h3>
                        <p>${error.message}</p>
                    </div>
                `;
            }
        }
        
        // Test automático al cargar
        window.addEventListener('load', () => {
            testRaw('check_auth');
        });
    </script>
</body>
</html>
