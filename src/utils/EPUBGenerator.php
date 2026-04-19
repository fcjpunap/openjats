<?php
/**
 * Generador de EPUB desde artículo marcado
 * EPUB es básicamente un ZIP con estructura específica
 */

require_once __DIR__ . '/../models/Article.php';

class EPUBGenerator {
    private $articleModel;
    private $config;
    private $usedTableLabels = [];
    private $usedFigureLabels = [];
    
    public function __construct() {
        $this->articleModel = new Article();
        $this->config = require __DIR__ . '/../../config/config.php';
    }
    
    /**
     * Generar EPUB desde artículo
     */
    public function generate($articleId) {
        $article = $this->articleModel->getById($articleId);
        
        if (!$article) {
            return ['success' => false, 'message' => 'Artículo no encontrado'];
        }
        
        // Obtener datos completos
        $authors = $this->articleModel->getAuthors($articleId);
        $sections = $this->articleModel->getSections($articleId);
        $references = $this->articleModel->getReferences($articleId);
        $footnotes = $this->articleModel->getFootnotes($articleId);
        
        $markup = $this->articleModel->getMarkup($articleId);
        $tables = $this->articleModel->getTables($articleId);
        $figures = $this->articleModel->getFigures($articleId);
        
        // Si hay marcación, esa es la fuente de la verdad
        if ($markup && isset($markup['markup_data'])) {
            $tables = $markup['markup_data']['tables'] ?? [];
            $figures = $markup['markup_data']['images'] ?? $markup['markup_data']['figures'] ?? [];
        }
        
        // Crear directorio temporal
        $tempDir = sys_get_temp_dir() . '/epub_' . uniqid();
        if (!mkdir($tempDir, 0755, true)) {
            return ['success' => false, 'message' => 'Error al crear directorio temporal'];
        }
        
        try {
            // Crear estructura EPUB
            $this->createEPUBStructure($tempDir, $article, $authors, $sections, $references, $footnotes, $tables, $figures);
            
            // Crear archivo ZIP (EPUB)
            $articleDir = $this->config['paths']['articles'] . $article['article_id'];
            if (!is_dir($articleDir)) {
                @mkdir($articleDir, 0755, true);
            }
            $epubFile = $articleDir . '/article.epub';
            $this->createZip($tempDir, $epubFile);
            
            // Limpiar directorio temporal
            $this->deleteDirectory($tempDir);
            
            return [
                'success' => true,
                'message' => 'EPUB generado exitosamente',
                'file' => basename($epubFile),
                'path' => $epubFile,
                'download_url' => str_replace($this->config['paths']['articles'], 'articles/', $epubFile)
            ];
            
        } catch (Exception $e) {
            // Limpiar en caso de error
            if (is_dir($tempDir)) {
                $this->deleteDirectory($tempDir);
            }
            return ['success' => false, 'message' => 'Error al generar EPUB: ' . $e->getMessage()];
        }
    }
    
    /**
     * Crear estructura de directorios y archivos EPUB
     */
    private function createEPUBStructure($dir, $article, $authors, $sections, $references, $footnotes, $tables, $figures) {
        // 1. mimetype (DEBE ser el primer archivo sin compresión)
        file_put_contents($dir . '/mimetype', 'application/epub+zip');
        
        // 2. META-INF/container.xml
        mkdir($dir . '/META-INF', 0755);
        file_put_contents($dir . '/META-INF/container.xml', $this->getContainerXML());
        
        // 3. OEBPS/ (contenido)
        mkdir($dir . '/OEBPS', 0755);
        
        // Pre-generar contenido XHTML para encontrar imágenes
        $contentXHTML = $this->getContentXHTML($article, $authors, $sections, $references, $footnotes, $tables, $figures);
        
        // Extraer y procesar imágenes
        $imageItems = $this->processAndCopyImages($dir, $contentXHTML);
        
        // 4. content.opf (metadatos y manifiesto) - Ahora con imágenes
        file_put_contents($dir . '/OEBPS/content.opf', $this->getContentOPF($article, $authors, $imageItems));
        
        // 5. toc.ncx (tabla de contenidos)
        file_put_contents($dir . '/OEBPS/toc.ncx', $this->getTocNCX($article, $sections));
        
        // 6. Contenido HTML
        file_put_contents($dir . '/OEBPS/content.xhtml', $contentXHTML);
        
        // 7. CSS
        file_put_contents($dir . '/OEBPS/style.css', $this->getCSS());
    }

