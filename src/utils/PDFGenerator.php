<?php
/**
 * Generador de PDF desde XML-JATS
 * Utiliza TCPDF
 */

require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../models/Database.php';

// Cargar TCPDF si existe, de lo contrario esto fallará pero asumismos que se bajó al folder tcpdf
if (file_exists(__DIR__ . '/tcpdf/tcpdf.php')) {
    require_once __DIR__ . '/tcpdf/tcpdf.php';
}

class JATSPDF extends TCPDF
{
    public $articleData = [];

    public function Header()
    {
        $jName = $this->articleData['journal_title'] ?? 'REVISTA DE DERECHO';
        $jNameHtml = mb_strtoupper($jName);
        if (strpos(mb_strtolower($jName), 'revista de derecho de la universidad') !== false) {
            // Replace the first match
            $jNameHtml = "REVISTA DE DERECHO<br/><span style=\"font-size:7pt; font-weight:normal;\">de la Universidad Nacional del Altiplano</span>";
        }

        $issnOnline = htmlspecialchars($this->articleData['issn'] ?? 'XXXX-XXXX');
        $issnPrint = htmlspecialchars($this->articleData['issn_print'] ?? 'XXXX-XXXX');
        $url = htmlspecialchars($this->articleData['journal_url'] ?? '');
        $vol = htmlspecialchars($this->articleData['volume_number'] ?? 'X');
        $iss = htmlspecialchars($this->articleData['issue_number'] ?? 'Y');
        $year = htmlspecialchars($this->articleData['year'] ?? date('Y'));
        $pag = htmlspecialchars($this->articleData['pagination'] ?? 'e-location');
        $doi = htmlspecialchars($this->articleData['doi'] ?? '10.xxxx/xxxx');

        $logoLeft = realpath(__DIR__ . '/../../public/journal.jpeg');
        if (!file_exists($logoLeft))
            $logoLeft = 'https://fcjp.derecho.unap.edu.pe/catg/jats-assistant/public/journal.jpeg';
        $logoRight = realpath(__DIR__ . '/../../public/logofcjp.png');
        if (!file_exists($logoRight))
            $logoRight = 'https://fcjp.derecho.unap.edu.pe/catg/jats-assistant/public/logofcjp.png';

        $html = '<table width="100%" style="border-bottom:1px solid #777; padding-bottom:3px; font-family: \'EB Garamond\', Garamond, times, serif;"><tr>';
        $html .= '<td width="15%" align="left"><img src="' . $logoLeft . '" height="35"></td>';
        $html .= '<td width="70%" align="center" style="font-size:7pt; line-height:1.2;"><strong>' . $jNameHtml . '</strong><br/>';

        $cleanDoi = preg_replace('/^https?:\/\/(dx\.)?doi\.org\//i', '', $doi);
        $doiLink = 'https://doi.org/' . $cleanDoi;

        $html .= 'ISSN Print: ' . $issnPrint . ' | ISSN Online: ' . $issnOnline . '<br/>Journal homepage: <a href="' . $url . '" style="color:#000000; text-decoration:none;">' . $url . '</a><br/>';
        $html .= 'Vol. ' . $vol . ', Núm. ' . $iss . ' (' . $year . '), ' . $pag . ' | DOI: <a href="' . $doiLink . '" style="color:#000000; text-decoration:none;">' . $doi . '</a><br/>This work is licensed under a Creative Commons Attribution 4.0 International License.</td>';
        $html .= '<td width="15%" align="right"><img src="' . $logoRight . '" height="35"></td>';
        $html .= '</tr></table>';

        $this->SetY(8);
        $this->writeHTMLCell(0, 0, 15, 8, $html, 0, 1, 0, true, 'C', true);
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

class PDFGenerator
{
    private $articleModel;
    private $usedTableLabels = [];
    private $usedFigureLabels = [];

    public function __construct()
    {
        $this->articleModel = new Article();
    }

    public function generatePDF($articleId)
    {
        $article = $this->articleModel->getById($articleId);

        if (!$article) {
            throw new Exception("Artículo no encontrado");
        }

        $authors = $this->articleModel->getAuthors($articleId);
        $sections = $this->articleModel->getSections($articleId);
        $references = $this->articleModel->getReferences($articleId);

        $markup = $this->articleModel->getMarkup($articleId);
        if ($markup && isset($markup['markup_data'])) {
            $tables = $markup['markup_data']['tables'] ?? [];
            $figures = $markup['markup_data']['images'] ?? [];
        } else {
            $tables = $this->articleModel->getTables($articleId);
            $figures = $this->articleModel->getFigures($articleId);
        }

        $pdf = new JATSPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->articleData = $article;

        // Metadata
        $pdf->SetCreator('OpenJATS - https://github.com/fcjpunap/openjats');
        $authorString = implode(' - ', array_map(function ($a) {
            return trim($a['given_names'] . ' ' . $a['surname']);
        }, $authors));
        $pdf->SetAuthor(str_replace(['"', "'", '“', '”'], '', $authorString));
        $pdf->SetTitle(str_replace(['"', "'", '“', '”'], '', $article['title']));

        $subject = $article['journal_title'] . (isset($article['volume_number']) ? ', Vol. ' . $article['volume_number'] : '') . (isset($article['issue_number']) ? ', Núm. ' . $article['issue_number'] : '');
        if (!empty($article['doi'])) {
            $cleanDoi = preg_replace('/^https?:\/\/(dx\.)?doi\.org\//i', '', $article['doi'] ?? '');
            $subject .= ' - https://doi.org/' . $cleanDoi;
        }
        $pdf->SetSubject($subject);

        if (!empty($article['keywords'])) {
            // First treat periods, semicolons, and newlines as commas
            $kwString = str_replace(['.', ';', "\r", "\n", '“', '”', '"'], ',', $article['keywords']);
            $kws = array_map('trim', explode(',', $kwString));
            $kws = array_filter(array_unique($kws));
            // Use hyphen instead of commas to prevent PDF viewers from showing structural quotes
            $pdf->SetKeywords(implode(' - ', $kws));
        }

        // Layout
        $pdf->SetMargins(20, 35, 20); // Aumento el margen superior por la cabecera
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(15);
        $pdf->SetAutoPageBreak(TRUE, 20);

        $pdf->AddPage();

        // Sección
        if (!empty($article['article_type'])) {
            $pdf->SetFont('times', 'B', 10);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->Cell(0, 5, 'Sección / Section: ' . mb_strtoupper($article['article_type']), 0, 1, 'L');
            $pdf->Ln(2);
        }

        // Titulo ESP
        $pdf->Ln(2);
        $htmlTitle = '<div style="text-align:center; font-family:\'Imprint MT Shadow\', times, serif; font-size:18pt; color:#006400; font-weight:bold;">' . htmlspecialchars($article['title']) . '</div>';
        $pdf->writeHTMLCell(0, 10, '', '', $htmlTitle, 0, 1, 0, true, 'C', true);

        // Titulo ENG
        if (!empty($article['title_en'])) {
            $pdf->Ln(1);
            $htmlTitleEn = '<div style="text-align:center; font-family:\'Imprint MT Shadow\', times, serif; font-size:16pt; color:#4caf50; font-style:italic; font-weight:bold;">' . htmlspecialchars($article['title_en']) . '</div>';
            $pdf->writeHTMLCell(0, 8, '', '', $htmlTitleEn, 0, 1, 0, true, 'C', true);
        }

        $pdf->SetTextColor(0, 0, 0); // Reset color
        $pdf->Ln(4);

        // Authors
        $pdf->SetFont('times', 'B', 11);
        $hasCorresponding = false;
        foreach ($authors as $author) {
            $authorText = $author['given_names'] . ' ' . $author['surname'];
            if (!empty($author['is_corresponding']) || (!empty($author['corresponding']))) {
                $authorText .= '<sup>*</sup>';
                $hasCorresponding = true;
            }

            $pdf->writeHTMLCell(0, 5, '', '', '<div style="text-align:center;"><b>' . $authorText . '</b></div>', 0, 1, 0, true, 'C', true);

            $subText = '';
            if ($author['affiliation'])
                $subText .= htmlspecialchars($author['affiliation']) . " | ";
            if ($author['email'])
                $subText .= "Email: " . htmlspecialchars($author['email']) . " | ";
            if ($author['orcid']) {
                $orcidClean = preg_replace('/^(https?:\/\/)?(www\.)?orcid\.org\//i', '', $author['orcid']);
                $subText .= '<img src="https://orcid.org/sites/default/files/images/orcid_16x16.png" height="10" /> ORCID: <a href="https://orcid.org/' . htmlspecialchars($orcidClean) . '" style="color:black;text-decoration:none;">' . htmlspecialchars($orcidClean) . '</a>';
            }

            $subText = rtrim($subText, " | ");
            if ($subText) {
                $pdf->SetFont('times', 'I', 9);
                $pdf->SetTextColor(80, 80, 80);
                $pdf->writeHTMLCell(0, 5, '', '', '<div style="text-align:center;">' . $subText . '</div>', 0, 1, 0, true, 'C', true);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont('times', 'B', 11);
            }
            $pdf->Ln(2);
        }

        if ($hasCorresponding) {
            $pdf->SetFont('times', 'I', 8);
            $pdf->writeHTMLCell(0, 5, '', '', '<hr style="border:0; border-top:1px solid #ccc;"><br/>* Autor de correspondencia / Correspondence author.', 0, 1, 0, true, 'L', true);
            $pdf->SetFont('times', 'B', 11);
        }

        $pdf->Ln(4);

        // Fechas
        $datesText = [];
        if (!empty($article['received_date']))
            $datesText[] = "Recibido / Received: " . date('d/m/Y', strtotime($article['received_date']));
        if (!empty($article['accepted_date']))
            $datesText[] = "Aceptado / Accepted: " . date('d/m/Y', strtotime($article['accepted_date']));
        if (!empty($article['published_date']))
            $datesText[] = "Publicado / Published: " . date('d/m/Y', strtotime($article['published_date']));

        if (count($datesText) > 0) {
            $pdf->SetFont('times', 'I', 9);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->MultiCell(0, 5, implode(" | ", $datesText), 0, 'C');
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Ln(6);
        }

        // Abstract ES
        if (!empty($article['abstract'])) {
            $pdf->SetFillColor(245, 245, 245);
            $pdf->SetFont('times', 'B', 12);
            $pdf->Cell(0, 8, ' Resumen', 0, 1, 'L', true);
            $pdf->SetFont('times', '', 10);
            $pdf->MultiCell(0, 5, strip_tags($article['abstract']), 0, 'J', true);
            $pdf->Ln(2);
            if (!empty($article['keywords'])) {
                $pdf->writeHTMLCell(0, 5, '', '', '<b>Palabras clave:</b> ' . htmlspecialchars($article['keywords']), 0, 1, true, true, 'J', true);
            }
            $pdf->Ln(4);
        }

        // Abstract EN
        if (!empty($article['abstract_en'])) {
            $pdf->SetFillColor(245, 245, 245);
            $pdf->SetFont('times', 'B', 12);
            $pdf->Cell(0, 8, ' Abstract', 0, 1, 'L', true);
            $pdf->SetFont('times', '', 10);
            $pdf->MultiCell(0, 5, strip_tags($article['abstract_en']), 0, 'J', true);
            $pdf->Ln(2);
            if (!empty($article['keywords_en'])) {
                $pdf->writeHTMLCell(0, 5, '', '', '<b>Keywords:</b> ' . htmlspecialchars($article['keywords_en']), 0, 1, true, true, 'J', true);
            }
            $pdf->Ln(4);
        }

        // ============================================================
        // Single-pass approach using TCPDF's native AddLink/SetLink.
        // We patched TCPDF's addHtmlLink to recognize pre-registered 
        // link IDs instead of treating #N as "go to page N".
        // ============================================================

        // Pre-scan content for all href targets
        $allContent = '';
        foreach ($sections as $section) {
            $allContent .= $section['content'];
        }
        $refTargetsInText = [];
        $fnTargetsInText = [];
        preg_match_all('/href=[\'"]#(ref-[^\'"]+)[\'"]/i', $allContent, $rm);
        if (!empty($rm[1]))
            $refTargetsInText = array_values(array_unique($rm[1]));
        preg_match_all('/href=[\'"]#(fn-[^\'"]+)[\'"]/i', $allContent, $fm);
        if (!empty($fm[1]))
            $fnTargetsInText = array_values(array_unique($fm[1]));

        $footnotes = $this->articleModel->getFootnotes($articleId);

        // Create native TCPDF links for ALL targets
        $nativeLinks = []; // targetName => TCPDF link ID
        foreach ($refTargetsInText as $t) {
            if (!isset($nativeLinks[$t]))
                $nativeLinks[$t] = $pdf->AddLink();
        }
        if (!empty($references)) {
            foreach ($references as $ref) {
                $t = 'ref-' . trim($ref['reference_order']);
                if (!isset($nativeLinks[$t]))
                    $nativeLinks[$t] = $pdf->AddLink();
            }
        }
        foreach ($fnTargetsInText as $t) {
            if (!isset($nativeLinks[$t]))
                $nativeLinks[$t] = $pdf->AddLink();
        }
        if (!empty($footnotes)) {
            foreach ($footnotes as $fn) {
                $t = 'fn-' . trim($fn['fn_id']);
                if (!isset($nativeLinks[$t]))
                    $nativeLinks[$t] = $pdf->AddLink();
            }
        }

        // ============================================================
        // Render Sections with href="#linkId" links
        // ============================================================
        foreach ($sections as $section) {
            $pdf->Bookmark($section['title'], 0, 0, '', '', array(0, 0, 0));

            $content = strip_tags($section['content'], '<b><i><u><strong><em><a><p><br><ol><ul><li><sup><sub><img>');

            // Flatten nested links (multi-pass)
            for ($pass = 0; $pass < 5; $pass++) {
                if (
                    !preg_match('/<a\s[^>]*>[^<]*<a\s/i', $content) &&
                    !preg_match('/<a\s[^>]*>\s*<sup>\s*<a\s/i', $content)
                )
                    break;
                $content = preg_replace('/<a\s+[^>]*href=[\'"]#([^\'"]+)[\'"][^>]*>\s*<sup>\s*<a\s+[^>]*href=[\'"]#([^\'"]+)[\'"][^>]*>(.*?)<\/a>\s*<\/sup>\s*<\/a>/is', '<a href="#$1"><sup>$3</sup></a>', $content);
                $content = preg_replace('/<a\s+[^>]*href=[\'"]#([^\'"]+)[\'"][^>]*>\s*<a\s+[^>]*href=[\'"]#([^\'"]+)[\'"][^>]*>(.*?)<\/a>\s*<\/a>/is', '<a href="#$1">$3</a>', $content);
            }

            // Replace href="#ref-X" / href="#fn-X" with href="#linkId" (numeric)
            $content = preg_replace_callback('/<a\s+[^>]*href=[\'"]#([^\'"]+)[\'"][^>]*>/i', function ($matches) use ($nativeLinks) {
                $target = trim($matches[1]);
                if (isset($nativeLinks[$target])) {
                    return '<a href="#' . $nativeLinks[$target] . '" style="color:#000000; text-decoration:none;">';
                }
                // Unknown target - strip link
                return '';
            }, $content);

            // Responsive tables (respecting APA or custom borders)
            $content = preg_replace('/<table([^>]*)>/i', '<table$1 width="100%" cellpadding="3" border="0">', $content);

            $sectionContentProcessed = $this->processPlaceholders($content, $tables, $figures);

            $sectionHtml = '<h2 style="font-family:times; font-weight:bold; font-size:13pt;">' . $section['title'] . '</h2>'
                . '<div style="font-family:times; font-size:11pt; text-align:justify;">' . $sectionContentProcessed . '</div><br/>';

            $pdf->writeHTML($sectionHtml, true, false, true, false, 'J');
        }

        // ============================================================
        // Render References — SetLink at current position for each
        // ============================================================
        if (!empty($references)) {
            $pdf->writeHTML('<h2 style="font-family:times; font-weight:bold; font-size:13pt;">Referencias</h2>', true, false, true, false, 'J');

            foreach ($references as $idx => $ref) {
                $seqTarget = 'ref-' . trim($ref['reference_order']);
                $citation = htmlspecialchars($ref['full_citation'] ?? $ref['citation'] ?? '');

                // Set link destination at current Y position
                if (isset($nativeLinks[$seqTarget])) {
                    $pdf->SetLink($nativeLinks[$seqTarget], -1);
                }
                // Also set for text target (e.g., ref-9 in text maps to ref-1 in DB)
                if (isset($refTargetsInText[$idx]) && isset($nativeLinks[$refTargetsInText[$idx]])) {
                    $pdf->SetLink($nativeLinks[$refTargetsInText[$idx]], -1);
                }

                $htmlBlock = '<div style="font-family:times; font-size:10pt; margin-bottom:4px; text-indent:-15px; padding-left:15px;">' . $citation . '</div>';
                $pdf->writeHTML($htmlBlock, true, false, true, false, 'J');
            }
        }

        // ============================================================
        // Render Footnotes — SetLink at current position for each
        // ============================================================
        if (!empty($footnotes)) {
            $pdf->writeHTML('<h2 style="font-family:times; font-weight:bold; font-size:13pt;">Notas al pie</h2>', true, false, true, false, 'J');

            foreach ($footnotes as $fn) {
                $mainTarget = 'fn-' . trim($fn['fn_id']);

                if (isset($nativeLinks[$mainTarget])) {
                    $pdf->SetLink($nativeLinks[$mainTarget], -1);
                }

                $htmlBlock = '<div style="font-family:times; font-size:9pt; margin-bottom:4px; text-indent:-15px; padding-left:15px;"><sup>[' . htmlspecialchars($fn['fn_id'] ?? '') . ']</sup> ' . htmlspecialchars($fn['text'] ?? '') . '</div>';
                $pdf->writeHTML($htmlBlock, true, false, true, false, 'J');
            }
        }

        // ============================================================
        // Render Tables & Figures NOT in placeholders
        // ============================================================
        $pdf->Ln(5);
        foreach ($tables as $t) {
            if (in_array(trim($t['label'] ?? ''), $this->usedTableLabels))
                continue;
            $html = '<div style="margin:10px 0; padding:10px;">';
            $html .= '<div style="font-weight:bold; font-size:11pt;">' . htmlspecialchars($t['label'] ?? 'Tabla') . '</div>';
            if (!empty($t['caption']))
                $html .= '<div style="font-style:italic; font-size:11pt; margin-bottom:5px;">' . htmlspecialchars($t['caption']) . '</div>';

            if (!empty($t['src']) && ($t['type'] ?? '') === 'image') {
                $html .= '<img src="' . $this->resolveInternalPath($t['src']) . '" width="450" />';
            } else {
                $tableHtml = $t['html'] ?? $t['html_content'] ?? $t['content'] ?? '';
                // Limpiar estilos previos
                $tableHtml = preg_replace('/border=["\']\d+["\']/i', 'border="0"', $tableHtml);
                // Inyectar estilos APA 7
                $tableHtml = preg_replace('/<table([^>]*)>/i', '<table$1 width="100%" cellpadding="5" border="0" style="font-size:10pt; border-top: 1px solid black; border-bottom: 1px solid black;">', $tableHtml);
                if (stripos($tableHtml, '<thead') !== false) {
                    $tableHtml = preg_replace('/<\/thead>/i', '<tr style="border-bottom: 1px solid black;"><td colspan="100%" style="height:0; padding:0;"></td></tr></thead>', $tableHtml);
                }
                $html .= $tableHtml;
            }
            if (!empty($t['nota']) && trim($t['nota']) !== '')
                $html .= '<div style="font-size:11pt; margin-top:5px;"><i>Nota.</i> ' . htmlspecialchars($t['nota']) . '</div>';
            if (!empty($t['footer']))
                $html .= '<div style="font-size:9pt; color:#666; margin-top:3px;">' . htmlspecialchars($t['footer']) . '</div>';
            $html .= '</div>';
            $pdf->writeHTML($html, true, false, true, false, 'J');
        }

        foreach ($figures as $f) {
            if (in_array(trim($f['label'] ?? ''), $this->usedFigureLabels))
                continue;
            $src = $this->resolveInternalPath($f['src'] ?? $f['file_path'] ?? '');

            $html = '<div style="margin:15px 0; padding:10px; text-align: left;">';
            $html .= '<div style="font-weight:bold; font-size:11pt; text-align: left;">' . htmlspecialchars($f['label'] ?? 'Figura') . '</div>';
            if (!empty($f['caption']))
                $html .= '<div style="font-style:italic; font-size:11pt; margin-top:5px; text-align: left;">' . htmlspecialchars($f['caption']) . '</div>';

            $widthVal = str_replace(['%', 'px'], '', $f['width'] ?? '100');
            $widthPx = (strpos($f['width'] ?? '', '%') !== false) ? (intval($widthVal) / 100) * 450 : min(intval($widthVal), 500);

            if ($src) {
                $html .= '<table width="100%"><tr><td align="center"><img src="' . $src . '" width="' . $widthPx . '" border="0" /></td></tr></table>';
            } else {
                $html .= '<div style="text-align: center; color: red; font-style: italic;">[Contenido de figura no disponible]</div>';
            }

            if (!empty($f['nota']) && trim($f['nota']) !== '')
                $html .= '<div style="font-size:11pt; margin-top:5px; text-align:left;"><i>Nota.</i> ' . htmlspecialchars($f['nota']) . '</div>';
            $html .= '</div>';
            $pdf->writeHTML($html, true, false, true, false, 'J');
        }

        // Output and save
        // Guardar archivo
        $config = require __DIR__ . '/../../config/config.php';
        $articleDir = $config['paths']['articles'] . $article['article_id'];

        if (!is_dir($articleDir)) {
            mkdir($articleDir, 0755, true);
        }

        $pdfPath = $articleDir . '/article.pdf';
        $pdf->Output($pdfPath, 'F');

        // Guardar en BD
        $this->articleModel->addFile([
            'article_id' => $articleId,
            'file_type' => 'pdf',
            'file_path' => $pdfPath,
            'file_size' => filesize($pdfPath),
            'mime_type' => 'application/pdf',
        ]);

        return [
            'success' => true,
            'file_path' => $pdfPath,
            'download_url' => 'articles/' . $article['article_id'] . '/article.pdf'
        ];
    }

    private function processPlaceholders($content, $tables, $figures)
    {
        $pattern = '/(<span[^>]*contenteditable=["\']false["\'][^>]*>)?\s*(\[(?:Tabla|Figura|Table|Figure)\s*[^\]]+\])\s*(<\/span>)?/i';

        $content = preg_replace_callback($pattern, function ($matches) use ($tables, $figures) {
            $label = strip_tags($matches[2]);

            // Buscar en tablas con coincidencia flexible
            $foundTable = $this->findTableByLabel($label, $tables);
            if ($foundTable) {
                $this->usedTableLabels[] = trim($foundTable['label'] ?? '');
                $t = $foundTable;
                $html = '<div style="margin:10px 0; padding:10px;">';
                $html .= '<div style="font-weight:bold; font-size:11pt; text-align: left;">' . htmlspecialchars($t['label'] ?? 'Tabla') . '</div>';
                $tCaption = !empty($t['caption']) ? $t['caption'] : (!empty($t['title']) ? $t['title'] : '');
                if (!empty($tCaption))
                    $html .= '<div style="font-style:italic; font-size:11pt; margin-bottom:5px; text-align: left;">' . htmlspecialchars($tCaption) . '</div>';

                if (!empty($t['src']) && ($t['type'] ?? '') === 'image') {
                    $html .= '<img src="' . $this->resolveInternalPath($t['src']) . '" width="450" />';
                } else {
                    $tableHtml = $t['html'] ?? $t['html_content'] ?? $t['content'] ?? '';

                    // Limpiar estilos previos (importantísimo para no romper EPA y legibilidad)
                    $tableHtml = preg_replace('/border=["\']\d+["\']/i', 'border="0"', $tableHtml);
                    $tableHtml = preg_replace('/style=["\'][^"\']*border[^"\']*["\']/i', '', $tableHtml);

                    // Inyectar estilos APA 7 para PDF/TCPDF
                    $tableHtml = preg_replace('/<table([^>]*)>/i', '<table$1 width="100%" cellpadding="5" border="0" style="font-size:10pt; border-top: 1px solid black; border-bottom: 1px solid black; border-collapse:collapse;">', $tableHtml);

                    if (stripos($tableHtml, '<thead') !== false) {
                        $tableHtml = preg_replace('/<\/thead>/i', '<tr style="line-height:1px;"><td colspan="100%" style="border-bottom: 1px solid black; height:1px; padding:0;"></td></tr></thead>', $tableHtml);
                    } else {
                        $tableHtml = preg_replace('/<\/tr>/i', '</tr><tr style="line-height:1px;"><td colspan="100%" style="border-bottom: 1px solid black; height:1px; padding:0;"></td></tr>', $tableHtml, 1);
                    }

                    $html .= $tableHtml;
                }

                if (!empty($t['nota']) && trim($t['nota']) !== '')
                    $html .= '<div style="font-size:11pt; margin-top:5px; text-align: left;"><i>Nota.</i> ' . htmlspecialchars($t['nota']) . '</div>';
                if (!empty($t['footer']))
                    $html .= '<div style="font-size:9pt; color:#666; margin-top:3px; text-align: left;">' . htmlspecialchars($t['footer']) . '</div>';
                $html .= '</div>';
                return $html;
            }

            // Buscar en figuras con coincidencia flexible
            $foundFigure = $this->findFigureByLabel($label, $figures);
            if ($foundFigure) {
                $this->usedFigureLabels[] = trim($foundFigure['label'] ?? '');
                $f = $foundFigure;
                $src = $this->resolveInternalPath($f['src'] ?? $f['file_path'] ?? '');

                $html = '<div style="margin:15px 0; padding:10px; text-align: left;">';
                $html .= '<div style="font-weight:bold; font-size:11pt; text-align: left;">' . htmlspecialchars($f['label'] ?? 'Figura') . '</div>';
                if (!empty($f['caption']))
                    $html .= '<div style="font-style:italic; font-size:11pt; margin-top:5px; text-align: left;">' . htmlspecialchars($f['caption']) . '</div>';

                $widthVal = str_replace(['%', 'px'], '', $f['width'] ?? '100');
                if (strpos($f['width'] ?? '', '%') !== false) {
                    $widthPx = (intval($widthVal) / 100) * 450;
                } else {
                    $widthPx = min(intval($widthVal), 500);
                }

                if ($src) {
                    $html .= '<table width="100%"><tr><td align="center"><img src="' . $src . '" width="' . $widthPx . '" border="0" /></td></tr></table>';
                } else {
                    $html .= '<div style="text-align: center; color: red; font-style: italic;">[Figura ' . htmlspecialchars($label) . ' no disponible]</div>';
                }

                if (!empty($f['nota']) && trim($f['nota']) !== '')
                    $html .= '<div style="font-size:11pt; margin-top:5px; text-align:left;"><i>Nota.</i> ' . htmlspecialchars($f['nota']) . '</div>';
                $html .= '</div>';
                return $html;
            }

            return $matches[2];
        }, $content);

        // Limpieza robusta de cuadrados residuales (marcas de posición que ya no existen)
        // Buscamos específicamente los colores de fondo del editor (azul/verde) y verificamos que no contengan texto
        $content = preg_replace_callback('/<span[^>]*style=["\'][^"\']*(?:rgb\(239,\s*246,\s*255\)|rgb\(240,\s*253,\s*244\)|eff6ff|f0fdf4)[^"\']*["\'][^>]*>(.*?)<\/span>/is', function ($m) {
            $innerClean = trim(strip_tags(str_ireplace(['&nbsp;', '&#160;', '&#xa0;', '&amp;nbsp;'], ' ', $m[1])));
            if ($innerClean === '' || preg_match('/^\[\s*\]$/', $innerClean)) {
                return '';
            }
            return $m[0];
        }, $content);

        return $content;
    }
    private function findTableByLabel($label, $tables)
    {
        $cleanSearch = strtolower(preg_replace('/\s+/', '', trim($label, '[]')));
        foreach ($tables as $t) {
            $tLabel = strtolower(preg_replace('/\s+/', '', trim($t['label'] ?? '')));
            if ($tLabel === $cleanSearch)
                return $t;
        }
        return null;
    }

    private function findFigureByLabel($label, $figures)
    {
        $cleanSearch = strtolower(preg_replace('/\s+/', '', trim($label, '[]')));
        foreach ($figures as $f) {
            $fLabel = strtolower(preg_replace('/\s+/', '', trim($f['label'] ?? '')));
            if ($fLabel === $cleanSearch)
                return $f;
        }
        return null;
    }

    private function resolveInternalPath($src)
    {
        if (empty($src))
            return '';
        if (strpos($src, 'data:image') === 0)
            return $src;

        $config = require __DIR__ . '/../../config/config.php';
        $baseUrl = rtrim($config['app']['url'], '/');
        $publicDir = realpath(__DIR__ . '/../../public');

        $cleanSrc = $src;
        if (strpos($src, $baseUrl) === 0) {
            $cleanSrc = substr($src, strlen($baseUrl));
        }
        $cleanSrc = ltrim($cleanSrc, '/');
        $cleanSrc = ltrim($cleanSrc, '.');
        $cleanSrc = ltrim($cleanSrc, '/');
        $filename = basename($cleanSrc);

        $attempts = [
            $publicDir . '/' . $cleanSrc,
            $publicDir . '/articles/9/' . $filename,
            $publicDir . '/articles/20260323_613683/articles/9/' . $filename,
            $publicDir . '/uploads/articles/9/' . $filename
        ];

        foreach ($attempts as $p) {
            if ($p && file_exists($p) && !is_dir($p)) {
                try {
                    $info = @getimagesize($p);
                    if ($info) {
                        $mime = $info['mime'];

                        // SI ES PNG, INTENTAR CONVERTIR A JPG PARA SOLUCIONAR PROBLEMAS DE ENTRELAZADO (INTERLACING) EN TCPDF
                        if ($mime === 'image/png' && function_exists('imagecreatefrompng')) {
                            $img = @imagecreatefrompng($p);
                            if ($img) {
                                // Rellenar fondo transparente con blanco para el JPG
                                $bg = imagecreatetruecolor(imagesx($img), imagesy($img));
                                $white = imagecolorallocate($bg, 255, 255, 255);
                                imagefill($bg, 0, 0, $white);
                                imagecopy($bg, $img, 0, 0, 0, 0, imagesx($img), imagesy($img));

                                ob_start();
                                imagejpeg($bg, null, 90);
                                $data = ob_get_clean();
                                imagedestroy($img);
                                imagedestroy($bg);
                                return 'data:image/jpeg;base64,' . base64_encode($data);
                            }
                        }

                        $data = @file_get_contents($p);
                        if ($data) {
                            return 'data:' . $mime . ';base64,' . base64_encode($data);
                        }
                    }
                } catch (Exception $e) {
                }
            }
        }

        if (strpos($src, 'http') === 0)
            return $src;
        return $baseUrl . '/' . $cleanSrc;
    }
}
