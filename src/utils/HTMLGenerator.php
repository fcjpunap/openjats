<?php
/**
 * Generador de HTML para visualización web del artículo
 */

require_once __DIR__ . '/../models/Article.php';

class HTMLGenerator {
    private $articleModel;
    private $usedTableLabels = [];
    private $usedFigureLabels = [];
    
    public function __construct() {
        $this->articleModel = new Article();
    }
    
    /**
     * Generar HTML para visualización web
     */
    public function generateHTML($articleId) {
        $article = $this->articleModel->getById($articleId);
        
        if (!$article) {
            throw new Exception("Artículo no encontrado");
        }
        
        $authors = $this->articleModel->getAuthors($articleId);
        $sections = $this->articleModel->getSections($articleId);
        $references = $this->articleModel->getReferences($articleId);
        $footnotes = $this->articleModel->getFootnotes($articleId);
        
        $tables = $this->articleModel->getTables($articleId);
        $figures = $this->articleModel->getFigures($articleId);
        
        $markup = $this->articleModel->getMarkup($articleId);
        if ($markup && isset($markup['markup_data'])) {
            $tables = $markup['markup_data']['tables'] ?? [];
            $figures = $markup['markup_data']['images'] ?? [];
        }
        
        $this->usedTableLabels = [];
        $this->usedFigureLabels = [];
        
        $html = $this->buildHTML($article, $authors, $sections, $tables, $figures, $references, $footnotes);
        
        // Guardar archivo
        $config = require __DIR__ . '/../../config/config.php';
        $articleDir = $config['paths']['articles'] . $article['article_id'];
        
        if (!is_dir($articleDir)) {
            mkdir($articleDir, 0755, true);
        }
        
        $htmlPath = $articleDir . '/index.html';
        file_put_contents($htmlPath, $html);
        
        // Guardar en BD
        $this->articleModel->addFile([
            'article_id' => $articleId,
            'file_type' => 'html',
            'file_path' => $htmlPath,
            'file_size' => strlen($html),
            'mime_type' => 'text/html',
        ]);
        
        return [
            'success' => true,
            'file_path' => $htmlPath,
            'download_url' => 'articles/' . $article['article_id'] . '/index.html'
        ];
    }
    
