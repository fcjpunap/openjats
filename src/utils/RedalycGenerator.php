<?php
/**
 * Clase JATSGenerator - Generación de XML-JATS desde datos marcados
 */

require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../models/Database.php';

class RedalycGenerator {
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
        
        // Obtener markup data (si existe) para tablas/figuras
        $markup = $this->articleModel->getMarkup($articleId);
        $markupTables = [];
        $markupFigures = [];
        if ($markup && isset($markup['markup_data'])) {
            $markupTables = $markup['markup_data']['tables'] ?? [];
            $markupFigures = $markup['markup_data']['images'] ?? [];
        }
        
        // Crear documento XML
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        
        // DOCTYPE
        $implementation = new DOMImplementation();
        $dtd = $implementation->createDocumentType(
            'article',
            '-//NLM//DTD JATS (Z39.96) Journal Publishing DTD v1.1d3 20150301//EN',
            'http://jats.nlm.nih.gov/publishing/1.1d3/JATS-journalpublishing1.dtd'
        );
        $dom = $implementation->createDocument('', '', $dtd);
        $dom->encoding = 'UTF-8';
        
        $pi = $dom->createProcessingInstruction('xml-model', 'type="application/xml-dtd" href="http://jats.nlm.nih.gov/publishing/1.1d3/JATS-journalpublishing1.dtd"');
        $dom->insertBefore($pi, $dom->firstChild);
        
        // Elemento raíz <article>
        $articleEl = $dom->createElement('article');
        $articleEl->setAttribute('xmlns:ali', 'http://www.niso.org/schemas/ali/1.0');
        $articleEl->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $articleEl->setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
        $articleEl->setAttribute('xmlns:mml', 'http://www.w3.org/1998/Math/MathML');
        $articleEl->setAttribute('dtd-version', '1.1d3');
        $articleEl->setAttribute('specific-use', 'Marcalyc 1.2');
        $articleEl->setAttribute('article-type', $article['article_type'] ?? 'research-article');
        $articleEl->setAttribute('xml:lang', $article['language'] ?? 'es');
        $dom->appendChild($articleEl);
        
        // Front matter
        $front = $dom->createElement('front');
        $articleEl->appendChild($front);
        
        // Journal metadata
        $journalMeta = $this->createJournalMeta($dom, $journal);
        $front->appendChild($journalMeta);
        
        // Article metadata
        $articleMeta = $this->createArticleMeta($dom, $article, $authors, $affiliations);
        $front->appendChild($articleMeta);
        // Body (Sections)
        // Body
        $body = $this->createBody($dom, $sections, $markupTables, $markupFigures);
        $articleEl->appendChild($body);
        
        // Back matter (referencias)
        $back = $this->createBack($dom, $references);
        $articleEl->appendChild($back);
        $xmlContent = $dom->saveXML();
        
        // Guardar archivo
        $config = require __DIR__ . '/../../config/config.php';
        $articleDir = $config['paths']['articles'] . $article['article_id'];
        
        if (!is_dir($articleDir)) {
            mkdir($articleDir, 0755, true);
        }
        
        $xmlPath = $articleDir . '/redalyc.xml';
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
            'download_url' => 'articles/' . $article['article_id'] . '/redalyc.xml'
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
        
