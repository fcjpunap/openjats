<?php
/**
 * Generador de PDF simplificado desde artículo marcado
 * Genera HTML que puede convertirse a PDF con herramientas del navegador
 */

require_once __DIR__ . '/../models/Article.php';

class PDFGenerator {
    private $articleModel;
    private $config;
    
    public function __construct() {
        $this->articleModel = new Article();
        $this->config = require __DIR__ . '/../../config/config.php';
    }
    
    /**
     * Generar PDF (como HTML print-ready)
     */
    public function generate($articleId) {
        $article = $this->articleModel->getById($articleId);
        
        if (!$article) {
            return ['success' => false, 'message' => 'Artículo no encontrado'];
        }
        
        // Obtener datos completos
        $authors = $this->articleModel->getAuthors($articleId);
        $sections = $this->articleModel->getSections($articleId);
        $tables = $this->articleModel->getTables($articleId);
        $figures = $this->articleModel->getFigures($articleId);
        $references = $this->articleModel->getReferences($articleId);
        
        // Generar HTML optimizado para impresión/PDF
        $html = $this->generatePrintHTML($article, $authors, $sections, $tables, $figures, $references);
        
        // Guardar HTML
        $filename = 'article_' . $articleId . '_print.html';
        $filepath = $this->config['paths']['articles'] . $filename;
        
        if (file_put_contents($filepath, $html) === false) {
            return ['success' => false, 'message' => 'Error al guardar archivo PDF'];
        }
        
        return [
            'success' => true,
            'message' => 'PDF generado exitosamente (abrir HTML y usar Ctrl+P → Guardar como PDF)',
            'file' => $filename,
            'path' => $filepath,
            'download_url' => 'articles/' . $filename
        ];
    }
    
    /**
     * Generar HTML optimizado para impresión
     */
    private function generatePrintHTML($article, $authors, $sections, $tables, $figures, $references) {
        $authorsHTML = $this->generateAuthorsHTML($authors);
        $sectionsHTML = $this->generateSectionsHTML($sections);
        $tablesHTML = $this->generateTablesHTML($tables);
        $figuresHTML = $this->generateFiguresHTML($figures);
        $referencesHTML = $this->generateReferencesHTML($references);
        
        return '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($article['title']) . '</title>
    <style>
        @media print {
            @page { margin: 2cm; }
            body { margin: 0; }
            .no-print { display: none; }
        }
        
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            line-height: 1.6;
            max-width: 21cm;
            margin: 0 auto;
            padding: 2cm;
            color: #000;
            background: #fff;
        }
        
        h1 {
            font-size: 18pt;
            font-weight: bold;
            text-align: center;
            margin-bottom: 1cm;
            page-break-after: avoid;
        }
        
        .authors {
            text-align: center;
            font-style: italic;
            margin-bottom: 1.5cm;
            page-break-after: avoid;
        }
        
        .author {
            margin: 0.2cm 0;
        }
        
        .affiliation {
            font-size: 10pt;
            color: #555;
        }
        
        h2 {
            font-size: 14pt;
            font-weight: bold;
            margin-top: 1cm;
            margin-bottom: 0.5cm;
            page-break-after: avoid;
        }
        
        h3 {
            font-size: 12pt;
            font-weight: bold;
            margin-top: 0.75cm;
            margin-bottom: 0.25cm;
            page-break-after: avoid;
        }
        
        p {
            text-align: justify;
            margin: 0.5cm 0;
            orphans: 3;
            widows: 3;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0.75cm 0;
            page-break-inside: avoid;
        }
        
        th, td {
            border: 1px solid #000;
            padding: 0.25cm;
            text-align: left;
        }
        
        th {
            background: #f0f0f0;
            font-weight: bold;
        }
        
        figure {
            page-break-inside: avoid;
            margin: 0.75cm 0;
            text-align: center;
        }
        
        figcaption {
            font-size: 10pt;
            font-style: italic;
            margin-top: 0.25cm;
        }
        
        .references {
            margin-top: 1.5cm;
        }
        
