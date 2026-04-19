<?php
/**
 * TOC Generator - Generates a Table of Contents PDF for a filtered list of articles
 */

require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../models/Database.php';

if (file_exists(__DIR__ . '/tcpdf/tcpdf.php')) {
    require_once __DIR__ . '/tcpdf/tcpdf.php';
}

class TOCPDF extends TCPDF {
    public $journalData = [];
    
    public function Header() {
        $jName = $this->journalData['title'] ?? 'REVISTA SIN NOMBRE';
        $jNameHtml = mb_strtoupper($jName);
        if (strpos(mb_strtolower($jName), 'revista de derecho de la universidad') !== false) {
            $jNameHtml = "REVISTA DE DERECHO<br/><span style=\"font-size:7pt; font-weight:normal;\">de la Universidad Nacional del Altiplano</span>";
        }

        $issnOnline = htmlspecialchars($this->journalData['issn_electronic'] ?? '');
        $issnPrint = htmlspecialchars($this->journalData['issn_print'] ?? '');
        $url = htmlspecialchars($this->journalData['url'] ?? '');
        $publisher = htmlspecialchars($this->journalData['publisher'] ?? '');
        
        $logoLeft = '';
        if (!empty($this->journalData['logo_path'])) {
            $logoLeft = realpath(__DIR__ . '/../../public/' . ltrim($this->journalData['logo_path'], '/'));
        }
        if (!$logoLeft || !file_exists($logoLeft)) {
            $logoLeft = realpath(__DIR__ . '/../../public/journal.jpeg') ?: 'https://fcjp.derecho.unap.edu.pe/catg/jats-assistant/public/journal.jpeg';
        }
        
        $logoRight = realpath(__DIR__ . '/../../public/logofcjp.png') ?: 'https://fcjp.derecho.unap.edu.pe/catg/jats-assistant/public/logofcjp.png';
        
        $html = '<table width="100%" style="border-bottom:1px solid #777; padding-bottom:3px; font-family: \'EB Garamond\', Garamond, times, serif;"><tr>';
        $html .= '<td width="15%" align="left"><img src="'.$logoLeft.'" height="35" border="0"></td>';
        $html .= '<td width="70%" align="center" style="font-size:7pt; line-height:1.2;"><strong>' . $jNameHtml . '</strong><br/>';
        
        $issns = [];
        if ($issnPrint) $issns[] = "ISSN Print: " . $issnPrint;
        if ($issnOnline) $issns[] = "ISSN Online: " . $issnOnline;
        if (count($issns) > 0) $html .= implode(' | ', $issns) . '<br/>';
        
        if ($url) $html .= $url . '<br/>';
        if ($publisher) $html .= $publisher . '</td>';
        $html .= '<td width="15%" align="right"><img src="'.$logoRight.'" height="35"></td>';
        $html .= '</tr></table>';
        
        $this->SetY(8);
        $this->writeHTMLCell(0, 0, 15, 8, $html, 0, 1, 0, true, 'C', true);
    }
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, $this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

class TOCGenerator {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function generate($articleIds) {
        $pdf = new TOCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Fetch valid journal matching the first requested article instead of random global journal
        $journal = [];
        if (!empty($articleIds)) {
            $firstArt = $articleIds[0];
            $q = "SELECT j.* FROM articles a 
                  LEFT JOIN issues i ON a.issue_id = i.id 
                  LEFT JOIN volumes v ON i.volume_id = v.id 
                  LEFT JOIN journals j ON v.journal_id = j.id 
                  WHERE a.article_id = ? LIMIT 1";
            $journalItem = $this->db->fetchOne($q, [$firstArt]);
            if ($journalItem) $journal = $journalItem;
        }
        
        // Fallback
        if (empty($journal)) {
            $journal = $this->db->fetchOne("SELECT * FROM journals WHERE active = TRUE LIMIT 1") ?: [];
        }
        
        $pdf->journalData = $journal;
        
        $pdf->SetCreator('OpenJATS');
        $pdf->SetAuthor($journal['title'] ?? 'Journal Editor');
        $pdf->SetTitle('Tabla de Contenido / Table of Contents');
        
        $pdf->SetMargins(20, 35, 20);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(15);
        $pdf->SetAutoPageBreak(TRUE, 20);
        $pdf->AddPage();
        
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Índice de Contenido / Table of Contents', 0, 1, 'C');
        $pdf->Ln(2);
        