        // Essential for Redalyc/SciELO: abbrev-journal-title
        $abbrevTitle = $dom->createElement('abbrev-journal-title', htmlspecialchars($journal['abbrev_title'] ?? ($journal['title'] ?? '')));
        $abbrevTitle->setAttribute('abbrev-type', 'publisher');
        $journalTitleGroup->appendChild($abbrevTitle);
        
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
        // JATS4R: Recommended NOT to duplicate article lang in title unless translating
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
            $pubDate->setAttribute('date-type', 'pub'); // Required
            $pubDate->setAttribute('publication-format', 'electronic'); // Required
            $this->addDateElements($dom, $pubDate, $article['published_date']);
            $articleMeta->appendChild($pubDate);
        }

        // Essential sequence for JATS
        if (!empty($article['volume_number'])) {
            $volume = $dom->createElement('volume', htmlspecialchars($article['volume_number']));
            $articleMeta->appendChild($volume);
        }
        if (!empty($article['issue_number'])) {
            $issue = $dom->createElement('issue', htmlspecialchars($article['issue_number']));
            $articleMeta->appendChild($issue);
        }
        $elocationId = $dom->createElement('elocation-id', htmlspecialchars($article['pages'] ?? 'e' . $article['article_id']));
        $articleMeta->appendChild($elocationId);
        
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
        
        // JATS4R: Required copyright-holder
        $copyrightHolder = $dom->createElement('copyright-holder', 'Autores (Authors)');
        $permissions->appendChild($copyrightHolder);
        
        // Creative Commons License CC BY 4.0
        $license = $dom->createElement('license');
        $license->setAttribute('license-type', 'open-access');
        // JATS4R: Use HTTPS and trailing slash
        $licenseURL = 'https://creativecommons.org/licenses/by/4.0/';
        $license->setAttribute('xlink:href', $licenseURL);
        
        // JATS 1.1d3+ standard for license Reference
        $licenseRef = $dom->createElement('ali:license_ref', $licenseURL);
        $license->appendChild($licenseRef);
        
        $licenseP = $dom->createElement('license-p', 'Este es un artículo de acceso abierto bajo la licencia CC BY 4.0');
        $license->appendChild($licenseP);
        $permissions->appendChild($license);
        
        $articleMeta->appendChild($permissions);
        
        // Abstract
        if (!empty($article['abstract'])) {
            $abstract = $dom->createElement('abstract');
            $abstractP = $dom->createElement('p', htmlspecialchars($article['abstract']));
            $abstract->appendChild($abstractP);
            $articleMeta->appendChild($abstract);
        }
        
        if (!empty($article['abstract_en'])) {
            $transAbstract = $dom->createElement('trans-abstract');
            $transAbstract->setAttribute('xml:lang', 'en');
            $abstractPEn = $dom->createElement('p', htmlspecialchars($article['abstract_en']));
            $transAbstract->appendChild($abstractPEn);
            $articleMeta->appendChild($transAbstract);
        }
        
        // Keywords
        if (!empty($article['keywords'])) {
            $kwdGroup = $dom->createElement('kwd-group');
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

            // ORCID (Must come before name or xref in Redalyc DTD)
            if (!empty($author['orcid'])) {
                $contribId = $dom->createElement('contrib-id', 'https://orcid.org/' . $author['orcid']);
                $contribId->setAttribute('contrib-id-type', 'orcid');
                $contrib->appendChild($contribId);
            }

            // Name
            $name = $dom->createElement('name');
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
     * Crear sección
     */
    private function createSection($dom, $section, $tables, $figures) {
        $sec = $dom->createElement('sec');
        $sec->setAttribute('id', $section['section_id']);
        
        if (!empty($section['title'])) {
            $title = $dom->createElement('title', htmlspecialchars($section['title']));
            $sec->appendChild($title);
        }
        
        if (!empty($section['content'])) {
            $content = $section['content'];
            
            // Regex para detectar etiquetas de posición [Tabla X] o [Figura X]
            $pattern = '/(\[(?:Tabla|Figura|Table|Figure)\s*[^\]]+\])/i';
            $parts = preg_split($pattern, $content, -1, PREG_SPLIT_DELIM_CAPTURE);
            
            $currentP = null;
            
            foreach ($parts as $part) {
                if (empty($part)) continue;
                
                $cleanPart = strip_tags($part);
                
                if (preg_match('/^\[(Tabla|Table)\s*([^\]]+)\]$/i', $cleanPart, $matches)) {
                    $labelToFind = trim($matches[1] . ' ' . $matches[2]);
                    $this->appendTableByLabel($dom, $sec, $labelToFind, $tables);
                    $currentP = null;
                } elseif (preg_match('/^\[(Figura|Figure)\s*([^\]]+)\]$/i', $cleanPart, $matches)) {
                    $labelToFind = trim($matches[1] . ' ' . $matches[2]);
                    $this->appendFigureByLabel($dom, $sec, $labelToFind, $figures);
                    $currentP = null;
                } else {
                    if (!$currentP) {
                        $currentP = $dom->createElement('p');
                        $sec->appendChild($currentP);
                    }
                    
                    // Importar HTML básico (b, i, u, sub, sup) y convertir <a> a <xref>
                    $textToImport = $part;
                    
                    // Footnotes: <a href="#fn-1" data-fnid="fn-1">...</a> -> <xref ref-type="fn" rid="fn-1">...</xref>
                    $textToImport = preg_replace('/<a\s+[^>]*data-fnid=[\'"]([^\'"]+)[\'"][^>]*>(.*?)<\/a>/i', '<xref ref-type="fn" rid="$1">$2</xref>', $textToImport);
                    
                    // References: <a href="#ref-1" data-refid="ref-1" ...>...</a> -> <xref ref-type="bibr" rid="ref-1">...</xref>
                    $textToImport = preg_replace('/<a\s+[^>]*data-refid=[\'"]([^\'"]+)[\'"][^>]*>(.*?)<\/a>/i', '<xref ref-type="bibr" rid="$1">$2</xref>', $textToImport);
                    
                    $allowedTags = '<b><i><u><sub><sup><xref>';
                    $fragment = $dom->createDocumentFragment();
                    $textToImport = strip_tags($textToImport, $allowedTags);
                    
                    try {
                        @$fragment->appendXML($textToImport);
                        $currentP->appendChild($fragment);
                    } catch (Exception $e) {
                        $currentP->appendChild($dom->createTextNode($cleanPart));
                    }
                }
            }
        }
        
        return $sec;
    }

    /**
     * Insertar tabla por etiqueta
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
        $p = $dom->createElement('p', htmlspecialchars($tableData['caption'] ?? ($tableData['title'] ?? '')));
        $caption->appendChild($p);
        $tableWrap->appendChild($caption);

        if (!empty($tableData['src']) && ($tableData['type'] ?? '') === 'image') {
            $graphic = $dom->createElement('graphic');
            $graphic->setAttribute('xlink:href', htmlspecialchars($tableData['src']));
            $tableWrap->appendChild($graphic);
        } elseif (!empty($tableData['html'])) {
            $fragment = $dom->createDocumentFragment();
            $tableHtml = preg_replace('/style="[^"]*"|class="[^"]*"|id="[^"]*"/i', '', ($tableData['html'] ?? $tableData['html_content'] ?? ''));
            try {
                @$fragment->appendXML($tableHtml);
                $tableWrap->appendChild($fragment);
            } catch (Exception $e) {
                $tableWrap->appendChild($dom->createElement('table'));
            }
        }
        
        if (!empty($tableData['nota'])) {
            $footer = $dom->createElement('table-wrap-foot');
            $fn = $dom->createElement('fn');
            $p = $dom->createElement('p', 'Nota. ' . htmlspecialchars($tableData['nota']));
            $fn->appendChild($p);
            $footer->appendChild($fn);
            $tableWrap->appendChild($footer);
        }
    }

    /**
     * Insertar figura por etiqueta
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
        
        if (!empty($figData['nota'])) {
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
        
        // Required by Redalyc/Marcalyc/JATS4R
        $mixedCitation = $dom->createElement('mixed-citation');
        $mixedCitation->setAttribute('publication-type', $ref['reference_type'] ?? 'journal');
        
        if (!empty($ref['authors'])) {
            $personGroup = $dom->createElement('person-group');
            $personGroup->setAttribute('person-group-type', 'author');
            $personGroup->appendChild($dom->createTextNode(htmlspecialchars($ref['authors'])));
            $mixedCitation->appendChild($personGroup);
            $mixedCitation->appendChild($dom->createTextNode('. '));
        }

        $citationText = "";
        if (!empty($ref['year'])) $citationText .= "(" . $ref['year'] . "). ";
        if (!empty($ref['title'])) $citationText .= $ref['title'] . ". ";
        if (!empty($ref['source'])) $citationText .= $ref['source'] . ". ";
        if (!empty($ref['volume'])) $citationText .= "Vol. " . $ref['volume'] . ". ";
        if (!empty($ref['doi'])) $citationText .= "DOI: " . $ref['doi'] . ". ";
        if (!empty($ref['url'])) $citationText .= "URL: " . $ref['url'];
        
        $mixedCitation->appendChild($dom->createTextNode(trim($citationText)));
        $refElement->appendChild($mixedCitation);

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