        .references ol {
            list-style-type: none;
            counter-reset: ref-counter;
            padding-left: 0;
        }
        
        .references li {
            counter-increment: ref-counter;
            margin: 0.5cm 0;
            text-indent: -1cm;
            padding-left: 1cm;
        }
        
        .references li:before {
            content: "[" counter(ref-counter) "] ";
            font-weight: bold;
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .print-button:hover {
            background: #1e40af;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">🖨️ Imprimir / Guardar como PDF</button>
    
    <article>
        <header>
            <h1>' . htmlspecialchars($article['title']) . '</h1>
            ' . $authorsHTML . '
        </header>
        
        <main>
            ' . $sectionsHTML . '
            ' . $tablesHTML . '
            ' . $figuresHTML . '
        </main>
        
        ' . $referencesHTML . '
    </article>
    
    <script class="no-print">
        // Auto-print al cargar (opcional)
        // window.onload = function() { setTimeout(function() { window.print(); }, 500); };
    </script>
</body>
</html>';
    }
    
    private function generateAuthorsHTML($authors) {
        if (empty($authors)) {
            return '';
        }
        
        $html = '<div class="authors">';
        foreach ($authors as $author) {
            $name = trim(($author['given_names'] ?? '') . ' ' . ($author['surname'] ?? ''));
            $html .= '<div class="author">' . htmlspecialchars($name);
            
            if (!empty($author['affiliation'])) {
                $html .= '<br><span class="affiliation">' . htmlspecialchars($author['affiliation']) . '</span>';
            }
            
            $html .= '</div>';
        }
        $html .= '</div>';
        
        return $html;
    }
    
    private function generateSectionsHTML($sections) {
        if (empty($sections)) {
            return '';
        }
        
        $html = '';
        foreach ($sections as $section) {
            $level = $section['level'] ?? 1;
            $tag = 'h' . min($level + 1, 3); // h2, h3
            
            $html .= '<section>';
            $html .= '<' . $tag . '>' . htmlspecialchars($section['title']) . '</' . $tag . '>';
            $html .= '<div class="section-content">' . ($section['content'] ?? '') . '</div>';
            $html .= '</section>';
        }
        
        return $html;
    }
    
    private function generateTablesHTML($tables) {
        if (empty($tables)) {
            return '';
        }
        
        $html = '';
        foreach ($tables as $table) {
            $html .= '<figure class="table-figure">';
            
            if (!empty($table['caption'])) {
                $html .= '<figcaption><strong>Tabla ' . ($table['table_number'] ?? '') . '.</strong> ' . 
                         htmlspecialchars($table['caption']) . '</figcaption>';
            }
            
            $html .= $table['content'] ?? '';
            $html .= '</figure>';
        }
        
        return $html;
    }
    
    private function generateFiguresHTML($figures) {
        if (empty($figures)) {
            return '';
        }
        
        $html = '';
        foreach ($figures as $figure) {
            $html .= '<figure>';
            
            if (!empty($figure['file_path'])) {
                $html .= '<img src="' . htmlspecialchars($figure['file_path']) . '" alt="' . 
                         htmlspecialchars($figure['caption'] ?? '') . '" style="max-width: 100%;">';
            }
            
            if (!empty($figure['caption'])) {
                $html .= '<figcaption><strong>Figura ' . ($figure['figure_number'] ?? '') . '.</strong> ' . 
                         htmlspecialchars($figure['caption']) . '</figcaption>';
            }
            
            $html .= '</figure>';
        }
        
        return $html;
    }
    
    private function generateReferencesHTML($references) {
        if (empty($references)) {
            return '';
        }
        
        $html = '<section class="references">';
        $html .= '<h2>Referencias</h2>';
        $html .= '<ol>';
        
        foreach ($references as $ref) {
            $html .= '<li>' . htmlspecialchars($ref['citation'] ?? '') . '</li>';
        }
        
        $html .= '</ol>';
        $html .= '</section>';
        
        return $html;
    }
}
