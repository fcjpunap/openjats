<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
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
    echo json_encode(['success' => false, 'message' => 'Error BD']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'create_issue') {
    $vol = trim($_POST['volume'] ?? '');
    $year = trim($_POST['year'] ?? '');
    $issue = trim($_POST['issue'] ?? '');
    
    if(!$vol || !$year || !$issue) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos']);
        exit;
    }
    
    try {
        $stmt = $pdo->query("SELECT id FROM journals LIMIT 1");
        $j = $stmt->fetch();
        $jid = $j ? $j['id'] : 1;
        
        $stmt = $pdo->prepare("SELECT id FROM volumes WHERE volume_number = ? AND journal_id = ?");
        $stmt->execute([$vol, $jid]);
        $v = $stmt->fetch();
        
        if(!$v) {
            $stmt = $pdo->prepare("INSERT INTO volumes (journal_id, volume_number, year) VALUES (?, ?, ?)");
            $stmt->execute([$jid, $vol, $year]);
            $vid = $pdo->lastInsertId();
        } else {
            $vid = $v['id'];
        }
        
        $stmt = $pdo->prepare("INSERT INTO issues (volume_id, issue_number) VALUES (?, ?)");
        $stmt->execute([$vid, $issue]);
        
        echo json_encode(['success' => true]);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($action === 'list_issues') {
    try {
        $stmt = $pdo->query("
            SELECT i.id as issue_id, i.issue_number, v.volume_number, v.year, j.title 
            FROM issues i 
            JOIN volumes v ON i.volume_id = v.id 
            JOIN journals j ON v.journal_id = j.id
            ORDER BY i.id DESC
        ");
        echo json_encode(['success' => true, 'issues' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($action === 'assign_issue') {
    $articleId = $_POST['article_id'] ?? '';
    $issueId = empty($_POST['issue_id']) ? null : $_POST['issue_id'];
    $articleType = empty($_POST['article_type']) ? null : $_POST['article_type'];
    
    try {
        // Asegurar que la columna tenga suficiente espacio para nombres de sección largos
        $pdo->exec("ALTER TABLE articles MODIFY article_type VARCHAR(255)");
        
        $stmt = $pdo->prepare("UPDATE articles SET issue_id = ?, article_type = ? WHERE id = ?");
        $stmt->execute([$issueId, $articleType, $articleId]);
        echo json_encode(['success' => true]);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($action === 'create_section') {
    $title = trim($_POST['title'] ?? '');
    if(!$title) {
        echo json_encode(['success' => false, 'message' => 'Falta el nombre']);
        exit;
    }
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS journal_sections (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL UNIQUE)");
        $stmt = $pdo->prepare("INSERT IGNORE INTO journal_sections (title) VALUES (?)");
        $stmt->execute([$title]);
        echo json_encode(['success' => true]);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($action === 'list_sections') {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS journal_sections (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL UNIQUE)");
        $stmt = $pdo->query("SELECT * FROM journal_sections ORDER BY title ASC");
        echo json_encode(['success' => true, 'sections' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($action === 'delete_issue') {
    $id = intval($_POST['id'] ?? 0);
    try {
        $pdo->query("DELETE FROM issues WHERE id = $id");
        $pdo->query("UPDATE articles SET issue_id = NULL WHERE issue_id = $id");
        echo json_encode(['success' => true]);
    } catch(Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
} elseif ($action === 'delete_section') {
    $id = intval($_POST['id'] ?? 0);
    try {
        $pdo->query("DELETE FROM journal_sections WHERE id = $id");
        // Podríamos también limpiar el string en article_type, pero es texto plano.
        echo json_encode(['success' => true]);
    } catch(Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
} elseif ($action === 'update_issue') {
    $id = intval($_POST['id'] ?? 0);
    $vol = trim($_POST['volume'] ?? '');
    $iss = trim($_POST['issue'] ?? '');
    $year = trim($_POST['year'] ?? '');
    if (!$vol || !$iss || !$year) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos']);
        exit;
    }
    try {
        // En issues y volumes actualizamos
        $stmt = $pdo->query("SELECT volume_id FROM issues WHERE id = $id");
        $v = $stmt->fetch();
        if ($v) {
            $vid = $v['volume_id'];
            $pdo->prepare("UPDATE volumes SET volume_number = ?, year = ? WHERE id = ?")->execute([$vol, $year, $vid]);
            $pdo->prepare("UPDATE issues SET issue_number = ? WHERE id = ?")->execute([$iss, $id]);
        }
        echo json_encode(['success' => true]);
    } catch(Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
} elseif ($action === 'assign_template') {
    $articleId = intval($_POST['article_id'] ?? 0);
    $templateId = empty($_POST['template_id']) ? null : intval($_POST['template_id']);
    try {
        $stmt = $pdo->prepare("UPDATE articles SET template_id = ? WHERE id = ?");
        $stmt->execute([$templateId, $articleId]);
        echo json_encode(['success' => true]);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($action === 'update_section') {
    $id = intval($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    if (!$title) {
        echo json_encode(['success' => false, 'message' => 'Falta título']);
        exit;
    }
    try {
        $pdo->prepare("UPDATE journal_sections SET title = ? WHERE id = ?")->execute([$title, $id]);
        echo json_encode(['success' => true]);
    } catch(Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
} elseif ($action === 'delete_article') {
    $id = intval($_POST['id'] ?? 0);
    try {
        $pdo->query("DELETE FROM articles WHERE id = $id");
        $dir = __DIR__ . '/articles/' . $id;
        if (is_dir($dir)) {
            exec("rm -rf " . escapeshellarg($dir));
        }
        echo json_encode(['success' => true]);
    } catch(Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
} elseif ($action === 'harvest_oai') {
    $oai_url = filter_var($_POST['oai_url'] ?? '', FILTER_VALIDATE_URL);
    if(!$oai_url) {
        echo json_encode(['success' => false, 'message' => 'URL de OAI inválida']);
        exit;
    }
    
    if(!str_ends_with(strtolower($oai_url), 'oai')) {
        $oai_url = rtrim($oai_url, '/') . '/oai';
    }

    try {
        // Paso 1: Cosechar las Secciones (Sets)
        $setsUrl = $oai_url . '?verb=ListSets';
        $setsXmlData = @file_get_contents($setsUrl);
        $setsMap = [];
        if ($setsXmlData) {
            $setsXml = new SimpleXMLElement($setsXmlData);
            $setsXml->registerXPathNamespace('oai', 'http://www.openarchives.org/OAI/2.0/');
            $setNodes = $setsXml->xpath('//oai:set');
            if ($setNodes) {
                foreach ($setNodes as $setNode) {
                    $spec = (string)$setNode->setSpec;
                    $name = (string)$setNode->setName;
                    if ($spec && $name) {
                        $setsMap[$spec] = $name;
                    }
                }
            }
        }

        // Paso 2: Cosechar los Registros
        $token = $_POST['resumptionToken'] ?? '';
        if ($token) {
            $apiUrl = $oai_url . '?verb=ListRecords&resumptionToken=' . urlencode($token);
        } else {
            $apiUrl = $oai_url . '?verb=ListRecords&metadataPrefix=oai_dc';
        }
        $xmlData = @file_get_contents($apiUrl);
        if(!$xmlData) throw new Exception("No se pudo conectar al endpoint OAI. Verifique la URL.");

        $xml = new SimpleXMLElement($xmlData);
        $xml->registerXPathNamespace('oai', 'http://www.openarchives.org/OAI/2.0/');
        $records = $xml->xpath('//oai:record');
        if(!$records) throw new Exception("No se encontraron registros en el OAI.");

        $countArts = 0;
        $countIssues = 0;
        $countSections = 0;

        foreach($records as $rec) {
            $identifier = (string)$rec->header->identifier;
            
            // Extraer la sección desde setSpec
            $sectionType = 'Artículos';
            if (isset($rec->header->setSpec)) {
                foreach($rec->header->setSpec as $specNode) {
                    $spec = (string)$specNode;
                    if ($spec != 'driver' && isset($setsMap[$spec])) {
                        $sectionType = $setsMap[$spec];
                        break;
                    }
                }
            }

            $dcNodes = clone $rec->metadata->children('http://www.openarchives.org/OAI/2.0/oai_dc/')->dc;
            if(!$dcNodes) continue;
            
            $dcNodes->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
            
            $titleNode = $dcNodes->xpath('.//dc:title');
            $title = $titleNode ? (string)$titleNode[0] : 'Sin Título';
            
            $dateNode = $dcNodes->xpath('.//dc:date');
            $dateStr = $dateNode ? (string)$dateNode[0] : '';
            
            // Auto-crear la sección
            if ($sectionType) {
                $pdo->exec("CREATE TABLE IF NOT EXISTS journal_sections (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL UNIQUE)");
                $stmtSec = $pdo->prepare("INSERT IGNORE INTO journal_sections (title) VALUES (?)");
                $stmtSec->execute([$sectionType]);
                if($stmtSec->rowCount() > 0) $countSections++;
            }
            
            $sourceNode = $dcNodes->xpath('.//dc:source');
            $source = [];
            if ($sourceNode) {
                foreach ($sourceNode as $sn) {
                    $source[] = (string)$sn;
                }
            }
            $sourceStr = implode(" || ", $source);
            
            $volume = '';
            $issue = '';
            $year = '';
            // Regex mejorado para capturar Vol., Volumen, v.
            if(preg_match('/(?:Volumen|Vol|v)\.?\s*(\d+)/i', $sourceStr, $m)) $volume = $m[1];
            
            // Regex para capturar Número, Núm, Num, Nro, No, N°, etc.
            if(preg_match('/(?:Número|N\x{00FA}mero|N\x{00B0}|N\x{00BA}|\x{00BA}|Núm|Num|Nro|No|N(?:\x{00B0}|º)|N\.)\.?\s*(\d+)/ui', $sourceStr, $m)) $issue = $m[1];
            
            // Extraer el año prioritariamente de la fuente (ejemplo: Vol. 1 Núm. 2 (2014)) que es el año real de la revista
            if(preg_match('/\((\d{4})\)/', $sourceStr, $m)) $year = $m[1];
            elseif ($dateStr && preg_match('/^(\d{4})/', $dateStr, $m)) $year = $m[1];
            elseif(preg_match('/(?:19|20)\d{2}/', $sourceStr, $m)) $year = $m[0];
            
            if (!$volume && $issue) $volume = 'S/V'; // Assign default if missing
            if (!$issue && $volume) $issue = 'S/N'; // Assign default if missing

            $issue_id = null;
            if ($volume && $year && $issue) {
                $stmt = $pdo->prepare("SELECT id FROM volumes WHERE volume_number = ?");
                $stmt->execute([$volume]);
                $v = $stmt->fetch();
                if(!$v) {
                    $pdo->prepare("INSERT INTO volumes (journal_id, volume_number, year) VALUES (1, ?, ?)")->execute([$volume, $year]);
                    $vid = $pdo->lastInsertId();
                } else {
                    $vid = $v['id'];
                }
                
                $stmt = $pdo->prepare("SELECT id FROM issues WHERE volume_id = ? AND issue_number = ?");
                $stmt->execute([$vid, $issue]);
                $issObj = $stmt->fetch();
                if(!$issObj) {
                    $pdo->prepare("INSERT INTO issues (volume_id, issue_number) VALUES (?, ?)")->execute([$vid, $issue]);
                    $issue_id = $pdo->lastInsertId();
                    $countIssues++;
                } else {
                    $issue_id = $issObj['id'];
                }
            }
            
            $strIdStr = substr(md5($identifier), 0, 15);
            $stmt = $pdo->prepare("SELECT id FROM articles WHERE article_id = ? OR title = ?");
            $stmt->execute([$strIdStr, $title]);
            if(!$stmt->fetch()) {
                $status = 'processing';
                $pdo->exec("ALTER TABLE articles MODIFY article_type VARCHAR(255)");
                
                $pdo->prepare("INSERT INTO articles (article_id, title, issue_id, article_type, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())")
                    ->execute([$strIdStr, $title, $issue_id, $sectionType, $status]);
                $countArts++;
            }
        }
        
        $tokenNode = $xml->xpath('//oai:resumptionToken');
        $nextToken = ($tokenNode && (string)$tokenNode[0] !== '') ? (string)$tokenNode[0] : null;

        echo json_encode(['success' => true, 'message' => "Cosechados: $countArts manuscritos nuevos, $countIssues fascículos nuevos, $countSections secciones nuevas.", 'resumptionToken' => $nextToken]);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($action === 'clean_oai') {
    // Added to force clean via admin frontend
    try {
        // El harvester genera article_ids como substr(md5(), 0, 15) que no tienen guion bajo '_' y son de 15 chars.
        // Los envíos manuales y de editor tienen formatos como '2026xxxx_xxxxxx'.
        $stmtArt = $pdo->query("DELETE FROM articles WHERE status = 'processing' AND LENGTH(article_id) = 15 AND article_id NOT LIKE '\_%' ESCAPE '\\\\' AND article_id NOT LIKE '%\_%' ESCAPE '\\\\'");
        
        // Collation safe delete for sections using explicit COLLATE
        $stmtSec = $pdo->query("DELETE FROM journal_sections WHERE title COLLATE utf8mb4_unicode_ci NOT IN (SELECT DISTINCT article_type COLLATE utf8mb4_unicode_ci FROM articles WHERE article_type IS NOT NULL)");
        
        // Remove empty issues/volumes
        $pdo->query("DELETE FROM issues WHERE id NOT IN (SELECT DISTINCT issue_id FROM articles WHERE issue_id IS NOT NULL)");
        $pdo->query("DELETE FROM volumes WHERE id NOT IN (SELECT DISTINCT volume_id FROM issues)");
        
        echo json_encode(['success' => true, 'message' => 'Limpio. Arts eliminados: '.$stmtArt->rowCount().', Secs vacías borradas: '.($stmtSec->rowCount() ?? 0)]);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