    private function buildHTML($article, $authors, $sections, $tables, $figures, $references, $footnotes = []) {
        $journal = $article['journal_title'] ?? 'Revista Académica';
        $vol = $article['volume_number'] ?? '';
        $issue = $article['issue_number'] ?? '';
        $year = $article['year'] ?? date('Y');
        
        ob_start();
        ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($article['abstract'] ?? '') ?>">
    <meta name="keywords" content="<?= htmlspecialchars($article['keywords'] ?? '') ?>">
    <meta name="author" content="<?= htmlspecialchars(implode(', ', array_map(function($a) {
        return $a['given_names'] . ' ' . $a['surname'];
    }, $authors))) ?>">
    
    <title><?= htmlspecialchars($article['title'] ?? '') ?> | <?= htmlspecialchars($journal ?? '') ?></title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Georgia, 'Times New Roman', serif;
            line-height: 1.8;
            color: #333;
            background: #f5f5f5;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        header {
            background: #ffffff;
            color: #333;
            padding: 20px 40px;
            border-bottom: 2px solid #2c3e50;
            font-family: 'Garamond', 'Times New Roman', serif;
        }
        
        header .journal-info {
            font-size: 14px;
            opacity: 0.9;
            text-align: center;
        }
        
        .article-header {
            padding: 40px;
            border-bottom: 3px solid #2c3e50;
        }
        
        h1 {
            font-size: 32px;
            margin-bottom: 20px;
            color: #2c3e50;
            line-height: 1.3;
        }
        
        .authors {
            margin: 20px 0;
        }
        
        .author {
            margin-bottom: 15px;
        }
        
        .author-name {
            font-weight: bold;
            font-size: 16px;
        }
        
        .affiliation, .email, .orcid {
            font-size: 14px;
            color: #666;
            margin: 3px 0;
        }
        
        .article-meta {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 14px;
            color: #666;
        }
        
        .article-content {
            padding: 40px;
        }
        
        .abstract {
            background: #f8f9fa;
            padding: 25px;
            margin: 30px 0;
            border-left: 4px solid #2c3e50;
        }
        
        .abstract h2 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        
        .keywords {
            margin: 20px 0;
            font-size: 14px;
        }
        
        .keywords strong {
            color: #2c3e50;
        }
        
        .section {
            margin: 40px 0;
        }
        
        h2 {
            font-size: 24px;
            color: #1b5e20;
            margin: 30px 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #ecf0f1;
        }
        
        h3 {
            font-size: 20px;
            color: #2e7d32;
            margin: 25px 0 15px 0;
        }
        
        p {
            margin: 15px 0;
            text-align: justify;
        }
        
        a {
            color: #000000;
            text-decoration: none;
        }
        
        a:hover {
            text-decoration: underline;
        }
        
        .table-container {
            margin: 30px 0;
            overflow-x: auto;
        }
        
        .table-label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .table-caption {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }
        
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            margin: 15px 0;
            display: block;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        
        th {
            background: #1b5e20;
            color: white;
            font-weight: bold;
        }
        
        tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .figure-container {
            margin: 30px 0;
            text-align: center;
        }
        
        .figure-label {
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .figure-container img {
            max-width: 100%;
            height: auto;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .figure-caption {
            font-size: 14px;
            color: #666;
            margin-top: 10px;
        }
        
        .references {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 3px solid #1b5e20;
        }
        
        .reference {
            margin: 15px 0;
            padding-left: 30px;
            text-indent: -30px;
            font-size: 14px;
        }
        
        footer {
            background: #1b5e20;
            color: white;
            padding: 20px 40px;
            text-align: center;
            font-size: 14px;
        }
        
        @media print {
            body {
                background: white;
            }
            .container {
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header style="display:flex; justify-content: space-between; align-items: center; text-align: center;">
            <div style="width:20%; text-align:left;">
                <img src="https://fcjp.derecho.unap.edu.pe/catg/jats-assistant/public/journal.jpeg" style="max-height:80px; max-width:100%;" alt="Logo Izquierda">
            </div>
            <div class="journal-info" style="width:60%; line-height: 1.4;">
                <?php
                    $jNameHtml = mb_strtoupper($journal);
                    if (strpos(mb_strtolower($journal), 'revista de derecho de la universidad') !== false) {
                        $jNameHtml = "<strong>REVISTA DE DERECHO</strong><br><span style=\"font-size:12px; font-weight:normal;\">de la Universidad Nacional del Altiplano</span>";
                    } else {
                        $jNameHtml = "<strong>" . $jNameHtml . "</strong>";
                    }
                    echo $jNameHtml;
                ?>
                <br>
                ISSN Print: <?= htmlspecialchars($article['issn_print'] ?? 'XXXX-XXXX') ?> | ISSN Online: <?= htmlspecialchars($article['issn'] ?? 'XXXX-XXXX') ?><br>
                <?= htmlspecialchars($article['journal_url'] ?? '') ?><br>
                <?php if ($vol && $issue): ?>
                    Vol. <?= htmlspecialchars($vol) ?>, Núm. <?= htmlspecialchars($issue) ?> (<?= $year ?>)
                <?php endif; ?>
                | DOI: <?= htmlspecialchars($article['doi'] ?? '') ?> <br/> Licencia CC BY 4.0
            </div>
            <div style="width:20%; text-align:right;">
                <img src="https://fcjp.derecho.unap.edu.pe/catg/jats-assistant/public/logofcjp.png" style="max-height:80px; max-width:100%;" alt="Logo Derecha">
            </div>
        </header>
        
        <div class="article-header">
            <?php if (!empty($article['article_type'])): ?>
                <div style="font-size:14px; color:#666; margin-bottom:10px; font-weight:bold; text-transform:uppercase;">
                    Sección: <?= htmlspecialchars($article['article_type'] ?? '') ?>
                </div>
            <?php endif; ?>

            <h1 style="font-family: 'Imprint MT Shadow', serif; color: #006400;"><?= htmlspecialchars($article['title'] ?? '') ?></h1>
            
            <?php if (!empty($article['title_en'])): ?>
                <h1 style="font-family: 'Imprint MT Shadow', serif; color: #90EE90; font-size: 24px; font-style: italic; margin-top: 15px;">
                    <?= htmlspecialchars($article['title_en'] ?? '') ?>
                </h1>
            <?php endif; ?>
            
            <div class="authors">
                <?php foreach ($authors as $author): ?>
                    <div class="author">
                        <div class="author-name">
                            <?= htmlspecialchars($author['given_names'] ?? '') ?> 
                            <?= htmlspecialchars($author['surname'] ?? '') ?>
                            <?php if (!empty($author['is_corresponding']) || (!empty($author['corresponding']))): ?>
                                <span style="margin-left:5px;">✉</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($author['affiliation'])): ?>
                            <div class="affiliation"><?= htmlspecialchars($author['affiliation'] ?? '') ?></div>
                        <?php endif; ?>
                        <?php if (!empty($author['orcid'])): ?>
                            <div class="orcid">
                                <img src="https://orcid.org/sites/default/files/images/orcid_16x16.png" style="height:14px; vertical-align:middle; margin-right:4px;" alt="ORCID">
                                <a href="https://orcid.org/<?= htmlspecialchars($author['orcid'] ?? '') ?>" target="_blank" style="color:inherit; text-decoration:none;">
                                    <?= htmlspecialchars($author['orcid'] ?? '') ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($author['email'])): ?>
                            <div class="email"><?= htmlspecialchars($author['email'] ?? '') ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="article-meta">
                <?php if ($article['received_date']): ?>
                    <span>Recibido: <?= date('d/m/Y', strtotime($article['received_date'])) ?></span>
                <?php endif; ?>
                <?php if ($article['accepted_date']): ?>
                    <span>Aceptado: <?= date('d/m/Y', strtotime($article['accepted_date'])) ?></span>
                <?php endif; ?>
                <?php if ($article['published_date']): ?>
                    <span>Publicado: <?= date('d/m/Y', strtotime($article['published_date'])) ?></span>
                <?php endif; ?>
                <?php if ($article['doi']): ?>
                    <span>DOI: <a href="https://doi.org/<?= htmlspecialchars($article['doi']) ?>" target="_blank">
                        <?= htmlspecialchars($article['doi']) ?>
                    </a></span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="article-content">
            <?php 
                $allowedTags = '<b><i><u><strong><em><a><p><br><ul><li><ol><sup><sub><span><div>';
            ?>
            <?php if ($article['abstract']): ?>
                <div class="abstract">
                    <h2>Resumen</h2>
                    <div style="text-align:justify;">
                        <?= nl2br(strip_tags($article['abstract'], $allowedTags)) ?>
                    </div>
                    
                    <?php if ($article['keywords']): ?>
                        <div class="keywords" style="margin-top:10px;">
                            <strong>Palabras clave:</strong> <?= htmlspecialchars($article['keywords']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($article['abstract_en']): ?>
                <div class="abstract" style="margin-top:20px;">
                    <h2>Abstract</h2>
                    <div style="text-align:justify;">
                        <?= nl2br(strip_tags($article['abstract_en'], $allowedTags)) ?>
                    </div>
                    
                    <?php if ($article['keywords_en']): ?>
                        <div class="keywords" style="margin-top:10px;">
                            <strong>Keywords:</strong> <?= htmlspecialchars($article['keywords_en']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php foreach ($sections as $section): ?>
                <div class="section level-<?= $section['level'] ?? 1 ?>">
                    <?php
                    $heading = 'h' . min($section['level'] ?? 2, 3);
                    ?>
                    <<?= $heading ?>><?= htmlspecialchars($section['title']) ?></<?= $heading ?>>
                    <?php if ($section['content']): ?>
                        <div style="text-align:justify; margin-top:15px; line-height:1.6;">
                            <?php 
                                $content = strip_tags(trim($section['content']), $allowedTags);
                                // Procesar placeholders
                                echo $this->processPlaceholders($content, $tables, $figures);
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <?php foreach ($tables as $table): ?>
                <?php 
                    // Si ya fue insertada mediante un placeholder, no la repetimos al final
                    if (in_array(trim($table['label'] ?? ''), $this->usedTableLabels)) continue;
                ?>
                <div class="table-container">
                    <?php if ($table['label']): ?>
                        <div class="table-label"><?= htmlspecialchars($table['label']) ?></div>
                    <?php endif; ?>
                    <?php 
                        $tCaption = !empty($table['caption']) ? $table['caption'] : (!empty($table['title']) ? $table['title'] : '');
                        if ($tCaption): 
                    ?>
                        <div class="table-caption"><?= htmlspecialchars($tCaption) ?></div>
                    <?php endif; ?>
                    <div class="table-responsive">
                        <?php if (!empty($table['src']) && ($table['type'] ?? '') === 'image'): ?>
                            <img src="<?= $this->resolveImageUrl($table['src']) ?>" style="max-width:100%; height:auto; display:block; margin:0 auto;">
                        <?php else: ?>
                            <?php 
                                $rawHtml = $table['html'] ?? $table['html_content'] ?? '';
                                $tableHtml = str_ireplace('&nbsp;', '&#160;', $rawHtml);
                                // Asegurar ampersands válidos para XHTML (evitar truncación en lectores estrictos)
                                $tableHtml = preg_replace('/&(?![a-zA-Z0-9#]+;)/', '&amp;', $tableHtml);
                                
                                $tableHtml = preg_replace('/<table([^>]*)>/i', '<table$1 border="0" style="width:100%; border-top:1px solid black; border-bottom:1px solid black; border-collapse:collapse;">', $tableHtml);
                                // Línea de cabecera APA 7
                                if (stripos($tableHtml, '<thead') !== false) {
                                    $tableHtml = preg_replace('/<\/thead>/i', '<tr><td colspan="20" style="border-bottom: 1px solid black; padding:0;">&#160;</td></tr></thead>', $tableHtml);
                                } else {
                                    $tableHtml = preg_replace('/<\/tr>/i', '</tr><tr><td colspan="20" style="border-bottom: 1px solid black; padding:0;">&#160;</td></tr>', $tableHtml, 1);
                                }
                                echo $tableHtml;
                            ?>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($table['nota'])): ?>
                        <div class="table-note" style="font-size: 13px; font-style: italic; margin-top: 8px;">
                            <i>Nota.</i> <?= htmlspecialchars($table['nota']) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($table['footer'])): ?>
                        <div class="table-footer" style="font-size: 12px; color: #666; margin-top: 5px;">
                            <?= htmlspecialchars($table['footer']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <?php foreach ($figures as $figure): ?>
                <?php 
                    // Si ya fue insertada mediante un placeholder, no la repetimos al final
                    if (in_array(trim($figure['label'] ?? ''), $this->usedFigureLabels)) continue;
                ?>
                <div class="figure-container" style="text-align: left;">
                    <?php if ($figure['label']): ?>
                        <div class="figure-label" style="text-align: left; font-weight: bold;"><?= htmlspecialchars($figure['label']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($figure['caption'])): ?>
                        <div class="figure-caption" style="text-align: left; font-style: italic; margin-bottom: 10px;"><?= htmlspecialchars($figure['caption']) ?></div>
                    <?php endif; ?>
                    <div style="text-align: center;">
                        <img src="<?= $this->resolveImageUrl($figure['src'] ?? $figure['file_path'] ?? '') ?>" alt="<?= htmlspecialchars($figure['caption'] ?? '') ?>" style="width: <?= htmlspecialchars($figure['width'] ?? '100%') ?>;">
                    </div>
                    <?php if (!empty($figure['nota'])): ?>
                        <div class="figure-note" style="font-size: 13px; font-style: italic; margin-top: 8px; text-align: left;">
                            <i>Nota.</i> <?= htmlspecialchars($figure['nota']) ?>
                        </div>
                    <?php endif; ?>
                </div>
<?php endforeach; ?>
            
            <?php if (!empty($references)): ?>
                <div class="references">
                    <h2>Referencias</h2>
                    <?php foreach ($references as $ref): ?>
                        <div class="reference" id="ref-<?= htmlspecialchars($ref['reference_order']) ?>">
                            <?= htmlspecialchars($ref['full_citation']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php 
            if (!empty($footnotes)): ?>
                <div class="references" style="margin-top:20px; border-top:1px solid #ddd; padding-top:20px;">
                    <h2 style="font-size:18px;">Notas al pie</h2>
                    <?php foreach ($footnotes as $fn): ?>
                        <div id="fn-<?= htmlspecialchars($fn['fn_id'] ?? '') ?>" style="font-size:13px; margin-bottom:10px;">
                            <sup>[<?= htmlspecialchars($fn['fn_id'] ?? '') ?>]</sup> <?= htmlspecialchars($fn['text'] ?? '') ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <footer>
            <p>&copy; <?= $year ?> <?= htmlspecialchars($journal) ?>. Todos los derechos reservados.</p>
            <?php if ($article['doi']): ?>
                <p>DOI: <?= htmlspecialchars($article['doi']) ?></p>
            <?php endif; ?>
        </footer>
    </div>
</body>
</html>
        <?php
        return ob_get_clean();
    }

    private function processPlaceholders($content, $tables, $figures) {
        // Mejorar regex para capturar opcionalmente el <span> envolvente del editor
        $pattern = '/(<span[^>]*?(?:contenteditable=["\']false["\']|style=["\'][^"\']*(?:rgb\(\s*239,\s*246,\s*255\s*\)|rgb\(\s*240,\s*253,\s*244\s*\)|eff6ff|f0fdf4)[^"\']*["\'])[^>]*>)?\s*(\[(?:Tabla|Figura|Table|Figure)\s*[^\]]+\])\s*(<\/span>)?/i';
        
        $content = preg_replace_callback($pattern, function($matches) use ($tables, $figures) {
            $label = strip_tags($matches[2]);
            
            // Buscar en tablas
            foreach ($tables as $t) {
                $tLabel = $t['label'] ?? '';
                if (strcasecmp(trim($tLabel), trim($label, '[]')) === 0) {
                    $this->usedTableLabels[] = trim($tLabel);
                    ob_start();
                    ?>
                    <div class="table-container" style="margin:1em 0; padding:0.5em; border-top:none;">
                        <div class="table-label" style="font-weight:bold;"><?= htmlspecialchars($t['label'] ?? 'Tabla') ?></div>
                        <?php 
                            $tCaptionPlaceholder = !empty($t['caption']) ? $t['caption'] : (!empty($t['title']) ? $t['title'] : '');
                            if ($tCaptionPlaceholder): 
                        ?>
                            <div class="table-caption" style="font-style:italic;font-size:0.9em;margin-bottom:0.5em;"><?= htmlspecialchars($tCaptionPlaceholder) ?></div>
                        <?php endif; ?>
                        <div class="table-responsive">
                            <?php if (!empty($t['src']) && ($t['type'] ?? '') === 'image'): ?>
                                <img src="<?= $this->resolveImageUrl($t['src']) ?>" style="max-width:100%; height:auto; display:block; margin:0 auto;">
                            <?php else: ?>
                                <?= $t['html'] ?? $t['content'] ?? $t['html_content'] ?? '' ?>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($t['nota']) && trim($t['nota']) !== ''): ?>
                            <div class="table-note" style="font-size: 13px; font-style: italic; margin-top: 8px;">
                                <i>Nota.</i> <?= htmlspecialchars($t['nota']) ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($t['footer'])): ?>
                            <div class="table-footer" style="font-size: 12px; color: #666; margin-top: 5px;">
                                <?= htmlspecialchars($t['footer']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php
                    return ob_get_clean();
                }
            }
            
            // Buscar en figuras
            foreach ($figures as $f) {
                $fLabel = $f['label'] ?? '';
                if (strcasecmp(trim($fLabel), trim($label, '[]')) === 0) {
                    $this->usedFigureLabels[] = trim($fLabel);
                    ob_start();
                    ?>
                    <div class="figure-container" style="text-align: left;">
                        <div class="figure-label" style="font-weight:bold; text-align: left;"><?= htmlspecialchars($f['label'] ?? 'Figura') ?></div>
                        <?php if (!empty($f['caption'])): ?>
                            <div class="figure-caption" style="font-style:italic; text-align: left; margin-bottom: 10px;"><?= htmlspecialchars($f['caption']) ?></div>
                        <?php endif; ?>
                        <div style="text-align: center;">
                            <img src="<?= $this->resolveImageUrl($f['src'] ?? $f['file_path'] ?? '') ?>" alt="<?= htmlspecialchars($f['caption'] ?? '') ?>" style="width: <?= htmlspecialchars($f['width'] ?? '100%') ?>;">
                        </div>
                        <?php if (!empty($f['nota']) && trim($f['nota']) !== ''): ?>
                            <div class="figure-note" style="font-size: 13px; font-style: italic; margin-top: 8px; text-align: left;">
                                <i>Nota.</i> <?= htmlspecialchars($f['nota']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php
                    return ob_get_clean();
                }
            }
            
            return $matches[2];
        }, $content);

        // Limpieza final y extremadamente robusta de los cuadrados residuales del editor
        // Buscamos cualquier span que tenga nuestro color de fondo (azul/verde) y que esté vacío o tenga solo espacios
        $content = preg_replace_callback('/<span[^>]*style=["\'][^"\']*(?:rgb\(\s*239,\s*246,\s*255\s*\)|rgb\(\s*240,\s*253,\s*244\s*\)|eff6ff|f0fdf4|background\s*:\s*#[a-f0-9]+)[^"\']*["\'][^>]*>(.*?)<\/span>/is', function($m) {
            $cleaned = trim(strip_tags(str_ireplace(['&nbsp;', '&#160;', '&#xa0;', '&amp;nbsp;'], ' ', $m[1])));
            if ($cleaned === '' || $cleaned === '[]') {
                return '';
            }
            return $m[0];
        }, $content);
        
        return $content;
    }
    private function resolveImageUrl($src) {
        if (empty($src)) return '';
        if (strpos($src, 'http') === 0) return $src;
        
        $config = require __DIR__ . '/../../config/config.php';
        $baseUrl = rtrim($config['app']['url'], '/');
        
        // Limpiar el src de posibles ../ o / iniciales
        $cleanSrc = ltrim($src, './');
        
        return $baseUrl . '/' . $cleanSrc;
    }
}