    private function processAndCopyImages($dir, &$content) {
        $imageItems = [];
        $publicDir = realpath(__DIR__ . '/../../public');
        
        // Encontrar todas las imágenes en el contenido
        if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $matches)) {
            $uniqueImages = array_unique($matches[1]);
            
            foreach ($uniqueImages as $index => $src) {
                // Saltar imágenes externas (URLs absolutas)
                if (preg_match('/^https?:\/\//i', $src)) continue;
                
                // Normalizar ruta
                $cleanSrc = ltrim($src, '/');
                $sourcePath = $publicDir . '/' . $cleanSrc;
                
                if (file_exists($sourcePath)) {
                    $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
                    if (empty($ext)) $ext = 'jpg';
                    
                    $targetName = 'img_' . $index . '.' . $ext;
                    $targetPath = $dir . '/OEBPS/' . $targetName;
                    
                    // Asegurar que el directorio destino existe (aunque OEBPS ya debe estar)
                    if (copy($sourcePath, $targetPath)) {
                        $mime = 'image/jpeg';
                        if ($ext === 'png') $mime = 'image/png';
                        if ($ext === 'gif') $mime = 'image/gif';
                        if ($ext === 'webp') $mime = 'image/webp';
                        if ($ext === 'svg') $mime = 'image/svg+xml';
                        
                        $imageItems[] = [
                            'id' => 'img' . $index,
                            'href' => $targetName,
                            'media-type' => $mime
                        ];
                        
                        // Actualizar el src en el contenido final para que sea relativo al EPUB
                        $content = str_replace($src, $targetName, $content);
                    }
                }
            }
        }
        
        return $imageItems;
    }
    
    /**
     * container.xml
     */
    private function getContainerXML() {
        return '<?xml version="1.0" encoding="UTF-8"?>
<container version="1.0" xmlns="urn:oasis:names:tc:opendocument:xmlns:container">
    <rootfiles>
        <rootfile full-path="OEBPS/content.opf" media-type="application/oebps-package+xml"/>
    </rootfiles>
</container>';
    }
    
    /**
     * content.opf (metadatos)
     */
    private function getContentOPF($article, $authors, $imageItems = []) {
        $authorsStr = implode(', ', array_map(function($a) {
            return ($a['given_names'] ?? '') . ' ' . ($a['surname'] ?? '');
        }, $authors));
        
        $uuid = 'urn:uuid:' . uniqid();
        $date = date('Y-m-d');
        
        $manifestItems = '';
        foreach ($imageItems as $img) {
            $manifestItems .= '        <item id="' . $img['id'] . '" href="' . $img['href'] . '" media-type="' . $img['media-type'] . '"/>' . "\n";
        }
        
        return '<?xml version="1.0" encoding="UTF-8"?>
<package xmlns="http://www.idpf.org/2007/opf" unique-identifier="BookID" version="2.0">
    <metadata xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:opf="http://www.idpf.org/2007/opf">
        <dc:title>' . htmlspecialchars($article['title']) . '</dc:title>
        <dc:creator>' . htmlspecialchars($authorsStr) . '</dc:creator>
        <dc:language>es</dc:language>
        <dc:identifier id="BookID">' . $uuid . '</dc:identifier>
        <dc:date>' . $date . '</dc:date>
        <dc:publisher>OpenJATS</dc:publisher>
        ' . (!empty($article['article_type']) ? '<dc:subject>' . htmlspecialchars($article['article_type']) . '</dc:subject>' : '') . '
    </metadata>
    <manifest>
        <item id="ncx" href="toc.ncx" media-type="application/x-dtbncx+xml"/>
        <item id="content" href="content.xhtml" media-type="application/xhtml+xml"/>
        <item id="css" href="style.css" media-type="text/css"/>
        ' . $manifestItems . '
    </manifest>
    <spine toc="ncx">
        <itemref idref="content"/>
    </spine>
</package>';
    }
    
    /**
     * toc.ncx (tabla de contenidos)
     */
    private function getTocNCX($article, $sections) {
        $navPoints = '';
        $order = 1;
        
        // 1. Resumen
        if (!empty($article['abstract']) || !empty($article['abstract_en'])) {
            $navPoints .= '
        <navPoint id="nav-abstract" playOrder="' . $order . '">
            <navLabel><text>Resumen / Abstract</text></navLabel>
            <content src="content.xhtml#abstract-sec"/>
        </navPoint>';
            $order++;
        }

        // 2. Secciones
        foreach ($sections as $section) {
            $navPoints .= '
        <navPoint id="section' . $section['id'] . '" playOrder="' . $order . '">
            <navLabel>
                <text>' . htmlspecialchars($section['title']) . '</text>
            </navLabel>
            <content src="content.xhtml#section' . $section['id'] . '"/>
        </navPoint>';
            $order++;
        }
        
        // 3. Referencias
        if (!empty($references)) {
            $navPoints .= '
        <navPoint id="nav-references" playOrder="' . $order . '">
            <navLabel><text>Referencias</text></navLabel>
            <content src="content.xhtml#references"/>
        </navPoint>';
            $order++;
        }

        // 4. Notas al pie
        if (!empty($footnotes)) {
            $navPoints .= '
        <navPoint id="nav-footnotes" playOrder="' . $order . '">
            <navLabel><text>Notas al pie</text></navLabel>
            <content src="content.xhtml#footnotes"/>
        </navPoint>';
            $order++;
        }

        return '<?xml version="1.0" encoding="UTF-8"?>
<ncx xmlns="http://www.daisy.org/z3986/2005/ncx/" version="2005-1">
    <head>
        <meta name="dtb:uid" content="' . uniqid() . '"/>
        <meta name="dtb:depth" content="1"/>
        <meta name="dtb:totalPageCount" content="0"/>
        <meta name="dtb:maxPageNumber" content="0"/>
    </head>
    <docTitle>
        <text>' . htmlspecialchars($article['title']) . '</text>
    </docTitle>
    <navMap>' . $navPoints . '
    </navMap>
</ncx>';
    }
    
    /**
     * content.xhtml (contenido principal)
     */
    private function getContentXHTML($article, $authors, $sections, $references, $footnotes, $tables, $figures) {
        $authorsHTML = '';
        foreach ($authors as $author) {
            $isCorr = (!empty($author['is_corresponding']) || !empty($author['corresponding'])) ? ' <sup>*</sup>' : '';
            $authorsHTML .= '<div class="author" style="margin-bottom:1em;">';
            $authorsHTML .= '<p style="font-weight:bold;margin:0;">' . htmlspecialchars(($author['given_names'] ?? '') . ' ' . ($author['surname'] ?? '')) . $isCorr . '</p>';
            if (!empty($author['affiliation'])) $authorsHTML .= '<p style="margin:0;font-size:0.9em;color:#555;">' . htmlspecialchars($author['affiliation']) . '</p>';
            if (!empty($author['email'])) $authorsHTML .= '<p style="margin:0;font-size:0.9em;color:#555;">Email: ' . htmlspecialchars($author['email']) . '</p>';
            if (!empty($author['orcid'])) {
                $orcidClean = preg_replace('/^(https?:\/\/)?(www\.)?orcid\.org\//i', '', $author['orcid']);
                $authorsHTML .= '<p style="margin:0;font-size:0.9em;color:#555;">ORCID: <a href="https://orcid.org/' . htmlspecialchars($orcidClean) . '" style="color:#555; text-decoration:none;">https://orcid.org/' . htmlspecialchars($orcidClean) . '</a></p>';
            }
            $authorsHTML .= '</div>';
        }
        $authorsHTML .= '<p style="font-size:0.8em;color:#777;margin-top:1em;">* Autor de correspondencia / Corresponding author</p>';

        $metaHTML = '<div class="article-meta" style="margin-top:2em;padding-top:1em;border-top:1px solid #ccc;font-size:0.9em;">';
        if (!empty($article['received_date'])) $metaHTML .= '<p><b>Recibido / Received:</b> ' . htmlspecialchars($article['received_date']) . '</p>';
        if (!empty($article['accepted_date'])) $metaHTML .= '<p><b>Aceptado / Accepted:</b> ' . htmlspecialchars($article['accepted_date']) . '</p>';
        if (!empty($article['published_date'])) $metaHTML .= '<p><b>Publicado / Published:</b> ' . htmlspecialchars($article['published_date']) . '</p>';
        $metaHTML .= '</div>';

        $journalHTML = '<div class="journal-info" style="text-align:center;margin-bottom:2em;border-bottom:2px solid #2c3e50;padding-bottom:1em;">';
        $journalHTML .= '<h3>' . htmlspecialchars($article['journal_title'] ?? 'Revista Académica') . '</h3>';
        $journalHTML .= '<p>ISSN: ' . htmlspecialchars($article['issn'] ?? '') . ' | Vol. ' . htmlspecialchars($article['volume_number'] ?? '') . ', Núm. ' . htmlspecialchars($article['issue_number'] ?? '') . ' (' . htmlspecialchars($article['year'] ?? '') . ')</p>';
        if (!empty($article['journal_url'])) $journalHTML .= '<p>Journal homepage: <a href="'.htmlspecialchars($article['journal_url']).'">' . htmlspecialchars($article['journal_url']) . '</a></p>';
        $cleanDoi = preg_replace('/^https?:\/\/(dx\.)?doi\.org\//i', '', $article['doi'] ?? '');
        if (!empty($cleanDoi)) $journalHTML .= '<p>DOI: <a href="https://doi.org/'.htmlspecialchars($cleanDoi).'">' . htmlspecialchars('https://doi.org/' . $cleanDoi) . '</a></p>';
        $journalHTML .= '<p>This work is licensed under a Creative Commons Attribution 4.0 International License.</p>';
        $journalHTML .= '</div>';
        
        $abstractHTML = '';
        if (!empty($article['abstract']) || !empty($article['abstract_en'])) {
            $abstractHTML .= '<div id="abstract-sec" class="abstract-container"><a id="abstract-sec"></a>';
            if (!empty($article['abstract'])) {
                $abstractHTML .= '<div class="abstract" style="margin:2em 0;padding:1.5em;background:#f9f9f9;border-left:4px solid #2c3e50;">';
                $abstractHTML .= '<h2>Resumen</h2><p>' . htmlspecialchars(strip_tags($article['abstract'])) . '</p>';
                if (!empty($article['keywords'])) $abstractHTML .= '<p><b>Palabras clave:</b> ' . htmlspecialchars($article['keywords']) . '</p>';
                $abstractHTML .= '</div>';
            }
            if (!empty($article['abstract_en'])) {
                $abstractHTML .= '<div class="abstract" style="margin:2em 0;padding:1.5em;background:#f9f9f9;border-left:4px solid #2c3e50;">';
                $abstractHTML .= '<h2>Abstract</h2><p>' . htmlspecialchars(strip_tags($article['abstract_en'])) . '</p>';
                if (!empty($article['keywords_en'])) $abstractHTML .= '<p><b>Keywords:</b> ' . htmlspecialchars($article['keywords_en']) . '</p>';
                $abstractHTML .= '</div>';
            }
            $abstractHTML .= '</div>';
        }
        
        $sectionsHTML = '';
        foreach ($sections as $section) {
            $content = $section['content'] ?? '';
            // Strip tables so that unmarked raw HTML tables are destroyed
            $content = strip_tags($content, '<b><i><u><strong><em><a><p><br><ol><ul><li><sup><sub><img><span><div>');
            $content = $this->sanitizeForXHTML($content);
            
            $sectionsHTML .= '
        <div class="section-block" id="section' . $section['id'] . '">
            <h2>' . htmlspecialchars($section['title']) . '</h2>
            <div class="section-content">' . $this->processPlaceholders($content, $tables, $figures) . '</div>
        </div>';
        }

        // Add unused tables
        foreach ($tables as $t) {
            $tLabel = trim($t['label'] ?? '');
            if ($tLabel !== '' && in_array($tLabel, $this->usedTableLabels)) continue;
            
            $tableHtml = $this->renderTable($t);
            $sectionsHTML .= $tableHtml;
        }

        // Add unused figures
        foreach ($figures as $f) {
            $fLabel = trim($f['label'] ?? '');
            if ($fLabel !== '' && in_array($fLabel, $this->usedFigureLabels)) continue;
            
            $figHtml = $this->renderFigure($f);
            $sectionsHTML .= $figHtml;
        }
        
        $referencesHTML = '';
        if (!empty($references)) {
            $referencesHTML = '<div class="section-block" id="references"><a id="references"></a><h2>Referencias</h2><div class="references-list">';
            foreach ($references as $ref) {
                $citationHtml = htmlspecialchars($ref['full_citation'] ?? $ref['citation'] ?? '');
                $citationHtml = preg_replace('/(https?:\/\/[^\s]+)/', '<a href="$1" target="_blank" style="color:#0000FF; text-decoration:underline;">$1</a>', $citationHtml);
                $referencesHTML .= '<div class="reference-item" style="margin-bottom: 1em; text-indent: -1.5em; padding-left: 1.5em;"><a id="ref-'.($ref['reference_order'] ?? '').'"></a><a id="'.($ref['ref_id'] ?? '').'"></a><a id="ref-'.($ref['ref_id'] ?? '').'"></a>' . $citationHtml . '</div>';
            }
            $referencesHTML .= '</div></div>';
        }
        
        $footnotesHTML = '';
        if (!empty($footnotes)) {
            $footnotesHTML = '<div class="section-block" id="footnotes"><a id="footnotes"></a><h2>Notas al pie</h2><div class="footnotes-list">';
            foreach ($footnotes as $fn) {
                $footnotesHTML .= '<div class="footnote-item" style="margin-bottom: 1em; font-size: 0.9em;"><a id="fn-' . htmlspecialchars($fn['fn_id'] ?? '') . '"></a><a id="' . htmlspecialchars($fn['fn_id'] ?? '') . '"></a><sup>[' . htmlspecialchars($fn['fn_id'] ?? '') . ']</sup> ' . htmlspecialchars($fn['text'] ?? '') . '</div>';
            }
            $footnotesHTML .= '</div></div>';
        }
        
        $xhtml = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8"/>
    <title>' . htmlspecialchars($article['title']) . '</title>
    <link rel="stylesheet" type="text/css" href="style.css"/>
</head>
<body>
    <div class="article-content">
        <div class="article-header">
            ' . $journalHTML . '
            ' . (!empty($article['article_type']) ? '<div class="article-type" style="text-transform:uppercase;color:#888;font-weight:bold;margin-bottom:1em;">Sección / Section: ' . htmlspecialchars($article['article_type']) . '</div>' : '') . '
            <h1>' . htmlspecialchars($article['title']) . '</h1>
            ' . (!empty($article['title_en']) ? '<h2 style="color:#555;font-style:italic;">' . htmlspecialchars($article['title_en']) . '</h2>' : '') . '
            <div class="authors">' . $authorsHTML . '</div>
            ' . $metaHTML . '
        </div>
        
        ' . $abstractHTML . '
        
        <div class="article-body">' . $sectionsHTML . '</div>
        
        ' . $referencesHTML . '
        
        ' . $footnotesHTML . '
    </div>
</body>
</html>';

        return $this->forceXHTMLCompliance($xhtml);
    }

    private function forceXHTMLCompliance($html) {
        if (!class_exists('DOMDocument')) return $html;

        try {
            $dom = new DOMDocument('1.0', 'UTF-8');
            // Cargar con prefijo XML para forzar UTF-8
            $content = '<?xml encoding="UTF-8">' . $html;
            
            // Usar LIBXML_HTML_NOIMPLIED para no añadir html/body si ya están, 
            // pero como estamos enviando el documento completo, mejor dejar que él limpie.
            @$dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            
            // Forzar XML (que es lo que EPUB necesita para XHTML)
            $xhtml = $dom->saveXML($dom->documentElement);
            
            // Re-añadir el DOCTYPE si saveXML lo quitó o lo cambió
            if (stripos($xhtml, '<!DOCTYPE') === false) {
                $xhtml = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">' . "\n" . $xhtml;
            }
            
            // Asegurar que el xmlns esté presente si saveXML lo quitó
            if (stripos($xhtml, 'xmlns="http://www.w3.org/1999/xhtml"') === false) {
                $xhtml = preg_replace('/<html/i', '<html xmlns="http://www.w3.org/1999/xhtml"', $xhtml);
            }
            
            return $xhtml;
        } catch (Exception $e) {
            return $html;
        }
    }
    
    /**
     * CSS para EPUB
     */
    private function getCSS() {
        return 'body {
    font-family: Georgia, serif;
    line-height: 1.6;
    margin: 1em;
}

