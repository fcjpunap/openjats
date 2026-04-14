<?php
/**
 * API REST - Versión Permanente con Bypass de Autenticación
 * Bypass: Solo login/logout/check_auth (evita caché de User.php)
 * Resto: Usa controllers normales
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

// Cargar configuración para funciones de autenticación
$config = require __DIR__ . '/../config/config.php';
$dbConfig = $config['database'];

$action = $_GET['action'] ?? $_POST['action'] ?? null;

// Auto-migrate DB (agregando pagination)
try {
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
    $pdo->exec("ALTER TABLE articles ADD COLUMN pagination VARCHAR(50) DEFAULT NULL AFTER published_date");
} catch(Exception $e) {}

if (!$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No action specified']);
    exit;
}

try {
    switch ($action) {
        // ==================== AUTENTICACIÓN (BYPASS - Sin User.php) ====================
        case 'login':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['username']) || !isset($input['password'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Faltan credenciales']);
                exit;
            }
            
            $username = $input['username'];
            $password = $input['password'];
            
            // Conexión directa (bypass User.php)
            $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
            $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $pdo->prepare(
                "SELECT * FROM users WHERE (username = :identifier OR email = :identifier) AND active = TRUE"
            );
            $stmt->execute(['identifier' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['logged_in'] = true;
                
                $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
                $updateStmt->execute(['id' => $user['id']]);
                
                unset($user['password_hash']);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Login exitoso',
                    'user' => $user
                ]);
            } else {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Usuario o contraseña incorrectos'
                ]);
            }
            break;
            
        case 'logout':
            session_destroy();
            echo json_encode(['success' => true, 'message' => 'Sesión cerrada']);
            break;
            
        case 'check_auth':
            if (isset($_SESSION['user_id']) && $_SESSION['logged_in']) {
                $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
                $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $stmt = $pdo->prepare("SELECT id, username, email, full_name, role FROM users WHERE id = :id");
                $stmt->execute(['id' => $_SESSION['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    echo json_encode([
                        'authenticated' => true,
                        'user' => $user
                    ]);
                } else {
                    echo json_encode(['authenticated' => false]);
                }
            } else {
                echo json_encode(['authenticated' => false]);
            }
            break;
            
        case 'get_oai_url':
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                exit;
            }
            
            $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
            $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $pdo->query("SELECT oai_url FROM journals LIMIT 1");
            $journal = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($journal && !empty($journal['oai_url'])) {
                echo json_encode(['success' => true, 'oai_url' => $journal['oai_url']]);
            } else {
                echo json_encode(['success' => false, 'oai_url' => '']);
            }
            break;
            
        case 'list_templates':
            $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
            $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
            $stmt = $pdo->query("SELECT id, name FROM templates ORDER BY name ASC");
            $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'templates' => $templates]);
            break;
            
        // ==================== ARTÍCULOS (Usa Controllers Normales) ====================
        case 'upload_article':
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                exit;
            }
            
            require_once __DIR__ . '/../src/controllers/ArticleController.php';
            $articleController = new ArticleController();
            $articleController->uploadZip(); // Ya hace echo internamente
            break;
            
        case 'get_article':
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                exit;
            }
            
            $articleId = $_GET['id'] ?? null;
            if (!$articleId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID no especificado']);
                exit;
            }
            
            require_once __DIR__ . '/../src/controllers/ArticleController.php';
            $articleController = new ArticleController();
            $articleController->getArticle($articleId); // Ya hace echo internamente
            break;
            
        case 'list_articles':
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                exit;
            }
            
            require_once __DIR__ . '/../src/controllers/ArticleController.php';
            $articleController = new ArticleController();
            $articleController->listArticles(); // No pasar parámetros, ya hace echo internamente
            break;
            
        case 'save_markup':
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                exit;
            }
            
            require_once __DIR__ . '/../src/controllers/ArticleController.php';
            $articleController = new ArticleController();
            $articleController->saveMarkup(); // Ya hace echo internamente y lee php://input directamente
            break;
            
        case 'list_markup_versions':
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                exit;
            }
            require_once __DIR__ . '/../src/controllers/ArticleController.php';
            $articleController = new ArticleController();
            $articleController->listMarkupVersions();
            break;

        case 'restore_markup_version':
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                exit;
            }
            require_once __DIR__ . '/../src/controllers/ArticleController.php';
            $articleController = new ArticleController();
            $articleController->restoreMarkupVersion();
            break;

        case 'duplicate_article':
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                exit;
            }
            require_once __DIR__ . '/../src/controllers/ArticleController.php';
            $articleController = new ArticleController();
            $articleController->duplicateArticle();
            break;
            
        case 'update_metadata':
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                exit;
            }
            require_once __DIR__ . '/../src/controllers/ArticleController.php';
            $articleController = new ArticleController();
            $articleController->updateMetadata();
            break;
            
        case 'upload_image':
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                exit;
            }
            require_once __DIR__ . '/../src/controllers/ArticleController.php';
            $articleController = new ArticleController();
            $articleController->uploadImage();
            break;
            
        case 'generate_xml':
        case 'generate_scielo':
        case 'generate_redalyc':
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                exit;
            }
            
            $articleId = $_GET['article_id'] ?? $_GET['id'] ?? null;
            if (!$articleId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID no especificado']);
                exit;
            }
            
            if ($action === 'generate_scielo') {
                require_once __DIR__ . '/../src/utils/ScieloGenerator.php';
                $generator = new ScieloGenerator();
                $result = $generator->generateXML($articleId);
            } elseif ($action === 'generate_redalyc') {
                require_once __DIR__ . '/../src/utils/RedalycGenerator.php';
                $generator = new RedalycGenerator();
                $result = $generator->generateXML($articleId);
            } else { // Default to JATSGenerator for 'generate_xml'
                require_once __DIR__ . '/../src/utils/JATSGenerator.php';
                $generator = new JATSGenerator();
                $result = $generator->generateXML($articleId);
            }
            echo json_encode($result);
            break;
            
        case 'generate_pdf':
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                exit;
            }
            
            $articleId = $_GET['article_id'] ?? null;
            if (!$articleId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID no especificado']);
                exit;
            }
            
            require_once __DIR__ . '/../src/utils/PDFGenerator.php';
            $generator = new PDFGenerator();
            
            try {
                $result = $generator->generatePDF($articleId);
                echo json_encode($result);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Error al generar PDF: ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'generate_html':
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                exit;
            }
            
            $articleId = $_GET['article_id'] ?? null;
            if (!$articleId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID no especificado']);
                exit;
            }
            
            require_once __DIR__ . '/../src/utils/HTMLGenerator.php';
            $generator = new HTMLGenerator();
            
            try {
                $result = $generator->generateHTML($articleId);
                echo json_encode($result);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al generar HTML: ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'generate_epub':
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                exit;
            }
            
            $articleId = $_GET['article_id'] ?? null;
            if (!$articleId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID no especificado']);
                exit;
            }
            
            require_once __DIR__ . '/../src/utils/EPUBGenerator.php';
            $generator = new EPUBGenerator();
            
            try {
                $result = $generator->generate($articleId);
                echo json_encode($result);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al generar EPUB: ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'generate_ojs_zip':
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                exit;
            }
            $articleId = $_GET['article_id'] ?? null;
            if (!$articleId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID no especificado']);
                exit;
            }
            
            require_once __DIR__ . '/../src/models/Article.php';
            $articleModel = new Article();
            $articleRow = $articleModel->getById($articleId);
            
            if (!$articleRow) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Artículo no encontrado']);
                exit;
            }
            
            $articleStrId = $articleRow['article_id']; // e.g. 20260323_613683
            
            $articleDirId = $config['paths']['articles'] . $articleId; // source files and images
            $articleDirStr = $config['paths']['articles'] . $articleStrId; // generated XML, HTML, EPUB
            
            if (!is_dir($articleDirStr)) {
                @mkdir($articleDirStr, 0755, true);
            }
            
            // Generar todos los archivos antes de comprimir
            try {
                require_once __DIR__ . '/../src/utils/JATSGenerator.php';
                (new JATSGenerator())->generateXML($articleId);
                
                require_once __DIR__ . '/../src/utils/ScieloGenerator.php';
                (new ScieloGenerator())->generateXML($articleId);
                
                require_once __DIR__ . '/../src/utils/RedalycGenerator.php';
                (new RedalycGenerator())->generateXML($articleId);
                
                require_once __DIR__ . '/../src/utils/HTMLGenerator.php';
                (new HTMLGenerator())->generateHTML($articleId);
                
                require_once __DIR__ . '/../src/utils/PDFGenerator.php';
                (new PDFGenerator())->generatePDF($articleId);
                
                require_once __DIR__ . '/../src/utils/EPUBGenerator.php';
                (new EPUBGenerator())->generate($articleId);
            } catch (Exception $e) {
                // Si alguno falla, continuamos y empaquetamos lo que se haya podido generar
            }
            
            $zipFilename = "ojs_export_{$articleId}.zip";
            $zipPath = $articleDirStr . '/' . $zipFilename;
            
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                echo json_encode(['success' => false, 'message' => 'No se pudo crear el archivo ZIP']);
                exit;
            }
            
            // Loop array of directories (just in case they are exactly the same, avoid duplicating)
            $dirsToScan = array_unique([$articleDirId, $articleDirStr]);
            
            foreach ($dirsToScan as $scanDir) {
                if (!is_dir($scanDir)) continue;
                
                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($scanDir));
                foreach ($iterator as $file) {
                    if ($file->isDir()) continue;
                    $filename = $file->getFilename();
                    $pathname = $file->getPathname();
                    $ext = strtolower($file->getExtension());
                    
                    // Skip the zip itself
                    if ($filename === $zipFilename || $filename === 'original.zip') continue;
                    // Skip article-print.html generated by PDF
                    if ($filename === 'article-print.html') continue;
                    // Skip files in 'source' folder
                    if (strpos($pathname, '/source/') !== false) continue;
                    
                    // Add valid output XML, HTML, PDF, EPUB and images
                    if (in_array($ext, ['xml', 'html', 'pdf', 'epub', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'])) {
                        
                        if ($filename === 'index.html' || in_array($filename, ['article.xml', 'scielo.xml', 'redalyc.xml'])) {
                            // Reescribir URLs para OJS en HTML y XML
                            $content = file_get_contents($pathname);
                            // Convertir URLs absolutas y relativas de imágenes a nombres planos
                            // Atrapa src="..." para HTML y xlink:href="..." para XML JATS
                            $content = preg_replace('/(src|xlink:href)=["\'][^"\']*articles\/[a-zA-Z0-9_]+\/([^"\'\/]+\.(png|jpg|jpeg|gif|svg|webp))["\']/i', '$1="$2"', $content);
                            
                            // Reemplazar logos específicos en HTML
                            $content = str_replace('https://fcjp.derecho.unap.edu.pe/catg/jats-assistant/public/journal.jpeg', 'journal.jpeg', $content);
                            $content = str_replace('https://fcjp.derecho.unap.edu.pe/catg/jats-assistant/public/logofcjp.png', 'logofcjp.png', $content);
                            
                            $zip->addFromString($filename, $content);
                        } else {
                            // Guardar en la raíz del ZIP evitando carpetas internas
                            $zip->addFile($pathname, $filename);
                        }
                    }
                }
            }
            
            // Adjuntar logos estáticos desde la carpeta public
            if (file_exists(__DIR__ . '/journal.jpeg')) {
                $zip->addFile(__DIR__ . '/journal.jpeg', 'journal.jpeg');
            }
            if (file_exists(__DIR__ . '/logofcjp.png')) {
                $zip->addFile(__DIR__ . '/logofcjp.png', 'logofcjp.png');
            }
            
            $zip->close();
            
            echo json_encode([
                'success' => true,
                'download_url' => 'articles/' . $articleStrId . '/' . $zipFilename
            ]);
            break;
            
        default:
            http_response_code(404);
            echo json_encode([
                'success' => false, 
                'error' => 'Action not found: ' . $action
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
