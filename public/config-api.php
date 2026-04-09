<?php
/**
 * API de Configuración - config-api.php
 * Maneja guardar/cargar configuración de la revista y usuario
 */

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id']) || !$_SESSION['logged_in']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$config = require __DIR__ . '/../config/config.php';
$dbConfig = $config['database'];

try {
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? null;

switch ($action) {
    case 'get_config':
        // Obtener configuración de la revista
        $stmt = $pdo->query("SELECT * FROM journals ORDER BY id DESC LIMIT 1");
        $journal = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'journal' => $journal
        ]);
        break;
        
    case 'save_config':
        // Guardar configuración de la revista
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Datos no válidos']);
            exit;
        }
        
        // Verificar si ya existe un registro
        $stmt = $pdo->query("SELECT id FROM journals LIMIT 1");
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Actualizar registro existente
            $stmt = $pdo->prepare("
                UPDATE journals SET 
                    title = :title,
                    issn_print = :issn_print,
                    issn_electronic = :issn_electronic,
                    doi_prefix = :doi_prefix,
                    publisher = :publisher,
                    publisher_location = :publisher_location,
                    base_url = :base_url,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                'id' => $existing['id'],
                'title' => $input['journal_name'] ?? '',
                'issn_print' => $input['issn_print'] ?? '',
                'issn_electronic' => $input['issn_electronic'] ?? '',
                'doi_prefix' => $input['doi_prefix'] ?? '',
                'publisher' => $input['publisher'] ?? '',
                'publisher_location' => $input['location'] ?? '',
                'base_url' => $input['website'] ?? ''
            ]);
        } else {
            // Crear nuevo registro
            $stmt = $pdo->prepare("
                INSERT INTO journals (
                    title, issn_print, issn_electronic, doi_prefix,
                    publisher, publisher_location, base_url,
                    created_at, updated_at
                ) VALUES (
                    :title, :issn_print, :issn_electronic, :doi_prefix,
                    :publisher, :publisher_location, :base_url,
                    NOW(), NOW()
                )
            ");
            $stmt->execute([
                'title' => $input['journal_name'] ?? '',
                'issn_print' => $input['issn_print'] ?? '',
                'issn_electronic' => $input['issn_electronic'] ?? '',
                'doi_prefix' => $input['doi_prefix'] ?? '',
                'publisher' => $input['publisher'] ?? '',
                'publisher_location' => $input['location'] ?? '',
                'base_url' => $input['website'] ?? ''
            ]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Configuración guardada exitosamente'
        ]);
        break;
        
    case 'change_password':
        // Cambiar contraseña del usuario
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['current_password']) || !isset($input['new_password'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos']);
            exit;
        }
        
        // Verificar contraseña actual
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!password_verify($input['current_password'], $user['password_hash'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Contraseña actual incorrecta']);
            exit;
        }
        
        // Validar nueva contraseña
        if (strlen($input['new_password']) < 8) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'La nueva contraseña debe tener al menos 8 caracteres']);
            exit;
        }
        
        // Actualizar contraseña
        $newHash = password_hash($input['new_password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
        $stmt->execute([
            'hash' => $newHash,
            'id' => $_SESSION['user_id']
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Contraseña cambiada exitosamente'
        ]);
        break;
        
    case 'clean_temp':
        // Limpiar archivos temporales
        $tempDir = __DIR__ . '/uploads/temp';
        $count = 0;
        
        if (is_dir($tempDir)) {
            $files = array_diff(scandir($tempDir), ['.', '..']);
            foreach ($files as $file) {
                $filePath = $tempDir . '/' . $file;
                if (is_file($filePath)) {
                    unlink($filePath);
                    $count++;
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => "Se eliminaron $count archivo(s) temporal(es)"
        ]);
        break;
        
    case 'init_dirs':
        // Inicializar directorios necesarios
        $dirs = [
            __DIR__ . '/uploads',
            __DIR__ . '/uploads/temp',
            __DIR__ . '/articles'
        ];
        
        $created = [];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
                $created[] = basename($dir);
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => count($created) > 0 
                ? 'Directorios creados: ' . implode(', ', $created)
                : 'Todos los directorios ya existen',
            'created' => $created
        ]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}