h1 {
    color: #2c3e50;
    font-size: 2em;
    margin-bottom: 0.5em;
}

h2 {
    color: #34495e;
    font-size: 1.5em;
    margin-top: 1.5em;
    margin-bottom: 0.5em;
}

.authors {
    font-style: italic;
    margin-bottom: 2em;
    color: #555;
}

.author {
    margin: 0.25em 0;
}

section {
    margin-bottom: 2em;
}

.section-content {
    text-align: justify;
}

ol, ul {
    margin: 1em 0;
    padding-left: 2em;
}

/* Estilos APA 7 para Tablas */
.table-container {
    margin: 2em 0;
}

.table-label, .figure-label {
    font-weight: bold;
    font-family: sans-serif;
    margin-bottom: 0.25em;
    text-align: left;
}

.table-caption, .figure-caption {
    font-style: italic;
    font-family: serif;
    margin-bottom: 1em;
    text-align: left;
}

table.apa-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1em;
    margin-bottom: 1.5em;
    border: none;
}

table.apa-table th, table.apa-table td {
    border: none;
    padding: 0.5em;
    text-align: left;
    vertical-align: top;
}

/* Líneas superior y divisoria de cabecera en la primera fila (Header) */
table.apa-table thead tr:first-child th, 
table.apa-table thead tr:first-child td,
table.apa-table tr:first-child th, 
table.apa-table tr:first-child td {
    border-top: 2px solid black;
    border-bottom: 1px solid black;
}

