<?php
/**
 * Modelo Article - Gestión de artículos
 */

require_once __DIR__ . '/Database.php';

class Article {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($data) {
        // Generar article_id único si no se proporciona
        if (!isset($data['article_id'])) {
            $data['article_id'] = $this->generateArticleId();
        }
        
        try {
            $articleId = $this->db->insert('articles', $data);
            return ['success' => true, 'article_id' => $articleId];
        } catch (Exception $e) {
            error_log("Error creating article: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al crear artículo: ' . $e->getMessage()];
        }
    }
    
    public function getById($id) {
        $article = $this->db->fetchOne(
            "SELECT a.*, i.issue_number, v.volume_number, v.year, 
                    j.title as journal_title, j.issn_electronic as issn, j.issn_print, j.base_url as journal_url, j.logo_path, j.publisher
             FROM articles a
             LEFT JOIN issues i ON a.issue_id = i.id
             LEFT JOIN volumes v ON i.volume_id = v.id
             LEFT JOIN journals j ON v.journal_id = j.id
             WHERE a.id = :id",
            ['id' => $id]
        );
        
        // Si no se asoció a un issue, traemos la info global de la revista (si existe 1 sola)
        if ($article && empty($article['journal_title'])) {
            $journal = $this->db->fetchOne("SELECT * FROM journals ORDER BY id DESC LIMIT 1");
            if ($journal) {
                $article['journal_title'] = $journal['title'];
                $article['issn'] = $journal['issn_electronic'];
                $article['issn_print'] = $journal['issn_print'];
                $article['journal_url'] = $journal['base_url'];
                $article['logo_path'] = $journal['logo_path'];
                $article['publisher'] = $journal['publisher'];
            }
        }
        
        return $article;
    }
    
    public function getByArticleId($articleId) {
        return $this->db->fetchOne(
            "SELECT * FROM articles WHERE article_id = :article_id",
            ['article_id' => $articleId]
        );
    }
    
    public function getAll($filters = []) {
        $where = [];
        $params = [];
        
        if (!empty($filters['status'])) {
            $where[] = "status = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['issue_id'])) {
            $where[] = "issue_id = :issue_id";
            $params['issue_id'] = $filters['issue_id'];
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        return $this->db->fetchAll(
            "SELECT a.*, u.full_name as uploaded_by_name,
                    i.issue_number, v.volume_number, v.year
             FROM articles a
             LEFT JOIN users u ON a.uploaded_by = u.id
             LEFT JOIN issues i ON a.issue_id = i.id
             LEFT JOIN volumes v ON i.volume_id = v.id
             {$whereClause}
             ORDER BY a.created_at DESC",
            $params
        );
    }
    
    public function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update('articles', $data, 'id = :id', ['id' => $id]);
    }
    
    public function delete($id) {
        return $this->db->delete('articles', 'id = :id', ['id' => $id]);
    }
    
    // Gestión de autores
    public function addAuthor($articleId, $authorData) {
        $authorData['article_id'] = $articleId;
        return $this->db->insert('authors', $authorData);
    }
    
    public function getAuthors($articleId) {
        return $this->db->fetchAll(
            "SELECT * FROM authors WHERE article_id = :article_id ORDER BY author_order",
            ['article_id' => $articleId]
        );
    }
    
    public function deleteAuthors($articleId) {
        return $this->db->delete('authors', 'article_id = :article_id', ['article_id' => $articleId]);
    }
    
    // Gestión de afiliaciones
    public function addAffiliation($articleId, $affData) {
        $affData['article_id'] = $articleId;
        return $this->db->insert('affiliations', $affData);
    }
    
    public function getAffiliations($articleId) {
        return $this->db->fetchAll(
            "SELECT * FROM affiliations WHERE article_id = :article_id",
            ['article_id' => $articleId]
        );
    }
    
    public function deleteAffiliations($articleId) {
        return $this->db->delete('affiliations', 'article_id = :article_id', ['article_id' => $articleId]);
    }
    
    // Gestión de secciones
    public function addSection($sectionData) {
        return $this->db->insert('article_sections', $sectionData);
    }
    
    public function getSections($articleId) {
        return $this->db->fetchAll(
            "SELECT * FROM article_sections WHERE article_id = :article_id ORDER BY section_order",
            ['article_id' => $articleId]
        );
    }
    
    public function deleteSections($articleId) {
        return $this->db->delete('article_sections', 'article_id = :article_id', ['article_id' => $articleId]);
    }
    
    // Gestión de tablas
    public function addTable($tableData) {
        return $this->db->insert('article_tables', $tableData);
    }
    
    public function getTables($articleId) {
        return $this->db->fetchAll(
            "SELECT * FROM article_tables WHERE article_id = :article_id ORDER BY table_order",
            ['article_id' => $articleId]
        );
    }
    
