<?php
/**
 * Clase JATSGenerator - Generación de XML-JATS desde datos marcados
 */

require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../models/Database.php';

class JATSGenerator {
    private $articleModel;
    private $db;
    
    public function __construct() {
        $this->articleModel = new Article();
        $this->db = Database::getInstance();
    }
    
    /**
     * Generar XML-JATS completo para un artículo
     */
    public function generateXML($articleId) {
        $article = $this->articleModel->getById($articleId);
        
        if (!$article) {
            throw new Exception("Artículo no encontrado");
        }
        
        // Obtener todos los datos del artículo
        $authors = $this->articleModel->getAuthors($articleId);
        $affiliations = $this->articleModel->getAffiliations($articleId);
        $sections = $this->articleModel->getSections($articleId);
        $tables = $this->articleModel->getTables($articleId);
        $figures = $this->articleModel->getFigures($articleId);
        $references = $this->articleModel->getReferences($articleId);
        
        // Obtener información de la revista
        $journal = $this->getJournalInfo($article);
        
        // Obtener markup data (si existe) para tablas/figuras/secciones
        $markup = $this->articleModel->getMarkup($articleId);
        $markupTables  = [];
        $markupFigures = [];
        $markupSections = [];
        if ($markup && isset($markup['markup_data'])) {
            $md = $markup['markup_data'];
            $markupTables   = $md['tables']    ?? [];
            $markupFigures  = $md['images']    ?? [];
            $markupSections = $md['sections']  ?? [];
        }

        // Fallback: si article_sections está vacía usa las del markup_data
        if (empty($sections) && !empty($markupSections)) {
            $sections = array_map(function($s, $i) {
                return [
                    'section_id'   => 'sec-' . ($i + 1),
                    'section_type' => $s['type']      ?? 'other',
                    'title'        => $s['type_name'] ?? ($s['title'] ?? ''),
                    'content'      => $s['content']   ?? '',
                    'level'        => $s['level']      ?? 1,
                    'section_order'=> $i + 1,
                ];
            }, $markupSections, array_keys($markupSections));
        }

        // Fallback: si las tablas de BD están vacías usa las del markup_data
        if (empty($tables) && !empty($markupTables)) {
            $tables = $markupTables;
        }
        // Usar siempre las tablas del markup (tienen el HTML actualizado)
        if (!empty($markupTables)) {
            $markupTablesIndexed = [];
            foreach ($markupTables as $mt) {
                $markupTablesIndexed[strtolower(trim($mt['label'] ?? ''))] = $mt;
            }
            // Combinar: markup_data tiene prioridad sobre article_tables
            $mergedTables = [];
            foreach ($tables as $t) {
                $key = strtolower(trim($t['label'] ?? ''));
                $mergedTables[] = $markupTablesIndexed[$key] ?? $t;
            }
            // Añadir tablas del markup que no estén en article_tables
            foreach ($markupTables as $mt) {
                $key = strtolower(trim($mt['label'] ?? ''));
                $found = false;
                foreach ($mergedTables as $m) {
                    if (strtolower(trim($m['label'] ?? '')) === $key) { $found = true; break; }
                }
                if (!$found) $mergedTables[] = $mt;
            }
            $tables = $mergedTables;
        }
        
        // Crear documento XML
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        
        // DOCTYPE
        $implementation = new DOMImplementation();
        $dtd = $implementation->createDocumentType(
            'article',
            '-//NLM//DTD JATS (Z39.96) Journal Archiving and Interchange DTD v1.2 20190208//EN',
            'JATS-archivearticle1.dtd'
        );
        $dom = $implementation->createDocument('', '', $dtd);
        $dom->encoding = 'UTF-8';
        $dom->formatOutput = true;
        
        // Elemento raíz <article>
        $articleElement = $dom->createElement('article');
        $articleElement->setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
        $articleElement->setAttribute('xmlns:mml', 'http://www.w3.org/1998/Math/MathML');
        $articleElement->setAttribute('article-type', $article['article_type'] ?? 'research-article');
        $articleElement->setAttribute('xml:lang', $article['language'] ?? 'es');
        $dom->appendChild($articleElement);
        
        // Front matter
        $front = $dom->createElement('front');
        $articleElement->appendChild($front);
        
        // Journal metadata
        $journalMeta = $this->createJournalMeta($dom, $journal);
        $front->appendChild($journalMeta);
        
        // Article metadata
        $articleMeta = $this->createArticleMeta($dom, $article, $authors, $affiliations);
        $front->appendChild($articleMeta);
        
        // Figuras: usar markup_data si article_figures está vacía
        if (empty($figures) && !empty($markupFigures)) {
            $figures = $markupFigures;
        }

        // Body - pasar $tables (ya merged con markup) y $figures
        $body = $this->createBody($dom, $sections, $tables, $figures);
        $articleElement->appendChild($body);
        
        // Back matter (referencias)
        $back = $this->createBack($dom, $references);
        $articleElement->appendChild($back);
        
        $xmlContent = $dom->saveXML();
        
        // Guardar archivo
        $config = require __DIR__ . '/../../config/config.php';
        $articleDir = $config['paths']['articles'] . $article['article_id'];
        
        if (!is_dir($articleDir)) {
            mkdir($articleDir, 0755, true);
        }
        
        $xmlPath = $articleDir . '/article.xml';
        file_put_contents($xmlPath, $xmlContent);
        
        // Guardar en BD
        $this->articleModel->addFile([
            'article_id' => $articleId,
            'file_type' => 'xml_jats',
            'file_path' => $xmlPath,
            'file_size' => strlen($xmlContent),
            'mime_type' => 'application/xml',
        ]);
        
        return [
            'success' => true,
            'file_path' => $xmlPath,
            'download_url' => 'articles/' . $article['article_id'] . '/article.xml'
        ];
    }
    