/* Línea inferior (Bottom border) en la última fila */
table.apa-table tr:last-child td,
table.apa-table tr:last-child th {
    border-bottom: 2px solid black;
}

.table-note {
    font-size: 0.9em;
    margin-top: 0.5em;
}';
    }
    
    /**
     * Crear archivo ZIP (EPUB)
     */
    private function createZip($sourceDir, $outputFile) {
        $zip = new ZipArchive();
        
        if ($zip->open($outputFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception('No se pudo crear archivo EPUB');
        }
        
        // mimetype DEBE ir primero y SIN compresión
        $zip->addFile($sourceDir . '/mimetype', 'mimetype');
        $zip->setCompressionName('mimetype', ZipArchive::CM_STORE);
        
        // Agregar resto de archivos
        $this->addDirectoryToZip($zip, $sourceDir, '');
        
        $zip->close();
    }
    
    /**
     * Agregar directorio recursivamente al ZIP
     */
    private function addDirectoryToZip($zip, $sourceDir, $zipPath) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($sourceDir) + 1);
                
                // Saltar mimetype (ya fue agregado)
                if ($relativePath === 'mimetype') {
                    continue;
                }
                
                $zip->addFile($filePath, $relativePath);
            }
        }
    }
    
    /**
     * Eliminar directorio recursivamente
     */
    private function deleteDirectory($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function processPlaceholders($content, $tables, $figures) {
        $pattern = '/(<span[^>]*?(?:contenteditable=["\']false["\']|style=["\'][^"\']*(?:rgb\(\s*239,\s*246,\s*255\s*\)|rgb\(\s*240,\s*253,\s*244\s*\)|eff6ff|f0fdf4)[^"\']*["\'])[^>]*>)?\s*(\[(?:Tabla|Figura|Table|Figure)\s*[^\]]+\])\s*(<\/span>)?/i';
        
        $content = preg_replace_callback($pattern, function($matches) use ($tables, $figures) {
            $label = strip_tags($matches[2]);
            
            // Buscar en tablas con coincidencia flexible
            $foundTable = $this->findTableByLabel($label, $tables);
            if ($foundTable) {
                $this->usedTableLabels[] = trim($foundTable['label'] ?? '');
                return $this->renderTable($foundTable);
            }
            
            // Buscar en figuras con coincidencia flexible
            $foundFigure = $this->findFigureByLabel($label, $figures);
            if ($foundFigure) {
                $this->usedFigureLabels[] = trim($foundFigure['label'] ?? '');
                return $this->renderFigure($foundFigure);
            }
            
            return $matches[2];
        }, $content);

        // Limpieza robusta de cuadrados residuales (marcas de posición que ya no existen)
        $content = preg_replace_callback('/<span[^>]*style=["\'][^"\']*(?:rgb\(239,\s*246,\s*255\)|rgb\(240,\s*253,\s*244\)|eff6ff|f0fdf4)[^"\']*["\'][^>]*>(.*?)<\/span>/is', function($m) {
            $innerClean = trim(strip_tags(str_ireplace(['&nbsp;', '&#160;', '&#xa0;', '&amp;nbsp;'], ' ', $m[1])));
            if ($innerClean === '' || preg_match('/^\[\s*\]$/', $innerClean)) {
                return '';
            }
            return $m[0];
        }, $content);
        
        return $content;
    }

    private function sanitizeForXHTML($html) {
        if (!$html || trim($html) === '') return '';
        
        // 1. Limpieza de entidades y caracteres problemáticos para XML
        $html = str_ireplace('&nbsp;', '&#160;', $html);
        $html = str_ireplace('&nbsp', '&#160;', $html);
        
        // Eliminar caracteres de control (0-31 except 9,10,13) que matan parsers XML
        $html = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $html);

        // 2. Escapar ampersands que no sean entidades existentes
        $html = preg_replace('/&(?![a-zA-Z0-9#]+;)/', '&amp;', $html);
        
        // Purga de estilos de fuente inline del editor web que rompen la lectura de EPUB
        $html = preg_replace('/font-size\s*:\s*[^;"\']+[;]?/i', '', $html);
        $html = preg_replace('/font-family\s*:\s*[^;"\']+[;]?/i', '', $html);
        $html = preg_replace('/style=["\']\s*["\']/i', '', $html); // Clean empty styles
        
        // 3. Cerrar etiquetas huérfanas comunes para XHTML
        $html = preg_replace('/<(br|hr|img|input|meta|link)([^>]*?)(?<!\/)>/i', '<$1$2 />', $html);
        
        // 4. Asegurar que los atributos tengan comillas (XHTML estricto)
        // Intentar arreglar cosas como border=0 o colspan=2
        $html = preg_replace('/(\s+)([a-z-]+)=([^"\'>\s]+)/i', '$1$2="$3"', $html);

        // 5. Arreglar imágenes sin alt
        $html = preg_replace_callback('/<img([^>]*\/?)>/i', function($m) {
            $tag = $m[0];
            if (stripos($tag, 'alt=') === false) {
                $tag = preg_replace('/<img/i', '<img alt="Imagen"', $tag);
            }
            return $tag;
        }, $html);
        
        return $html;
    }

    private function findTableByLabel($label, $tables) {
        $cleanSearch = strtolower(preg_replace('/\s+/', '', trim($label, '[]')));
        foreach ($tables as $t) {
            $tLabel = strtolower(preg_replace('/\s+/', '', trim($t['label'] ?? '')));
            if ($tLabel === $cleanSearch) return $t;
        }
        return null;
    }

    private function findFigureByLabel($label, $figures) {
        $cleanSearch = strtolower(preg_replace('/\s+/', '', trim($label, '[]')));
        foreach ($figures as $f) {
            $fLabel = strtolower(preg_replace('/\s+/', '', trim($f['label'] ?? '')));
            if ($fLabel === $cleanSearch) return $f;
        }
        return null;
    }

    private function renderTable($t) {
        $html = '<div class="table-container" style="margin:1em 0; padding:0.5em; border:none;">';
        $html .= '<div class="table-label" style="font-weight:bold;">' . htmlspecialchars($t['label'] ?? 'Tabla') . '</div>';
        $tCaptionPlaceholder = !empty($t['caption']) ? $t['caption'] : (!empty($t['title']) ? $t['title'] : '');
        if (!empty($tCaptionPlaceholder)) $html .= '<div style="font-style:italic; font-size:11pt; margin-bottom:10px; text-align:left;">' . htmlspecialchars($tCaptionPlaceholder) . '</div>';
        
        if (!empty($t['src']) && (($t['type'] ?? '') === 'image' || stripos($t['src'], 'uploads/') !== false)) {
            $html .= '<img src="' . htmlspecialchars($t['src']) . '" style="max-width:100%; height:auto;" alt="Tabla"/>';
        } else {
            // Ampliamos las opciones de claves para encontrar el contenido
            // Damos prioridad a claves que suelen tener el HTML real
            $tableHtml = $t['content'] ?? $t['html_content'] ?? $t['html'] ?? $t['table_html'] ?? $t['html_code'] ?? $t['data'] ?? '';
            
            if (empty($tableHtml) || strlen(trim(strip_tags($tableHtml))) < 5) {
                // Si está vacío o es muy corto, intentar con otras claves posibles
                $tableHtml = $t['markup'] ?? $t['raw_html'] ?? '';
            }
            
            $tableHtml = $this->sanitizeForXHTML($tableHtml);
            
            if (empty($tableHtml) || trim($tableHtml) === '') {
                $tableHtml = '<p style="color:red; font-style:italic;">[Contenido de tabla no disponible de ID: ' . htmlspecialchars($t['id'] ?? $t['table_id'] ?? '?') . ']</p>';
            } else {
                // Procesamiento avanzado con DOMDocument para estilo APA 7 infalible
                try {
                    $dom = new DOMDocument();
                    if (function_exists('mb_convert_encoding')) {
                        $tableHtml = mb_convert_encoding($tableHtml, 'HTML-ENTITIES', 'UTF-8');
                    }
                    @$dom->loadHTML('<?xml encoding="UTF-8"><div>' . $tableHtml . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                    
                    $tables = $dom->getElementsByTagName('table');
                    if ($tables->length > 0) {
                        $table = $tables->item(0);
                        $table->setAttribute('class', 'apa-table');
                        $table->removeAttribute('border');
                        $table->removeAttribute('cellspacing');
                        $table->removeAttribute('cellpadding');
                        
                        // Estilo APA 7 principal: Bordes superior (2px) e inferior (2px)
                        $table->setAttribute('style', 'width:100%; border-collapse:collapse; border-top:2px solid black; border-bottom:2px solid black; margin:1em 0;');
                        
                        // Identificar celdas de la primera fila (Cabecera) para la línea divisoria (1px)
                        $rows = $table->getElementsByTagName('tr');
                        if ($rows->length > 0) {
                            $firstRow = $rows->item(0);
                            $cells = $firstRow->getElementsByTagName('*');
                            foreach ($cells as $cell) {
                                if ($cell->nodeName == 'td' || $cell->nodeName == 'th') {
                                    $cell->setAttribute('style', 'border-bottom:1px solid black; padding: 0.5em; text-align: left; font-weight: bold;');
                                }
                            }
                            
                            // Limpiar padding en el resto de filas para consistencia
                            for ($i = 1; $i < $rows->length; $i++) {
                                $otherCells = $rows->item($i)->getElementsByTagName('*');
                                foreach ($otherCells as $cell) {
                                    if ($cell->nodeName == 'td' || $cell->nodeName == 'th') {
                                        $cell->setAttribute('style', 'padding: 0.5em; text-align: left; border: none;');
                                    }
                                }
                            }
                        }
                        $tableHtml = $dom->saveXML($table);
                    }
                } catch (Exception $e) {
                    // Fallback simple si falla DOMDocument
                    $tableHtml = preg_replace('/<table([^>]*)>/i', '<table$1 border="0" class="apa-table" style="border-collapse:collapse; border-top:2px solid black; border-bottom:2px solid black; width:100%;">', $tableHtml);
                }
            }
            
            $html .= $tableHtml;
        }

        if (!empty($t['nota']) && trim($t['nota']) !== '') $html .= '<div class="table-note" style="font-size:0.9em;font-style:italic;margin-top:0.5em;"><i>Nota.</i> ' . htmlspecialchars($t['nota']) . '</div>';
        if (!empty($t['footer']) && trim($t['footer']) !== '') $html .= '<div class="table-footer" style="font-size:0.8em;color:#666;margin-top:0.3em;">' . htmlspecialchars($t['footer']) . '</div>';
        $html .= '</div>';
        return $html;
    }

    private function renderFigure($f) {
        $src = $f['src'] ?? $f['file_path'] ?? $f['url'] ?? '';
        $html = '<div class="figure-container" style="margin:1.5em 0; text-align: left;">';
        $html .= '<div class="figure-label">' . htmlspecialchars($f['label'] ?? 'Figura') . '</div>';
        if (!empty($f['caption'])) $html .= '<div class="figure-caption" style="font-size:0.9em; margin-bottom: 0.8em;">' . htmlspecialchars($f['caption']) . '</div>';
        
        $w = $f['width'] ?? '100%';
        if ($src) {
            $html .= '<div style="text-align: center; margin: 1em 0;"><img src="' . htmlspecialchars($src) . '" style="max-width:' . htmlspecialchars($w) . '; height:auto;" alt="Figura"/></div>';
        }
        
        if (!empty($f['nota']) && trim($f['nota']) !== '') $html .= '<div class="figure-note" style="font-size:0.9em; font-style:italic; margin-top:0.5em; text-align:left;"><i>Nota.</i> ' . htmlspecialchars($f['nota']) . '</div>';
        $html .= '</div>';
        return $html;
    }
}
