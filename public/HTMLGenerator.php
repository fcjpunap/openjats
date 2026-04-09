<?php
/**
 * Generador de HTML desde artículo marcado
 * Genera HTML5 limpio y semántico
 */

require_once __DIR__ . '/../models/Article.php';

class HTMLGenerator {
    private $articleModel;
    private $config;
    
    public function __construct() {
        $this->articleModel = new Article();
        $this->config = require __DIR__ . '/../../config/config.php';
    }
    
    /**
     * Generar HTML desde artículo
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
        
        // Generar HTML
        $html = $this->generateHTML($article, $authors, $sections, $tables, $figures, $references);
        
        // Guardar archivo
        $filename = 'article_' . $articleId . '.html';
        $filepath = $this->config['paths']['articles'] . $filename;
        
        if (file_put_contents($filepath, $html) === false) {
            return ['success' => false, 'message' => 'Error al guardar archivo HTML'];
        }
        
        return [
            'success' => true,
            'message' => 'HTML generado exitosamente',
            'file' => $filename,
            'path' => $filepath,
            'download_url' => 'articles/' . $filename
        ];
    }
    
    /**
     * Generar HTML completo
     */
    private function generateHTML($article, $authors, $sections, $tables, $figures, $references) {
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
    <meta name="description" content="' . htmlspecialchars(substr(strip_tags($article['abstract'] ?? ''), 0, 160)) . '">
    <title>' . htmlspecialchars($article['title']) . '</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #1f2937;
            background: #f9fafb;
            padding: 2rem 1rem;
        }
        
        article {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 3rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        
        header {
            margin-bottom: 3rem;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 2rem;
        }
        
        h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }
        
        .authors {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .author {
            display: flex;
            flex-direction: column;
        }
        
        .author-name {
            font-weight: 600;
            color: #374151;
        }
        
        .author-affiliation {
            font-size: 0.875rem;
            color: #6b7280;
            font-style: italic;
        }
        
        h2 {
            font-size: 1.875rem;
            font-weight: 600;
            color: #111827;
            margin-top: 2.5rem;
            margin-bottom: 1rem;
        }
        
        h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #374151;
            margin-top: 2rem;
            margin-bottom: 0.75rem;
        }
        
        p {
            margin: 1rem 0;
            text-align: justify;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 2rem 0;
            font-size: 0.875rem;
        }
        
        th, td {
            border: 1px solid #e5e7eb;
            padding: 0.75rem;
            text-align: left;
        }
        
        th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        
        figure {
            margin: 2rem 0;
            text-align: center;
        }
        
        figure img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        figcaption {
            margin-top: 0.75rem;
            font-size: 0.875rem;
            color: #6b7280;
            font-style: italic;
        }
        
        .references {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 2px solid #e5e7eb;
        }
        
        .references ol {
            list-style-position: outside;
            padding-left: 1.5rem;
        }
        
        .references li {
            margin: 0.75rem 0;
            font-size: 0.875rem;
        }
        
        @media (max-width: 768px) {
            article {
                padding: 2rem 1.5rem;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
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
            
            $html .= '<div class="author">';
            $html .= '<span class="author-name">' . htmlspecialchars($name) . '</span>';
            
            if (!empty($author['affiliation'])) {
                $html .= '<span class="author-affiliation">' . htmlspecialchars($author['affiliation']) . '</span>';
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
                         htmlspecialchars($figure['caption'] ?? '') . '">';
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