    /**
     * Crear metadata de revista
     */
    private function createJournalMeta($dom, $journal) {
        $journalMeta = $dom->createElement('journal-meta');
        
        // Journal ID
        $journalId = $dom->createElement('journal-id', htmlspecialchars($journal['id'] ?? 'JOURNAL'));
        $journalId->setAttribute('journal-id-type', 'publisher-id');
        $journalMeta->appendChild($journalId);
        
        // Journal title
        $journalTitleGroup = $dom->createElement('journal-title-group');
        $journalTitle = $dom->createElement('journal-title', htmlspecialchars($journal['title'] ?? ''));
        $journalTitleGroup->appendChild($journalTitle);
        $journalMeta->appendChild($journalTitleGroup);
        
        // ISSN Print
        if (!empty($journal['issn_print'])) {
            $issnPrint = $dom->createElement('issn', $journal['issn_print']);
            $issnPrint->setAttribute('pub-type', 'ppub');
            $journalMeta->appendChild($issnPrint);
        }
        
        // ISSN Electronic
        if (!empty($journal['issn_electronic'])) {
            $issnElec = $dom->createElement('issn', $journal['issn_electronic']);
            $issnElec->setAttribute('pub-type', 'epub');
            $journalMeta->appendChild($issnElec);
        }
        
        // Publisher
        $publisher = $dom->createElement('publisher');
        $publisherName = $dom->createElement('publisher-name', htmlspecialchars($journal['publisher'] ?? ''));
        $publisher->appendChild($publisherName);
        $journalMeta->appendChild($publisher);
        
        return $journalMeta;
    }
    
