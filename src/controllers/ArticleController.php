<?php
/**
 * Controlador ArticleController - Gestión de artículos y procesamiento
 */

require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../utils/HTMLProcessor.php';

class ArticleController {
    private $articleModel;
    private $config;
    
    public function __construct() {
        $this->articleModel = new Article();
        $this->config = require __DIR__ . '/../../config/config.php';
    }
    
    /**
     * Subir y procesar archivo ZIP
     */
    public function uploadZip() {
        header('Content-Type: application/json');
        
        if (!isset($_FILES['article_zip'])) {
            echo json_encode(['success' => false, 'message' => 'No se recibió archivo']);
            return;
        }
        
        $file = $_FILES['article_zip'];
        
        // Validar archivo
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Error al subir archivo']);
            return;
        }
        
        if ($file['size'] > $this->config['upload']['max_size']) {
            echo json_encode(['success' => false, 'message' => 'Archivo muy grande']);
            return;
        }
        
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $this->config['upload']['allowed_types'])) {
            echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido']);
            return;
        }
        
        // Crear directorio temporal
        $tempDir = $this->config['paths']['temp'] . uniqid('article_');
        if (!mkdir($tempDir, 0755, true)) {
            echo json_encode(['success' => false, 'message' => 'Error al crear directorio temporal']);
            return;
        }
        
        // Extraer ZIP
        $zip = new ZipArchive();
        if ($zip->open($file['tmp_name']) !== true) {
            echo json_encode(['success' => false, 'message' => 'Error al abrir archivo ZIP']);
            return;
        }
        
        $zip->extractTo($tempDir);
        $zip->close();
        
        // Buscar archivo HTML principal
        $htmlFile = $this->findMainHTML($tempDir);
        
        if (!$htmlFile) {
            $this->cleanupTemp($tempDir);
            echo json_encode(['success' => false, 'message' => 'No se encontró archivo HTML en el ZIP']);
            return;
        }
        
        // Procesar HTML
        $htmlContent = file_get_contents($htmlFile);
        $processor = new HTMLProcessor($htmlContent);
        $extractedData = $processor->processDocument();
        
        $articleData = [
            'status' => 'processing',
            'uploaded_by' => $_SESSION['user_id'] ?? null,
            'issue_id' => !empty($_POST['issue_id']) ? $_POST['issue_id'] : null,
        ];
        
        $existingArticleId = !empty($_POST['existing_article_id']) ? intval($_POST['existing_article_id']) : null;
        
        if ($existingArticleId) {
            $articleId = $existingArticleId;
            
            $existingMeta = $this->articleModel->getById($articleId);
            
            $updateData = [
                'status' => 'processing',
                'uploaded_by' => $_SESSION['user_id'] ?? null,
            ];
            
            // Update base metadata ONLY if it is currently empty or if extracted data is very rich
            if (empty($existingMeta['title']) && !empty($extractedData['metadata']['title'])) {
                $updateData['title'] = $extractedData['metadata']['title'];
            }
            if (empty($existingMeta['title_en']) && !empty($extractedData['metadata']['title_en'])) {
                $updateData['title_en'] = $extractedData['metadata']['title_en'];
            }
            
            $absEs = $extractedData['abstract']['es'] ?? null;
            if ($absEs && empty($existingMeta['abstract'])) $updateData['abstract'] = $absEs;
            
            $absEn = $extractedData['abstract']['en'] ?? null;
            if ($absEn && empty($existingMeta['abstract_en'])) $updateData['abstract_en'] = $absEn;
            
            $this->articleModel->update($articleId, $updateData);
            
            // Only delete and overwrite Authors/Affiliations/References IF the extracted HTML actually found them
            // Otherwise, we keep the duplicated/existing ones intact!
            if (!empty($extractedData['authors'])) {
                $this->articleModel->deleteAuthors($articleId);
                foreach ($extractedData['authors'] as $author) {
                    $author['article_id'] = $articleId;
                    $this->articleModel->addAuthor($articleId, $author);
                }
            }
            if (!empty($extractedData['affiliations'])) {
                $this->articleModel->deleteAffiliations($articleId);
                foreach ($extractedData['affiliations'] as $aff) {
                    $aff['article_id'] = $articleId;
                    $this->articleModel->addAffiliation($articleId, $aff);
                }
            }
            if (!empty($extractedData['references'])) {
                $this->articleModel->deleteReferences($articleId);
                foreach ($extractedData['references'] as $ref) {
                    $ref['article_id'] = $articleId;
                    $this->articleModel->addReference($ref);
                }
            }
            
            // ALways replace HTML structural data (Sections, Tables, Figures)
            $this->articleModel->deleteSections($articleId);
            $this->articleModel->deleteTables($articleId);
            $this->articleModel->deleteFigures($articleId);
            
            foreach ($extractedData['sections'] as $section) {
                $section['article_id'] = $articleId;
                $this->articleModel->addSection($section);
            }
            foreach ($extractedData['tables'] as $table) {
                $table['article_id'] = $articleId;
                $this->articleModel->addTable($table);
            }
            foreach ($extractedData['figures'] as $figure) {
                $figure['article_id'] = $articleId;
                $this->articleModel->addFigure($figure);
            }
            
            // We empty out extracted data arrays so `saveExtractedData` doesn't double insert
            $extractedData['authors'] = [];
            $extractedData['affiliations'] = [];
            $extractedData['sections'] = [];
            $extractedData['tables'] = [];
            $extractedData['figures'] = [];
            $extractedData['references'] = [];
            
        } else {
            // Crear artículo en BD
            $articleData['title'] = $extractedData['metadata']['title'] ?? 'Sin título';
            $articleData['title_en'] = $extractedData['metadata']['title_en'] ?? null;
            $articleData['abstract'] = $extractedData['abstract']['es'] ?? null;
            $articleData['abstract_en'] = $extractedData['abstract']['en'] ?? null;
            $articleData['keywords'] = implode(', ', $extractedData['keywords']['es'] ?? []);
            $articleData['keywords_en'] = implode(', ', $extractedData['keywords']['en'] ?? []);
            $articleData['received_date'] = $extractedData['metadata']['received_date'] ?? null;
            $articleData['accepted_date'] = $extractedData['metadata']['accepted_date'] ?? null;
            $articleData['published_date'] = $extractedData['metadata']['published_date'] ?? null;
            
            $result = $this->articleModel->create($articleData);
            
            if (!$result['success']) {
                $this->cleanupTemp($tempDir);
                echo json_encode($result);
                return;
            }
            $articleId = $result['article_id'];
        }
        
        // Mover archivos a directorio permanente
        $articleDir = $this->config['paths']['articles'] . $articleId;
        if (!is_dir($articleDir)) {
            if (!mkdir($articleDir, 0755, true)) {
                $this->cleanupTemp($tempDir);
                echo json_encode(['success' => false, 'message' => 'Error al crear directorio del artículo']);
                return;
            }
        }
        
        $this->moveDirectory($tempDir, $articleDir . '/source');
        
        // Guardar archivo ZIP original
        $zipPath = $articleDir . '/original.zip';
        move_uploaded_file($file['tmp_name'], $zipPath);
        
        $this->articleModel->addFile([
            'article_id' => $articleId,
            'file_type' => 'source_zip',
            'file_path' => $zipPath,
            'file_size' => $file['size'],
            'mime_type' => 'application/zip',
        ]);
        
        // Guardar HTML
        $htmlPath = $articleDir . '/source/article.html';
        copy($htmlFile, $htmlPath);
        
        $this->articleModel->addFile([
            'article_id' => $articleId,
            'file_type' => 'html',
            'file_path' => $htmlPath,
            'mime_type' => 'text/html',
        ]);
        
        // Guardar datos extraídos
        $this->saveExtractedData($articleId, $extractedData);
        
        // Limpiar temporal
        $this->cleanupTemp($tempDir);
        
        echo json_encode([
            'success' => true,
            'article_id' => $articleId,
            'extracted_data' => $extractedData,
        ]);
    }
    
    /**
     * Guardar datos extraídos en la base de datos
     */
    private function saveExtractedData($articleId, $data) {
        // Guardar autores
        foreach ($data['authors'] as $author) {
            $author['article_id'] = $articleId;
            $this->articleModel->addAuthor($articleId, $author);
        }
        
        // Guardar afiliaciones
        foreach ($data['affiliations'] as $aff) {
            $aff['article_id'] = $articleId;
            $this->articleModel->addAffiliation($articleId, $aff);
        }
        
        // Guardar secciones
        foreach ($data['sections'] as $section) {
            $section['article_id'] = $articleId;
            $this->articleModel->addSection($section);
        }
        
        // Guardar tablas
        foreach ($data['tables'] as $table) {
            $table['article_id'] = $articleId;
            $this->articleModel->addTable($table);
        }
        
        // Guardar figuras
        foreach ($data['figures'] as $figure) {
            $figure['article_id'] = $articleId;
            $this->articleModel->addFigure($figure);
        }
        
        // Guardar referencias
        foreach ($data['references'] as $ref) {
            $ref['article_id'] = $articleId;
            $this->articleModel->addReference($ref);
        }
    }
    
    /**
     * Buscar archivo HTML principal en el directorio
     */
    private function findMainHTML($dir) {
        $fallback = null;
        
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($files as $file) {
            $filename = $file->getFilename();
            $pathname = $file->getPathname();
            
            // Ignorar archivos de metadatos de Mac
            if (strpos($filename, '._') === 0 || strpos($pathname, '__MACOSX') !== false) {
                continue;
            }
            
            if ($file->isFile() && strtolower($file->getExtension()) === 'html') {
                $lowerName = strtolower($filename);
                if (in_array($lowerName, ['document.html', 'article.html', 'index.html'])) {
                    return $pathname;
                }
                if (!$fallback) {
                    $fallback = $pathname; // Guardar el primero válido como resguardo
                }
            }
        }
        
        return $fallback;
    }
    
    /**
     * Mover directorio recursivamente
     */
    private function moveDirectory($source, $destination) {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($files as $file) {
            $filename = $file->getFilename();
            $pathname = $file->getPathname();
            
            // Ignorar metadatos de Mac
            if (strpos($filename, '._') === 0 || strpos($pathname, '__MACOSX') !== false) {
                continue;
            }
            
            $targetPath = $destination . '/' . substr($pathname, strlen($source) + 1);
            
            if ($file->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                copy($pathname, $targetPath);
            }
        }
    }
    
    /**
     * Limpiar directorio temporal
     */
    private function cleanupTemp($dir) {
        if (!is_dir($dir)) return;
        
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        
        rmdir($dir);
    }
    
    /**
     * Obtener artículo con todos sus datos
     */
    public function getArticle($articleId) {
        header('Content-Type: application/json');
        
        $article = $this->articleModel->getById($articleId);
        
        if (!$article) {
            echo json_encode(['success' => false, 'message' => 'Artículo no encontrado']);
            return;
        }
        
        $article['authors'] = $this->articleModel->getAuthors($articleId);
        $article['affiliations'] = $this->articleModel->getAffiliations($articleId);
        $article['sections'] = $this->articleModel->getSections($articleId);
        $article['tables'] = $this->articleModel->getTables($articleId);
        $article['figures'] = $this->articleModel->getFigures($articleId);
        $article['references'] = $this->articleModel->getReferences($articleId);
        $article['footnotes']  = $this->articleModel->getFootnotes($articleId);
        $article['files'] = $this->articleModel->getFiles($articleId);
        
        // Leer el contenido HTML desde el archivo físico
        if (empty($article['html_content'])) {
            $filesAsc = array_reverse($article['files']);
            foreach ($filesAsc as $file) {
                if ($file['file_type'] === 'html' && file_exists($file['file_path'])) {
                    // Evitar cargar htmls recién exportados por error (index.html)
                    if (strpos($file['file_path'], 'index.html') !== false) continue;
                    $article['html_content'] = file_get_contents($file['file_path']);
                    break;
                }
            }
        }
        
        $article['markup'] = $this->articleModel->getMarkup($articleId);
        
        // Sanitize UTF-8 to prevent json_encode from failing silently
        if (!empty($article['html_content'])) {
            $article['html_content'] = mb_convert_encoding($article['html_content'], 'UTF-8', 'UTF-8');
        }
        
        $json = json_encode(['success' => true, 'article' => $article]);
        if ($json === false) {
            echo json_encode(['success' => false, 'message' => 'Error JSON']);
        } else {
            echo $json;
        }
    }
    
    /**
     * Listar todos los artículos
     */
    public function listArticles() {
        header('Content-Type: application/json');
        
        $filters = [];
        if (isset($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        
        $articles = $this->articleModel->getAll($filters);
        
        echo json_encode(['success' => true, 'articles' => $articles]);
    }
    
    /**
     * Guardar marcación manual
     */
    public function saveMarkup() {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['article_id']) || !isset($input['markup_data'])) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            return;
        }
        
        $this->articleModel->saveMarkup(
            $input['article_id'],
            $input['markup_data'],
            $_SESSION['user_id'] ?? null
        );
        
        echo json_encode(['success' => true]);
    }
    
    public function listMarkupVersions() {
        header('Content-Type: application/json');
        $articleId = $_GET['article_id'] ?? null;
        if (!$articleId) {
            echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
            return;
        }
        $versions = $this->articleModel->getMarkupVersions($articleId);
        echo json_encode(['success' => true, 'versions' => $versions]);
    }

    public function restoreMarkupVersion() {
        header('Content-Type: application/json');
        $markupId = $_GET['markup_id'] ?? null;
        if (!$markupId) {
            echo json_encode(['success' => false, 'message' => 'ID de versión no proporcionado']);
            return;
        }
        $markup = $this->articleModel->getMarkupById($markupId);
        if ($markup) {
            echo json_encode(['success' => true, 'markup_data' => $markup['markup_data']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Versión no encontrada']);
        }
    }

    public function duplicateArticle() {
        header('Content-Type: application/json');
        $articleIdStr = $_GET['article_id'] ?? null;
        $lang = $_GET['lang'] ?? 'en';
        
        if (!$articleIdStr) {
            echo json_encode(['success' => false, 'message' => 'ID de artículo no proporcionado']);
            return;
        }
        
        $article = $this->articleModel->getById($articleIdStr);
        if (!$article) {
            echo json_encode(['success' => false, 'message' => 'Artículo no encontrado']);
            return;
        }
        
        $newArticleData = [
            'issue_id' => $article['issue_id'],
            'title' => $article['title'] . " (Copia $lang)",
            'title_en' => $article['title_en'],
            'doi' => $article['doi'] ?? null,
            'abstract' => $article['abstract'],
            'abstract_en' => $article['abstract_en'],
            'keywords' => $article['keywords'],
            'keywords_en' => $article['keywords_en'],
            'article_type' => $article['article_type'],
            'language' => $lang,
            'received_date' => $article['received_date'],
            'accepted_date' => $article['accepted_date'],
            'published_date' => $article['published_date'],
            'pagination' => $article['pagination'],
            'status' => $article['status'],
            'uploaded_by' => $_SESSION['user_id'] ?? null,
            'template_id' => $article['template_id'] ?? null
        ];
        
        $result = $this->articleModel->create($newArticleData);
        if ($result['success']) {
            // Also copy authors/affiliations/etc.
            $newArticleId = $result['article_id'];
            $this->copyArticleMetadata($articleIdStr, $newArticleId);
            
            // Also copy the physical files
            $sourceDir = $this->config['paths']['articles'] . $articleIdStr;
            $destDir = $this->config['paths']['articles'] . $newArticleId;
            if (is_dir($sourceDir)) {
                $this->moveDirectory($sourceDir, $destDir); // Acts as a copy
            }
            
            echo json_encode(['success' => true, 'new_article_id' => $newArticleId]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al duplicar artículo']);
        }
    }

    private function copyArticleMetadata($sourceId, $destId) {
        $authors = $this->articleModel->getAuthors($sourceId);
        foreach($authors as $a) {
            unset($a['id']);
            $this->articleModel->addAuthor($destId, $a);
        }
        
        $affils = $this->articleModel->getAffiliations($sourceId);
        foreach($affils as $a) {
            unset($a['id']);
            $this->articleModel->addAffiliation($destId, $a);
        }
        
        $sections = $this->articleModel->getSections($sourceId);
        foreach($sections as $s) {
            unset($s['id']);
            $s['article_id'] = $destId;
            $this->articleModel->addSection($s);
        }
        
        $refs = $this->articleModel->getReferences($sourceId);
        foreach($refs as $r) {
            unset($r['id']);
            $r['article_id'] = $destId;
            $this->articleModel->addReference($r);
        }
        
        $tables = $this->articleModel->getTables($sourceId);
        foreach($tables as $t) {
            unset($t['id']);
            $t['article_id'] = $destId;
            $this->articleModel->addTable($t);
        }
        
        $figures = $this->articleModel->getFigures($sourceId);
        foreach($figures as $f) {
            unset($f['id']);
            $f['article_id'] = $destId;
            $this->articleModel->addFigure($f);
        }
        
        $markup = $this->articleModel->getMarkup($sourceId);
        if ($markup) {
            $this->articleModel->saveMarkup($destId, $markup['markup_data'], $_SESSION['user_id'] ?? null);
        }
    }
    
    /**
     * Subir imagen para tablas/figuras
     */
    public function uploadImage() {
        header('Content-Type: application/json');
        
        if (!isset($_FILES['image']) || !isset($_POST['article_id'])) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            return;
        }
        
        $articleId = $_POST['article_id'];
        $file = $_FILES['image'];
        
        $articleDir = $this->config['paths']['articles'] . $articleId;
        if (!is_dir($articleDir)) {
            mkdir($articleDir, 0755, true);
        }
        
        // Sanitize filename
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        if (!in_array($extension, $allowedExtensions)) {
            echo json_encode(['success' => false, 'message' => 'Tipo de imagen no permitido']);
            return;
        }
        
        $filename = uniqid('asset_') . '.' . $extension;
        $targetPath = $articleDir . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // URL pública relativa
            $url = 'articles/' . $articleId . '/' . $filename;
            echo json_encode([
                'success' => true, 
                'url' => $url, 
                'path' => $targetPath, 
                'filename' => $filename
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al mover archivo']);
        }
    }

    /**
     * Guardar metadatos desde el panel izquierdo (Title, Abstract, Sections, References)
     */
    public function updateMetadata() {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['article_id'])) {
            echo json_encode(['success' => false, 'message' => 'ID de artículo no especificado']);
            return;
        }
        
        $articleId = $input['article_id'];
        
        // 1. Actualizar artículos base (title, abstract, keywords, etc)
        $updateData = [
            'title' => $input['title'] ?? '',
            'title_en' => $input['title_en'] ?? '',
            'doi' => $input['doi'] ?? '',
            'pagination' => $input['pagination'] ?? '',
            'abstract' => $input['abstract'] ?? '',
            'abstract_en' => $input['abstract_en'] ?? '',
            'keywords' => $input['keywords'] ?? '',
            'keywords_en' => $input['keywords_en'] ?? ''
        ];
        
        if (!empty($input['received_date'])) $updateData['received_date'] = $input['received_date'];
        if (!empty($input['accepted_date'])) $updateData['accepted_date'] = $input['accepted_date'];
        if (!empty($input['published_date'])) $updateData['published_date'] = $input['published_date'];
        
        $this->articleModel->update($articleId, $updateData);
        
        // 1.5. Actualizar Autores
        if (isset($input['custom_authors']) && is_array($input['custom_authors'])) {
            $this->articleModel->deleteAuthors($articleId);
            $order = 1;
            foreach ($input['custom_authors'] as $auth) {
                $this->articleModel->addAuthor($articleId, [
                    'given_names' => $auth['given_names'] ?? '',
                    'surname' => $auth['surname'] ?? '',
                    'affiliation' => $auth['affiliation'] ?? '',
                    'email' => $auth['email'] ?? '',
                    'orcid' => $auth['orcid'] ?? '',
                    'corresponding' => $auth['corresponding'] ?? 0,
                    'author_order' => $order++
                ]);
            }
        }
        
        // 2. Actualizar Secciones
        if (isset($input['custom_sections']) && is_array($input['custom_sections'])) {
            $this->articleModel->deleteSections($articleId);
            $order = 1;
            foreach ($input['custom_sections'] as $sec) {
                $this->articleModel->addSection([
                    'article_id' => $articleId,
                    'section_id' => 'sec-' . uniqid(),
                    'title' => $sec['type_name'] ?? '',
                    'content' => $sec['content'] ?? '',
                    'level' => $sec['level'] ?? 1,
                    'section_order' => $order++
                ]);
            }
        }
        
        // Ensure article_footnotes table exists
        require_once __DIR__ . '/../models/Database.php';
        $db = Database::getInstance();
        $db->query("CREATE TABLE IF NOT EXISTS article_footnotes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            article_id INT NOT NULL,
            fn_id VARCHAR(50) NOT NULL,
            text TEXT,
            fn_order INT NOT NULL
        )");
        
        // 2.5 Actualizar Notas al pie
        if (isset($input['custom_footnotes']) && is_array($input['custom_footnotes'])) {
            $db->query("DELETE FROM article_footnotes WHERE article_id = " . intval($articleId));
            $order = 1;
            foreach ($input['custom_footnotes'] as $fn) {
                $db->query("INSERT INTO article_footnotes (article_id, fn_id, text, fn_order) VALUES (" . 
                    intval($articleId) . ", '" . 
                    addslashes($fn['fn_id'] ?? 'fn-'.$order) . "', '" . 
                    addslashes($fn['text'] ?? '') . "', " . 
                    $order++ . ")"
                );
            }
        }
        
        // 3. Actualizar Referencias
        if (isset($input['custom_references']) && is_array($input['custom_references'])) {
            $this->articleModel->deleteReferences($articleId);
            $order = 1;
            foreach ($input['custom_references'] as $ref) {
                $authors = $ref['authors'] ?? '';
                $year = $ref['year'] ?? '';
                $title = $ref['title'] ?? '';
                $source = $ref['source'] ?? '';
                $pages = $ref['pages'] ?? '';
                $doi = $ref['doi'] ?? '';
                $url = $ref['url'] ?? '';
                
                $citationParts = [];
                if($authors) $citationParts[] = $authors;
                if($year) $citationParts[] = "($year)";
                if($title) $citationParts[] = $title;
                if($source) $citationParts[] = $source;
                if($pages) $citationParts[] = $pages;
                
                $citation = trim(implode('. ', $citationParts));
                
                $this->articleModel->addReference([
                    'article_id' => $articleId,
                    'ref_id' => 'ref-' . uniqid(),
                    'reference_type' => 'journal',
                    'authors' => $authors,
                    'year' => $year,
                    'title' => $title,
                    'source' => $source,
                    'pages' => $pages,
                    'doi' => $doi,
                    'url' => $url,
                    'full_citation' => $citation,
                    'reference_order' => $order++
                ]);
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Metadatos actualizados']);
    }
}