        if (!empty($articleIds)) {
                $inClause = implode(',', array_fill(0, count($articleIds), '?'));
            $sql = "
                SELECT a.*, 
                       v.volume_number, 
                       i.issue_number, 
                       v.year
                FROM articles a 
                LEFT JOIN issues i ON a.issue_id = i.id 
                LEFT JOIN volumes v ON i.volume_id = v.id 
                WHERE a.article_id IN ($inClause)
                ORDER BY v.year DESC, v.volume_number DESC, i.issue_number DESC, 
                         CASE WHEN LOWER(a.article_type) LIKE '%editor%' THEN 0 ELSE 1 END ASC,
                         a.article_type ASC, a.title ASC
            ";
            
            $articles = $this->db->fetchAll($sql, $articleIds);
            
            $currentIssue = null;
            $currentSection = null;
            
            foreach ($articles as $art) {
                // Formatting Issue (Bilingual)
                $issueStr = '';
                if ($art['volume_number'] && $art['issue_number']) {
                    $issueStr = "Vol. {$art['volume_number']} Núm. {$art['issue_number']} / Vol. {$art['volume_number']} No. {$art['issue_number']}";
                    if ($art['year']) $issueStr .= " ({$art['year']})";
                } else if ($art['issue_id']) {
                    $issueStr = "ID " . $art['issue_id'];
                } else {
                    $issueStr = "Volumen y Número sin asignar / Unassigned Volume and Issue";
                }
                
                // Formatting Section - force upper case for comparison
                $sectionStr = mb_strtoupper(trim($art['article_type'] ?: 'Sección sin asignar'));
                
                // Track Headers Break by Issue
                if ($currentIssue !== $issueStr) {
                    $currentIssue = $issueStr;
                    $pdf->Ln(4);
                    $pdf->SetFont('helvetica', 'B', 12);
                    $pdf->SetFillColor(235, 242, 250);
                    $pdf->Cell(0, 8, ' ' . $currentIssue, 0, 1, 'L', true);
                    $pdf->Ln(2);
                    // Force resetting section text after issue change
                    $currentSection = null; 
                }
                
                // Track Headers Break by Section
                if ($currentSection !== $sectionStr) {
                    $currentSection = $sectionStr;
                    $pdf->SetFont('helvetica', 'B', 11);
                    $pdf->SetTextColor(100, 100, 100);
                    $pdf->Cell(0, 7, '  ' . $currentSection, 0, 1, 'L');
                    $pdf->SetTextColor(0, 0, 0);
                }
                
                // Generating table output for this article
                $html = '<table width="100%" cellpadding="3" style="border-bottom: 1px dotted #ccc;">';
                
                $titleEs = strip_tags($art['title'] ?? 'Sin título');
                $titleEn = (!empty($art['title_en'])) ? strip_tags($art['title_en']) : '';
                
                $meta = [];
                // Pagina (Bilingual)
                if (!empty($art['pagination']) || !empty($art['pages'])) {
                    $meta[] = 'Páginas / Pages: ' . htmlspecialchars($art['pagination'] ?? $art['pages'] ?? '');
                } else {
                    $meta[] = 'Páginas / Pages: [Por asignar / Unassigned]';
                }
                
                // DOI
                if (!empty($art['doi'])) {
                    $cleanDoi = preg_replace('/^https?:\/\/(dx\.)?doi\.org\//i', '', $art['doi']);
                    $meta[] = 'DOI: <a href="https://doi.org/' . htmlspecialchars($cleanDoi) . '" style="color:#000000; text-decoration:none;">' . htmlspecialchars($cleanDoi) . '</a>';
                } else {
                    $meta[] = 'DOI: [Por asignar / Unassigned]';
                }
                
                $metaStr = count($meta) > 0 ? implode('  |  ', $meta) : '';
                
                $html .= '<tr><td width="100%" style="padding-left:15px;">';
                $html .= '<div style="font-family:times; font-size:11pt; text-align:justify; margin-bottom:2px;"><b>' . htmlspecialchars($titleEs) . '</b></div>';
                
                if ($titleEn) {
                    $html .= '<div style="font-family:times; font-size:11pt; color:#444; font-style:italic; text-align:justify; margin-bottom:2px;">' . htmlspecialchars($titleEn) . '</div>';
                }
                
                if ($metaStr) {
                    $html .= '<div style="font-family:helvetica; font-size:9pt; color:#444; text-align:left;">' . $metaStr . '</div>';
                }
                $html .= '</td></tr></table>';
                
                $pdf->writeHTML($html, true, false, true, false, '');
            }
        } else {
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(0, 10, 'No hay artículos para generar el índice.', 0, 1, 'C');
        }
        
        $pdf->Output('indice_resumen.pdf', 'I');
    }
}