    /**
     * Crear metadata del artículo
     */
    private function createArticleMeta($dom, $article, $authors, $affiliations) {
        $articleMeta = $dom->createElement('article-meta');
        
        // Article ID
        $articleId = $dom->createElement('article-id', htmlspecialchars($article['article_id']));
        $articleId->setAttribute('pub-id-type', 'publisher-id');
        $articleMeta->appendChild($articleId);
        
        // DOI
        if (!empty($article['doi'])) {
            $doiElement = $dom->createElement('article-id', htmlspecialchars($article['doi']));
            $doiElement->setAttribute('pub-id-type', 'doi');
            $articleMeta->appendChild($doiElement);
        }
        
        // Article Categories / Section
        if (!empty($article['article_type'])) {
            $articleCategories = $dom->createElement('article-categories');
            $subjGroup = $dom->createElement('subj-group');
            $subjGroup->setAttribute('subj-group-type', 'heading');
            $subject = $dom->createElement('subject', htmlspecialchars($article['article_type']));
            $subjGroup->appendChild($subject);
            $articleCategories->appendChild($subjGroup);
            $articleMeta->appendChild($articleCategories);
        }
        
        // Title group
        $titleGroup = $dom->createElement('title-group');
        $title = $dom->createElement('article-title');
        $title->setAttribute('xml:lang', 'es');
        $title->appendChild($dom->createTextNode($article['title']));
        $titleGroup->appendChild($title);
        
        if (!empty($article['title_en'])) {
            $transTitleGroup = $dom->createElement('trans-title-group');
            $transTitleGroup->setAttribute('xml:lang', 'en');
            $transTitle = $dom->createElement('trans-title', htmlspecialchars($article['title_en']));
            $transTitleGroup->appendChild($transTitle);
            $titleGroup->appendChild($transTitleGroup);
        }
        
        $articleMeta->appendChild($titleGroup);
        
        // Contributors (authors)
        $contribGroup = $this->createContribGroup($dom, $authors, $affiliations);
        $articleMeta->appendChild($contribGroup);
        
        // Affiliations
        foreach ($affiliations as $aff) {
            $affElement = $this->createAffiliation($dom, $aff);
            $articleMeta->appendChild($affElement);
        }
        
        // Publication dates
        if (!empty($article['published_date'])) {
            $pubDate = $dom->createElement('pub-date');
            $pubDate->setAttribute('pub-type', 'epub');
            $this->addDateElements($dom, $pubDate, $article['published_date']);
            $articleMeta->appendChild($pubDate);
        }
        
        // History
        if (!empty($article['received_date']) || !empty($article['accepted_date'])) {
            $history = $dom->createElement('history');
            
            if (!empty($article['received_date'])) {
                $received = $dom->createElement('date');
                $received->setAttribute('date-type', 'received');
                $this->addDateElements($dom, $received, $article['received_date']);
                $history->appendChild($received);
            }
            
            if (!empty($article['accepted_date'])) {
                $accepted = $dom->createElement('date');
                $accepted->setAttribute('date-type', 'accepted');
                $this->addDateElements($dom, $accepted, $article['accepted_date']);
                $history->appendChild($accepted);
            }
            
            $articleMeta->appendChild($history);
        }
        
        // Permissions (license)
        $permissions = $dom->createElement('permissions');
        $copyrightStatement = $dom->createElement('copyright-statement', '© ' . date('Y') . ' Autores (Authors)');
        $permissions->appendChild($copyrightStatement);
        $copyrightYear = $dom->createElement('copyright-year', date('Y'));
        $permissions->appendChild($copyrightYear);
        
        // Creative Commons License CC BY 4.0 (Scopus requirement)
        $license = $dom->createElement('license');
        $license->setAttribute('license-type', 'open-access');
        $license->setAttribute('xlink:href', 'http://creativecommons.org/licenses/by/4.0/');
        $licenseP = $dom->createElement('license-p', 'Este es un artículo de acceso abierto bajo la licencia CC BY 4.0');
        $license->appendChild($licenseP);
        $permissions->appendChild($license);
        
        $articleMeta->appendChild($permissions);
        
        // Abstract
        if (!empty($article['abstract'])) {
            $abstract = $dom->createElement('abstract');
            $abstract->setAttribute('xml:lang', 'es');
            $abstractP = $dom->createElement('p', htmlspecialchars($article['abstract']));
            $abstract->appendChild($abstractP);
            $articleMeta->appendChild($abstract);
        }
        
        if (!empty($article['abstract_en'])) {
            $abstractEn = $dom->createElement('abstract');
            $abstractEn->setAttribute('xml:lang', 'en');
            $abstractPEn = $dom->createElement('p', htmlspecialchars($article['abstract_en']));
            $abstractEn->appendChild($abstractPEn);
            $articleMeta->appendChild($abstractEn);
        }
        
        // Keywords
        if (!empty($article['keywords'])) {
            $kwdGroup = $dom->createElement('kwd-group');
            $kwdGroup->setAttribute('xml:lang', 'es');
            foreach (explode(',', $article['keywords']) as $keyword) {
                $kwd = $dom->createElement('kwd', htmlspecialchars(trim($keyword)));
                $kwdGroup->appendChild($kwd);
            }
            $articleMeta->appendChild($kwdGroup);
        }
        
        if (!empty($article['keywords_en'])) {
            $kwdGroupEn = $dom->createElement('kwd-group');
            $kwdGroupEn->setAttribute('xml:lang', 'en');
            foreach (explode(',', $article['keywords_en']) as $keyword) {
                $kwd = $dom->createElement('kwd', htmlspecialchars(trim($keyword)));
                $kwdGroupEn->appendChild($kwd);
            }
            $articleMeta->appendChild($kwdGroupEn);
        }
        
        return $articleMeta;
    }
    
