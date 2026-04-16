<?php
/**
 * Clase ScieloGenerator - Generación de XML-JATS con estándares de SciELO
 */

require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../models/Database.php';

class ScieloGenerator {
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
        
        // Obtener markup data de respaldo
        $markup = $this->articleModel->getMarkup($articleId);
        $md = ($markup && isset($markup['markup_data'])) ? $markup['markup_data'] : [];
        $markupTables  = $md['tables'] ?? [];
        $markupFigures = $md['images'] ?? [];
        $markupSections = $md['sections'] ?? [];

        // FALLBACKS DE DATOS DESDE EL EDITOR (MARKUP_DATA)
        
        // 1. Secciones: si la tabla article_sections está vacía
        if (empty($sections) && !empty($markupSections)) {
            $sections = array_map(function($s, $i) {
                return [
                    'section_id'   => 'sec-' . ($i + 1),
                    'section_type' => $s['type'] ?? 'other',
                    'title'        => $s['type_name'] ?? ($s['title'] ?? ''),
                    'content'      => $s['content'] ?? '',
                    'level'        => $s['level'] ?? 1,
                    'section_order'=> $i + 1,
                ];
            }, $markupSections, array_keys($markupSections));
        }

        // 2. Tablas: merge de article_tables con markup_data (markup manda)
        if (empty($tables) && !empty($markupTables)) {
            $tables = $markupTables;
        } elseif (!empty($markupTables)) {
            $markupTablesIndexed = [];
            foreach ($markupTables as $mt) {
                $markupTablesIndexed[strtolower(trim($mt['label'] ?? ''))] = $mt;
            }
            $mergedTables = [];
            foreach ($tables as $t) {
                $key = strtolower(trim($t['label'] ?? ''));
                $mergedTables[] = $markupTablesIndexed[$key] ?? $t;
            }
            // Agregar tablas nuevas del editor
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

        // 3. Autores: si article_authors está vacía
        if (empty($authors) && !empty($md['authors'])) {
            $authors = $md['authors'];
        }

        // 4. Afiliaciones: extraer de autores si la tabla está vacía
        if (empty($affiliations)) {
            $affiliations = $md['affiliations'] ?? [];
            if (empty($affiliations) && !empty($authors)) {
                $tempAffs = [];
                foreach ($authors as &$author) {
                    if (!empty($author['affiliation']) && empty($author['affiliation_id'])) {
                        $affId = 'aff-' . crc32($author['affiliation']);
                        $author['affiliation_id'] = $affId;
                        $tempAffs[$affId] = [
                            'affiliation_id' => $affId,
                            'institution' => $author['affiliation']
                        ];
                    }
                }
                $affiliations = array_values($tempAffs);
            }
        }

        // 5. Figuras
        if (empty($figures) && !empty($markupFigures)) {
            $figures = $markupFigures;
        }
        
        // Crear documento XML
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        
        // DOCTYPE SciELO compatible
        $implementation = new DOMImplementation();
        $dtd = $implementation->createDocumentType(
            'article',
            '-//NLM//DTD JATS (Z39.96) Journal Publishing DTD v1.1 20151215//EN',
            'http://jats.nlm.nih.gov/publishing/1.1/JATS-journalpublishing1.dtd'
        );
        $dom = $implementation->createDocument('', '', $dtd);
        $dom->encoding = 'UTF-8';
        $dom->formatOutput = true;
        
        // Elemento raíz <article>
        $articleEl = $dom->createElement('article');
        $articleEl->setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
        $articleEl->setAttribute('xmlns:mml', 'http://www.w3.org/1998/Math/MathML');
        $articleEl->setAttribute('dtd-version', '1.1');
        $articleEl->setAttribute('specific-use', 'sps-1.9');
        $articleEl->setAttribute('article-type', 'research-article');
        $articleEl->setAttribute('xml:lang', 'es');
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
        $body = $this->createBody($dom, $sections, $tables, $figures);
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
        
        $xmlPath = $articleDir . '/scielo.xml';
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
            'download_url' => 'articles/' . $article['article_id'] . '/scielo.xml'
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
        
        // Essential for SciELO: abbrev-journal-title
        $abbrevTitle = $dom->createElement('abbrev-journal-title', htmlspecialchars($journal['abbrev_title'] ?? ($journal['title'] ?? '')));
        $abbrevTitle->setAttribute('abbrev-type', 'publisher');
        $journalTitleGroup->appendChild($abbrevTitle);
        
        $journalMeta->appendChild($journalTitleGroup);
        
        // ISSN
        if (!empty($journal['issn_print'])) {
            $issnPrint = $dom->createElement('issn', $journal['issn_print']);
            $issnPrint->setAttribute('pub-type', 'ppub');
            $journalMeta->appendChild($issnPrint);
        }
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
        $articleIdNode = $dom->createElement('article-id', htmlspecialchars($article['article_id']));
        $articleIdNode->setAttribute('pub-id-type', 'publisher-id');
        $articleMeta->appendChild($articleIdNode);
        
        // DOI
        if (!empty($article['doi'])) {
            $doiElement = $dom->createElement('article-id', htmlspecialchars($article['doi']));
            $doiElement->setAttribute('pub-id-type', 'doi');
            $articleMeta->appendChild($doiElement);
        }
        
        // Article Categories / Section
        if (!empty($article['article_type']) || !empty($article['subject'])) {
            $articleCategories = $dom->createElement('article-categories');
            $subjGroup = $dom->createElement('subj-group');
            $subjGroup->setAttribute('subj-group-type', 'heading');
            $subject = $dom->createElement('subject', htmlspecialchars($article['article_type'] ?? ($article['subject'] ?? 'Articulo')));
            $subjGroup->appendChild($subject);
            $articleCategories->appendChild($subjGroup);
            $articleMeta->appendChild($articleCategories);
        }
        
        // Title group
        $titleGroup = $dom->createElement('title-group');
        $title = $dom->createElement('article-title');
        $title->appendChild($dom->createTextNode($article['title']));
        $titleGroup->appendChild($title);
        
        // English Title Fallback
        $titleEn = $article['title_en'] ?? ($article['english_title'] ?? '');
        if (!empty($titleEn)) {
            $transTitleGroup = $dom->createElement('trans-title-group');
            $transTitleGroup->setAttribute('xml:lang', 'en');
            $transTitle = $dom->createElement('trans-title', htmlspecialchars($titleEn));
            $transTitleGroup->appendChild($transTitle);
            $titleGroup->appendChild($transTitleGroup);
        }
        
        $articleMeta->appendChild($titleGroup);
        
        // Contributors (authors)
        $contribGroup = $this->createContribGroup($dom, $authors, $affiliations);
        $articleMeta->appendChild($contribGroup);
        
        // Affiliations
        if (empty($affiliations)) {
            $fallbackAff = ['affiliation_id' => 'aff1', 'institution' => $article['publisher'] ?? 'UNAP', 'country' => 'Perú'];
            $articleMeta->appendChild($this->createAffiliation($dom, $fallbackAff));
        } else {
            foreach ($affiliations as $aff) {
                $articleMeta->appendChild($this->createAffiliation($dom, $aff));
            }
        }
        
        // Publication dates
        if (!empty($article['published_date'])) {
            $pubDate = $dom->createElement('pub-date');
            $pubDate->setAttribute('pub-type', 'epub');
            $pubDate->setAttribute('date-type', 'pub');
            $pubDate->setAttribute('publication-format', 'electronic');
            $this->addDateElements($dom, $pubDate, $article['published_date']);
            $articleMeta->appendChild($pubDate);
        }
        
        // Volume, Issue, Elocation
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
        
        // Permissions
        $permissions = $dom->createElement('permissions');
        $copyrightStatement = $dom->createElement('copyright-statement', '© ' . date('Y') . ' Autores (Authors)');
        $permissions->appendChild($copyrightStatement);
        $copyrightYear = $dom->createElement('copyright-year', date('Y'));
        $permissions->appendChild($copyrightYear);
        
        $license = $dom->createElement('license');
        $license->setAttribute('license-type', 'open-access');
        $license->setAttribute('xlink:href', 'http://creativecommons.org/licenses/by/4.0/');
        $license->setAttribute('xml:lang', 'es');
        $licenseP = $dom->createElement('license-p', 'Este es un artículo de acceso abierto bajo la licencia CC BY 4.0');
        $license->appendChild($licenseP);
        $permissions->appendChild($license);
        $articleMeta->appendChild($permissions);
        
        // Abstract
        if (!empty($article['abstract'])) {
            $abstract = $dom->createElement('abstract');
            $abstract->appendChild($dom->createElement('p', htmlspecialchars($article['abstract'])));
            $articleMeta->appendChild($abstract);
        }
        if (!empty($article['abstract_en'])) {
            $abstractEn = $dom->createElement('trans-abstract');
            $abstractEn->setAttribute('xml:lang', 'en');
            $abstractEn->appendChild($dom->createElement('p', htmlspecialchars($article['abstract_en'])));
            $articleMeta->appendChild($abstractEn);
        }
        
        // Keywords
        if (!empty($article['keywords'])) {
            $kwdGroup = $dom->createElement('kwd-group');
            $kwdGroup->setAttribute('xml:lang', 'es');
            foreach (explode(',', $article['keywords']) as $keyword) {
                $kwdGroup->appendChild($dom->createElement('kwd', htmlspecialchars(trim($keyword))));
            }
            $articleMeta->appendChild($kwdGroup);
        }
        if (!empty($article['keywords_en'])) {
            $kwdGroupEn = $dom->createElement('kwd-group');
            $kwdGroupEn->setAttribute('xml:lang', 'en');
            foreach (explode(',', $article['keywords_en']) as $keyword) {
                $kwdGroupEn->appendChild($dom->createElement('kwd', htmlspecialchars(trim($keyword))));
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
            
            if (!empty($author['orcid'])) {
                $contribId = $dom->createElement('contrib-id', 'https://orcid.org/' . $author['orcid']);
                $contribId->setAttribute('contrib-id-type', 'orcid');
                $contrib->appendChild($contribId);
            }

            $name = $dom->createElement('name');
            $name->appendChild($dom->createElement('surname', htmlspecialchars($author['surname'] ?? '')));
            $name->appendChild($dom->createElement('given-names', htmlspecialchars($author['given_names'] ?? '')));
            $contrib->appendChild($name);
            
            $affId = !empty($author['affiliation_id']) ? $author['affiliation_id'] : (isset($affiliations[0]['affiliation_id']) ? $affiliations[0]['affiliation_id'] : 'aff1');
            $xref = $dom->createElement('xref');
            $xref->setAttribute('ref-type', 'aff');
            $xref->setAttribute('rid', $affId);
            $contrib->appendChild($xref);
            
            if (!empty($author['email'])) {
                $contrib->appendChild($dom->createElement('email', $author['email']));
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
        $affElement->setAttribute('id', $aff['affiliation_id'] ?? 'aff1');
        
        $instName = htmlspecialchars($aff['institution'] ?? 'UNAP');
        $institutionOriginal = $dom->createElement('institution', $instName);
        $institutionOriginal->setAttribute('content-type', 'original');
        $affElement->appendChild($institutionOriginal);
        
        $institutionOrg = $dom->createElement('institution', $instName);
        $institutionOrg->setAttribute('content-type', 'orgname');
        $affElement->appendChild($institutionOrg);
        
        if (!empty($aff['country'])) {
            $country = $dom->createElement('country', htmlspecialchars($aff['country']));
            $country->setAttribute('country', 'PE');
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
            $body->appendChild($this->createSection($dom, $section, $tables, $figures));
        }
        return $body;
    }
    
    /**
     * Crear sección con soporte de múltiples párrafos HTML del editor
     */
    private function createSection($dom, $section, $tables, $figures) {
        $sec = $dom->createElement('sec');
        $sec->setAttribute('id', $section['section_id'] ?? 'sec-' . uniqid());

        if (!empty($section['title'])) {
            $sec->appendChild($dom->createElement('title', htmlspecialchars($section['title'])));
        }

        if (!empty($section['content'])) {
            $blocks = $this->extractParagraphsFromHtml($section['content']);
            foreach ($blocks as $block) {
                if (empty(trim($block))) continue;
                
                $cleanBlock = trim(strip_tags($block));
                // Placeholder detect
                if (preg_match('/^\[(Tabla|Figura|Table|Figure)\s*([^\]]+)\]$/i', $cleanBlock, $m)) {
                    if (stripos($m[1], 'Tab') !== false) $this->appendTableByLabel($dom, $sec, trim($m[1] . ' ' . $m[2]), $tables);
                    else $this->appendFigureByLabel($dom, $sec, trim($m[1] . ' ' . $m[2]), $figures);
                    continue;
                }

                $pattern = '/(\[(?:Tabla|Figura|Table|Figure)\s*[^\]]+\])/i';
                $parts = preg_split($pattern, $block, -1, PREG_SPLIT_DELIM_CAPTURE);
                $currentP = null;

                foreach ($parts as $part) {
                    if ($part === '') continue;
                    $cleanPart = trim(strip_tags($part));

                    if (preg_match('/^\[(Tabla|Table)\s*([^\]]+)\]$/i', $cleanPart, $m)) {
                        if ($currentP) { $sec->appendChild($currentP); $currentP = null; }
                        $this->appendTableByLabel($dom, $sec, trim($m[1] . ' ' . $m[2]), $tables);
                    } elseif (preg_match('/^\[(Figura|Figure)\s*([^\]]+)\]$/i', $cleanPart, $m)) {
                        if ($currentP) { $sec->appendChild($currentP); $currentP = null; }
                        $this->appendFigureByLabel($dom, $sec, trim($m[1] . ' ' . $m[2]), $figures);
                    } else {
                        if (!$currentP) $currentP = $dom->createElement('p');
                        $text = $this->htmlToXmlFragment($part);
                        if (trim($text) !== '') {
                            $frag = $dom->createDocumentFragment();
                            if (@$frag->appendXML($text)) $currentP->appendChild($frag);
                            else $currentP->appendChild($dom->createTextNode(strip_tags($text)));
                        }
                    }
                }
                if ($currentP) $sec->appendChild($currentP);
            }
        }
        return $sec;
    }

    private function extractParagraphsFromHtml(string $html): array {
        $html = str_replace(["\r\n", "\r", "\n"], " ", $html);
        $break = '||PARBREAK||';
        $html = preg_replace('/<\/(p|div|li|h[1-6]|blockquote)>/i', $break, $html);
        $html = preg_replace('/(<br\s*\/?>\s*){2,}/i', $break, $html);
        $html = preg_replace('/<(p|div|ul|ol|li|h[1-6]|blockquote)[^>]*>/i', '', $html);
        $chunks = explode($break, $html);
        $result = [];
        foreach ($chunks as $chunk) {
            $chunk = preg_replace('/\s+/', ' ', $chunk);
            $chunk = trim($chunk);
            if ($chunk !== '') $result[] = $chunk;
        }
        return $result;
    }

    private function htmlToXmlFragment(string $html): string {
        $map = ['&nbsp;'=>'&#160;', '&ndash;'=>'–', '&mdash;'=>'—', '&ldquo;'=>'"', '&rdquo;'=>'"', '&hellip;'=>'…', '&bull;'=>'•'];
        foreach ($map as $entity => $char) $html = str_replace($entity, $char, $html);
        $html = html_entity_decode($html, ENT_HTML5 | ENT_QUOTES, 'UTF-8');
        $html = preg_replace('/&(?!(?:[a-zA-Z]+|#\d+|#x[0-9a-fA-F]+);)/', '&amp;', $html);
        return $html;
    }

    private function tableHtmlToXml(string $html): string {
        $html = preg_replace('/\s+(style|class|id)="[^"]*"/i', '', $html);
        libxml_use_internal_errors(true);
        $tmpDom = new DOMDocument('1.0', 'UTF-8');
        $tmpDom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $tableTags = $tmpDom->getElementsByTagName('table');
        return $tableTags->length > 0 ? $tmpDom->saveXML($tableTags->item(0)) : '';
    }

    private function appendTableByLabel($dom, $parent, $label, $tables) {
        $data = null;
        foreach ($tables as $t) { if (strcasecmp(trim($t['label'] ?? ''), $label) === 0) { $data = $t; break; } }
        if (!$data) return;
        $tw = $dom->createElement('table-wrap');
        $tw->setAttribute('id', 't-' . uniqid());
        $parent->appendChild($tw);
        $tw->appendChild($dom->createElement('label', htmlspecialchars($data['label'] ?? 'Tabla')));
        $cap = $dom->createElement('caption');
        $cap->appendChild($dom->createElement('p', htmlspecialchars($data['caption'] ?? ($data['title'] ?? ''))));
        $tw->appendChild($cap);
        if (!empty($data['src']) && ($data['type'] ?? '') === 'image') {
            $g = $dom->createElement('graphic'); $g->setAttribute('xlink:href', htmlspecialchars($data['src'])); $tw->appendChild($g);
        } elseif (!empty($data['html'])) {
            $f = $dom->createDocumentFragment();
            $xml = $this->tableHtmlToXml($data['html']);
            if ($xml && @$f->appendXML($xml)) $tw->appendChild($f);
            else $tw->appendChild($dom->createElement('table'));
        }
        if (!empty($data['nota'])) {
            $foot = $dom->createElement('table-wrap-foot'); $fn = $dom->createElement('fn');
            $fn->appendChild($dom->createElement('p', 'Nota. ' . htmlspecialchars($data['nota'])));
            $foot->appendChild($fn); $tw->appendChild($foot);
        }
    }

    private function appendFigureByLabel($dom, $parent, $label, $figures) {
        $data = null;
        foreach ($figures as $f) { if (strcasecmp(trim($f['label'] ?? ''), $label) === 0) { $data = $f; break; } }
        if (!$data) return;
        $fig = $dom->createElement('fig'); $fig->setAttribute('id', 'f-' . uniqid()); $parent->appendChild($fig);
        $fig->appendChild($dom->createElement('label', htmlspecialchars($data['label'] ?? 'Figura')));
        $cap = $dom->createElement('caption');
        $cap->appendChild($dom->createElement('p', htmlspecialchars($data['alt'] ?? ($data['caption'] ?? ''))));
        $fig->appendChild($cap);
        $g = $dom->createElement('graphic'); $g->setAttribute('xlink:href', htmlspecialchars($data['src'] ?? '')); $fig->appendChild($g);
        if (!empty($data['nota'])) $fig->appendChild($dom->createElement('p', 'Nota. ' . htmlspecialchars($data['nota'])));
    }

    private function createBack($dom, $references) {
        $back = $dom->createElement('back');
        if (!empty($references)) {
            $rl = $dom->createElement('ref-list'); $rl->appendChild($dom->createElement('title', 'Referencias'));
            foreach ($references as $r) $rl->appendChild($this->createReference($dom, $r));
            $back->appendChild($rl);
        }
        return $back;
    }

    private function createReference($dom, $ref) {
        $rEl = $dom->createElement('ref'); $rEl->setAttribute('id', $ref['ref_id'] ?? 'ref-' . uniqid());
        $cit = $dom->createElement('element-citation');
        $cit->setAttribute('publication-type', $ref['reference_type'] ?? 'journal');
        if (!empty($ref['authors'])) { $pg = $dom->createElement('person-group'); $pg->setAttribute('person-group-type', 'author'); $cit->appendChild($pg); }
        if (!empty($ref['year'])) $cit->appendChild($dom->createElement('year', $ref['year']));
        if (!empty($ref['title'])) $cit->appendChild($dom->createElement('article-title', htmlspecialchars($ref['title'])));
        if (!empty($ref['source'])) $cit->appendChild($dom->createElement('source', htmlspecialchars($ref['source'])));
        if (!empty($ref['doi'])) { $doi = $dom->createElement('pub-id', $ref['doi']); $doi->setAttribute('pub-id-type', 'doi'); $cit->appendChild($doi); }
        $rEl->appendChild($cit);
        $mix = $dom->createElement('mixed-citation');
        $txt = ($ref['authors'] ?? '') . ". " . (!empty($ref['year']) ? "(".$ref['year']."). " : "") . ($ref['title'] ?? "") . ". " . ($ref['source'] ?? "");
        $mix->appendChild($dom->createTextNode(trim($txt)));
        $rEl->appendChild($mix);
        return $rEl;
    }

    private function addDateElements($dom, $parentElement, $dateString) {
        try {
            $date = new DateTime($dateString);
            $parentElement->appendChild($dom->createElement('day', $date->format('d')));
            $parentElement->appendChild($dom->createElement('month', $date->format('m')));
            $parentElement->appendChild($dom->createElement('year', $date->format('Y')));
        } catch (Exception $e) {}
    }

    private function getJournalInfo($article) {
        $sql = "SELECT j.* FROM journals j LEFT JOIN volumes v ON j.id = v.journal_id LEFT JOIN issues i ON v.id = i.volume_id WHERE i.id = :issue_id";
        $journal = $this->db->fetchOne($sql, ['issue_id' => $article['issue_id']]);
        if (!$journal) $journal = $this->db->fetchOne("SELECT * FROM journals WHERE active = TRUE LIMIT 1");
        return $journal ?: [];
    }
}