    public function deleteTables($articleId) {
        return $this->db->delete('article_tables', 'article_id = :article_id', ['article_id' => $articleId]);
    }
    
    // Gestión de figuras
    public function addFigure($figureData) {
        return $this->db->insert('article_figures', $figureData);
    }
    
    public function getFigures($articleId) {
        return $this->db->fetchAll(
            "SELECT * FROM article_figures WHERE article_id = :article_id ORDER BY figure_order",
            ['article_id' => $articleId]
        );
    }
    
    public function deleteFigures($articleId) {
        return $this->db->delete('article_figures', 'article_id = :article_id', ['article_id' => $articleId]);
    }
    
    // Gestión de referencias
    public function addReference($refData) {
        return $this->db->insert('article_references', $refData);
    }
    
    public function getReferences($articleId) {
        return $this->db->fetchAll(
            "SELECT * FROM article_references WHERE article_id = :article_id ORDER BY reference_order",
            ['article_id' => $articleId]
        );
    }
    
    public function deleteReferences($articleId) {
        return $this->db->delete('article_references', 'article_id = :article_id', ['article_id' => $articleId]);
    }
    
    // Gestión de notas al pie
    public function getFootnotes($articleId) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM article_footnotes WHERE article_id = :article_id ORDER BY fn_order",
                ['article_id' => $articleId]
            );
        } catch(Exception $e) {
            return []; // Tabla no existe
        }
    }
    
    // Gestión de archivos
    public function addFile($fileData) {
        return $this->db->insert('article_files', $fileData);
    }
    
    public function getFiles($articleId, $fileType = null) {
        $sql = "SELECT * FROM article_files WHERE article_id = :article_id";
        $params = ['article_id' => $articleId];
        
        if ($fileType) {
            $sql .= " AND file_type = :file_type";
            $params['file_type'] = $fileType;
        }
        
        $sql .= " ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, $params);
    }
    
    // Guardar marcación
    public function saveMarkup($articleId, $markupData, $userId) {
        // Limpiar cuadrados residuales antes de guardar
        if (isset($markupData['sections'])) {
            $markupData['sections'] = array_map(function($sec) {
                if (isset($sec['content'])) {
                   $sec['content'] = preg_replace_callback('/<span[^>]*style=["\'][^"\']*(?:rgb\(\s*239,\s*246,\s*255\s*\)|rgb\(\s*240,\s*253,\s*244\s*\)|eff6ff|f0fdf4|background\s*:\s*#[a-f0-9]+)[^"\']*["\'][^>]*>(.*?)<\/span>/is', function($m) {
                      $innerClean = trim(strip_tags(str_ireplace(['&nbsp;', '&#160;', '&#xa0;', '&amp;nbsp;'], ' ', $m[1])));
                      return ($innerClean === '' || preg_match('/^\[\s*\]$/', $innerClean)) ? '' : $m[0];
                   }, $sec['content']);
                }
                return $sec;
            }, $markupData['sections']);
        }

        $jsonMarkup = json_encode($markupData, JSON_UNESCAPED_UNICODE);
        if ($jsonMarkup === false) {
            error_log("Error al codificar markup_data como JSON para el artículo " . $articleId);
            return;
        }

        $data = [
            'article_id' => $articleId,
            'markup_data' => $jsonMarkup,
            'saved_by' => $userId
        ];

        // Se comenta la eliminación para mantener un historial de versiones
        // $this->db->delete('article_markup', 'article_id = :article_id', ['article_id' => $articleId]);
        $this->db->insert('article_markup', $data);
    }
    
    public function getMarkup($articleId) {
        $markup = $this->db->fetchOne(
            "SELECT * FROM article_markup WHERE article_id = :article_id ORDER BY id DESC LIMIT 1",
            ['article_id' => $articleId]
        );
        
        if ($markup && $markup['markup_data']) {
            $markup['markup_data'] = json_decode($markup['markup_data'], true);
        }
        
        return $markup;
    }
    
    public function getMarkupVersions($articleId) {
        return $this->db->fetchAll(
            "SELECT id, created_at FROM article_markup WHERE article_id = :article_id ORDER BY id DESC",
            ['article_id' => $articleId]
        );
    }
    
    public function getMarkupById($markupId) {
        $markup = $this->db->fetchOne(
            "SELECT * FROM article_markup WHERE id = :id",
            ['id' => $markupId]
        );
        
        if ($markup && $markup['markup_data']) {
            $markup['markup_data'] = json_decode($markup['markup_data'], true);
        }
        
        return $markup;
    }
    
    private function generateArticleId() {
        // Generar ID único basado en timestamp
        return date('Ymd') . '_' . substr(uniqid(), -6);
    }
}