    /**
     * Crear grupo de contribuyentes (autores)
     */
    private function createContribGroup($dom, $authors, $affiliations) {
        $contribGroup = $dom->createElement('contrib-group');
        
        foreach ($authors as $author) {
            $contrib = $dom->createElement('contrib');
            $contrib->setAttribute('contrib-type', 'author');
            
            // Name
            $name = $dom->createElement('n');
            $surname = $dom->createElement('surname', htmlspecialchars($author['surname']));
            $givenNames = $dom->createElement('given-names', htmlspecialchars($author['given_names']));
            $name->appendChild($surname);
            $name->appendChild($givenNames);
            $contrib->appendChild($name);
            
            // Affiliation reference
            if (!empty($author['affiliation_id'])) {
                $xref = $dom->createElement('xref');
                $xref->setAttribute('ref-type', 'aff');
                $xref->setAttribute('rid', $author['affiliation_id']);
                $contrib->appendChild($xref);
            }
            
            // ORCID
            if (!empty($author['orcid'])) {
                $contribId = $dom->createElement('contrib-id', 'https://orcid.org/' . $author['orcid']);
                $contribId->setAttribute('contrib-id-type', 'orcid');
                $contrib->appendChild($contribId);
            }
            
            // Email
            if (!empty($author['email'])) {
                $email = $dom->createElement('email', $author['email']);
                $contrib->appendChild($email);
            }
            
            $contribGroup->appendChild($contrib);
        }
        
        return $contribGroup;
    }
    
    /**
     * Crear elemento de afiliación
     */
    private function createAffiliation($dom, $aff) {
        $affElement = $dom->createElement('aff');
        $affElement->setAttribute('id', $aff['affiliation_id']);
        
        if (!empty($aff['institution'])) {
            $institution = $dom->createElement('institution', htmlspecialchars($aff['institution']));
            $affElement->appendChild($institution);
        }
        
        if (!empty($aff['country'])) {
            $country = $dom->createElement('country', htmlspecialchars($aff['country']));
            $affElement->appendChild($country);
        }
        
        return $affElement;
    }
    
    /**
     * Crear body del artículo
     */
    private function createBody($dom, $sections, $tables, $figures) {
        $body = $dom->createElement('body');
        
        foreach ($sections as $section) {
            $sec = $this->createSection($dom, $section, $tables, $figures);
            $body->appendChild($sec);
        }
        
        return $body;
    }

    /**
     * Crear sección con soporte de múltiples párrafos HTML del editor
     */
    private function createSection($dom, $section, $tables, $figures) {
        $sec = $dom->createElement('sec');
        $sec->setAttribute('id', $section['section_id']);

        if (!empty($section['title'])) {
            $title = $dom->createElement('title', htmlspecialchars($section['title']));
            $sec->appendChild($title);
        }

        if (!empty($section['content'])) {
            // Dividir el contenido HTML del editor en bloques de párrafo
            $blocks = $this->extractParagraphsFromHtml($section['content']);

            foreach ($blocks as $block) {
                if (empty(trim($block))) continue;

                $cleanBlock = trim(strip_tags($block));

                // ¿Es solo un placeholder [Tabla X] o [Figura X]?
                if (preg_match('/^\[(Tabla|Table)\s*([^\]]+)\]$/i', $cleanBlock, $matches)) {
                    $this->appendTableByLabel($dom, $sec, trim($matches[1] . ' ' . $matches[2]), $tables);
                    continue;
                }
                if (preg_match('/^\[(Figura|Figure)\s*([^\]]+)\]$/i', $cleanBlock, $matches)) {
                    $this->appendFigureByLabel($dom, $sec, trim($matches[1] . ' ' . $matches[2]), $figures);
                    continue;
                }

                // El bloque puede contener texto Y placeholders intercalados
                $pattern = '/(\[(?:Tabla|Figura|Table|Figure)\s*[^\]]+\])/i';
                $parts = preg_split($pattern, $block, -1, PREG_SPLIT_DELIM_CAPTURE);

                $currentP = null;

                foreach ($parts as $part) {
                    if ($part === '' || $part === null) continue;
                    $cleanPart = trim(strip_tags($part));

                    if (preg_match('/^\[(Tabla|Table)\s*([^\]]+)\]$/i', $cleanPart, $m)) {
                        if ($currentP) { $sec->appendChild($currentP); $currentP = null; }
                        $this->appendTableByLabel($dom, $sec, trim($m[1] . ' ' . $m[2]), $tables);
                    } elseif (preg_match('/^\[(Figura|Figure)\s*([^\]]+)\]$/i', $cleanPart, $m)) {
                        if ($currentP) { $sec->appendChild($currentP); $currentP = null; }
                        $this->appendFigureByLabel($dom, $sec, trim($m[1] . ' ' . $m[2]), $figures);
                    } else {
                        if (!$currentP) {
                            $currentP = $dom->createElement('p');
                        }
                        $text = $part;
                        // Convertir <a data-fnid> a <xref ref-type="fn">
                        $text = preg_replace('/<a\s[^>]*data-fnid=[\'"]([^\'"]+)[\'"][^>]*>(.*?)<\/a>/is',
                            '<xref ref-type="fn" rid="$1">$2</xref>', $text);
                        // Convertir <a data-refid> / <a data-rid> a <xref ref-type="bibr">
                        $text = preg_replace('/<a\s[^>]*data-(?:refid|rid)=[\'"]([^\'"]+)[\'"][^>]*>(.*?)<\/a>/is',
                            '<xref ref-type="bibr" rid="$1">$2</xref>', $text);
                        $text = strip_tags($text, '<b><i><u><sub><sup><xref>');
                        $text = $this->htmlToXmlFragment($text);

                        if (trim($text) !== '') {
                            $frag = $dom->createDocumentFragment();
                            libxml_use_internal_errors(true);
                            $ok = @$frag->appendXML($text);
                            libxml_clear_errors();
                            if ($ok !== false) {
                                $currentP->appendChild($frag);
                            } else {
                                $currentP->appendChild($dom->createTextNode($cleanPart));
                            }
                        }
                    }
                }

                if ($currentP && $currentP->hasChildNodes()) {
                    $sec->appendChild($currentP);
                }
            }
        }

        return $sec;
    }

    /**
     * Divide el HTML del editor en bloques de párrafo individuales
     */
    private function extractParagraphsFromHtml(string $html): array {
        // Convertir <br> a salto de línea
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
        // Convertir cierre de bloque a salto de línea
        $html = preg_replace('/<\/(p|div|li|h[1-6])>/i', "\n", $html);
        // Eliminar apertura de etiquetas de bloque
        $html = preg_replace('/<(p|div|ul|ol|li|h[1-6])[^>]*>/i', '', $html);
        // Dividir por líneas
        $lines = preg_split('/\n+/', $html);
        $result = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $result[] = $line;
            }
        }
        return $result;
    }

    /**
     * Normaliza un fragmento HTML inline para que sea XML válido.
     * Convierte entidades HTML (&nbsp; etc.) a sus equivalentes Unicode.
     */
    private function htmlToXmlFragment(string $html): string {
        // Reemplazar entidades HTML frecuentes antes de decodificar
        $map = [
            '&nbsp;'   => '&#160;',
            '&ndash;'  => '–',
            '&mdash;'  => '—',
            '&ldquo;'  => '"',
            '&rdquo;'  => '"',
            '&lsquo;'  => "\u{2018}",
            '&rsquo;'  => "\u{2019}",
            '&hellip;' => '…',
            '&bull;'   => '•',
            '&copy;'   => '©',
            '&reg;'    => '®',
            '&trade;'  => '™',
            '&deg;'    => '°',
        ];
        foreach ($map as $entity => $char) {
            $html = str_replace($entity, $char, $html);
        }
        // Decodificar cualquier entidad named restante a UTF-8
        $html = html_entity_decode($html, ENT_HTML5 | ENT_QUOTES, 'UTF-8');
        // Re-escapar & sueltos que no sean entidades XML
        $html = preg_replace('/&(?!(?:[a-zA-Z]+|#\d+|#x[0-9a-fA-F]+);)/', '&amp;', $html);
        return $html;
    }

    /**
     * Convierte HTML de tabla a XML válido usando DOMDocument como parser intermedio
     */
    private function tableHtmlToXml(string $html): string {
        $html = preg_replace('/\s+style="[^"]*"/i', '', $html);
        $html = preg_replace('/\s+class="[^"]*"/i', '', $html);
        $html = preg_replace('/\s+id="[^"]*"/i', '', $html);

        libxml_use_internal_errors(true);
        $tmpDom = new DOMDocument('1.0', 'UTF-8');
        $tmpDom->loadHTML(
            '<?xml encoding="UTF-8">' . $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR
        );
        libxml_clear_errors();

        $tableTags = $tmpDom->getElementsByTagName('table');
        if ($tableTags->length === 0) return '';
        return $tmpDom->saveXML($tableTags->item(0));
    }

    /**
     * Insertar tabla por etiqueta (Ej: Tabla 1) con conversión HTML→XML robusta
     */
    private function appendTableByLabel($dom, $parent, $label, $tables) {
        $tableData = null;
        foreach ($tables as $t) {
            if (strcasecmp(trim($t['label'] ?? ''), $label) === 0) {
                $tableData = $t;
                break;
            }
        }

        if (!$tableData) return;

        $tableWrap = $dom->createElement('table-wrap');
        $tableWrap->setAttribute('id', 't-' . uniqid());
        $parent->appendChild($tableWrap);

        $labelEl = $dom->createElement('label', htmlspecialchars($tableData['label'] ?? 'Tabla'));
        $tableWrap->appendChild($labelEl);

        $caption = $dom->createElement('caption');
        $capText = $tableData['caption'] ?? ($tableData['title'] ?? '');
        $caption->appendChild($dom->createElement('p', htmlspecialchars($capText)));
        $tableWrap->appendChild($caption);

        if (!empty($tableData['src']) && ($tableData['type'] ?? '') === 'image') {
            $graphic = $dom->createElement('graphic');
            $graphic->setAttribute('xlink:href', htmlspecialchars($tableData['src']));
            $tableWrap->appendChild($graphic);
        } elseif (!empty($tableData['html'])) {
            // 1er intento: conversión via DOMDocument (más robusta)
            $tableXml = $this->tableHtmlToXml($tableData['html']);
            $appended  = false;
            if (!empty($tableXml)) {
                $frag = $dom->createDocumentFragment();
                libxml_use_internal_errors(true);
                $ok = @$frag->appendXML($tableXml);
                libxml_clear_errors();
                if ($ok !== false) {
                    $tableWrap->appendChild($frag);
                    $appended = true;
                }
            }
            // 2do intento: limpiar atributos y convertir entidades, luego appendXML
            if (!$appended) {
                $clean = preg_replace('/style="[^"]*"|class="[^"]*"|id="[^"]*"/i', '', $tableData['html']);
                $clean = $this->htmlToXmlFragment($clean);
                $frag2 = $dom->createDocumentFragment();
                libxml_use_internal_errors(true);
                $ok2 = @$frag2->appendXML($clean);
                libxml_clear_errors();
                if ($ok2 !== false) {
                    $tableWrap->appendChild($frag2);
                } else {
                    // Fallback final: tabla vacía
                    $tableWrap->appendChild($dom->createElement('table'));
                }
            }
        }

        if (!empty($tableData['nota']) && trim($tableData['nota']) !== '') {
            $footer = $dom->createElement('table-wrap-foot');
            $fn     = $dom->createElement('fn');
            $fn->appendChild($dom->createElement('p', 'Nota. ' . htmlspecialchars($tableData['nota'])));
            $footer->appendChild($fn);
            $tableWrap->appendChild($footer);
        }
    }

    /**
     * Insertar figura por etiqueta (Ej: Figura 1)
     */
    private function appendFigureByLabel($dom, $parent, $label, $figures) {
        $figData = null;
        foreach ($figures as $f) {
            if (strcasecmp(trim($f['label'] ?? ''), $label) === 0) {
                $figData = $f;
                break;
            }
        }
        
        if (!$figData) return;

        $fig = $dom->createElement('fig');
        $fig->setAttribute('id', 'f-' . uniqid());
        $parent->appendChild($fig);

        $labelEl = $dom->createElement('label', htmlspecialchars($figData['label'] ?? 'Figura'));
        $fig->appendChild($labelEl);

        $caption = $dom->createElement('caption');
        $p = $dom->createElement('p', htmlspecialchars($figData['alt'] ?? ($figData['caption'] ?? '')));
        $caption->appendChild($p);
        $fig->appendChild($caption);

        $graphic = $dom->createElement('graphic');
        $graphic->setAttribute('xlink:href', htmlspecialchars($figData['src'] ?? ''));
        $fig->appendChild($graphic);
        
        if (!empty($figData['nota']) && trim($figData['nota']) !== '') {
            $p = $dom->createElement('p', 'Nota. ' . htmlspecialchars($figData['nota']));
            $fig->appendChild($p);
        }
    }
    
    /**
     * Crear back matter (referencias)
     */
    private function createBack($dom, $references) {
        $back = $dom->createElement('back');
        
        if (!empty($references)) {
            $refList = $dom->createElement('ref-list');
            $refTitle = $dom->createElement('title', 'Referencias');
            $refList->appendChild($refTitle);
            
            foreach ($references as $ref) {
                $refElement = $this->createReference($dom, $ref);
                $refList->appendChild($refElement);
            }
            
            $back->appendChild($refList);
        }
        
        return $back;
    }
    
    /**
     * Crear elemento de referencia
     */
    private function createReference($dom, $ref) {
        $refElement = $dom->createElement('ref');
        $refElement->setAttribute('id', $ref['ref_id']);
        
        $elementCitation = $dom->createElement('element-citation');
        $elementCitation->setAttribute('publication-type', $ref['reference_type'] ?? 'journal');
        
        if (!empty($ref['authors'])) {
            $personGroup = $dom->createElement('person-group');
            $personGroup->setAttribute('person-group-type', 'author');
            // Aquí se podría parsear los autores individualmente
            $elementCitation->appendChild($personGroup);
        }
        
        if (!empty($ref['year'])) {
            $year = $dom->createElement('year', $ref['year']);
            $elementCitation->appendChild($year);
        }
        
        if (!empty($ref['title'])) {
            $articleTitle = $dom->createElement('article-title', htmlspecialchars($ref['title']));
            $elementCitation->appendChild($articleTitle);
        }
        
        if (!empty($ref['source'])) {
            $source = $dom->createElement('source', htmlspecialchars($ref['source']));
            $elementCitation->appendChild($source);
        }
        
        if (!empty($ref['volume'])) {
            $volume = $dom->createElement('volume', $ref['volume']);
            $elementCitation->appendChild($volume);
        }
        
        if (!empty($ref['doi'])) {
            $doi = $dom->createElement('pub-id', $ref['doi']);
            $doi->setAttribute('pub-id-type', 'doi');
            $elementCitation->appendChild($doi);
        }
        
        if (!empty($ref['url'])) {
            $extLink = $dom->createElement('ext-link', htmlspecialchars($ref['url']));
            $extLink->setAttribute('ext-link-type', 'uri');
            $extLink->setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href', $ref['url']);
            $elementCitation->appendChild($extLink);
        }
        
        $refElement->appendChild($elementCitation);
        return $refElement;
    }
    
    /**
     * Agregar elementos de fecha
     */
    private function addDateElements($dom, $parentElement, $dateString) {
        $date = new DateTime($dateString);
        
        $day = $dom->createElement('day', $date->format('d'));
        $month = $dom->createElement('month', $date->format('m'));
        $year = $dom->createElement('year', $date->format('Y'));
        
        $parentElement->appendChild($day);
        $parentElement->appendChild($month);
        $parentElement->appendChild($year);
    }
    
    /**
     * Obtener información de la revista
     */
    private function getJournalInfo($article) {
        $sql = "SELECT j.* FROM journals j
                LEFT JOIN volumes v ON j.id = v.journal_id
                LEFT JOIN issues i ON v.id = i.volume_id
                WHERE i.id = :issue_id";
        
        $journal = $this->db->fetchOne($sql, ['issue_id' => $article['issue_id']]);
        
        if (!$journal) {
            // Obtener primera revista disponible
            $journal = $this->db->fetchOne("SELECT * FROM journals WHERE active = TRUE LIMIT 1");
        }
        
        return $journal ?: [];
    }
}
