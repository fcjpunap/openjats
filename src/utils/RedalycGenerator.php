<?php
/**
 * Clase RedalycGenerator - Generación de XML-JATS con estándares de Redalyc (Marcalyc)
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
        
        // Obtener datos iniciales
        $authors = $this->articleModel->getAuthors($articleId);
        $affiliations = $this->articleModel->getAffiliations($articleId);
        $sections = $this->articleModel->getSections($articleId);
        $tables = $this->articleModel->getTables($articleId);
        $figures = $this->articleModel->getFigures($articleId);
        $references = $this->articleModel->getReferences($articleId);
        
        $journal = $this->getJournalInfo($article);
        
        // Markup data fallback
        $markup = $this->articleModel->getMarkup($articleId);
        $md = ($markup && isset($markup['markup_data'])) ? $markup['markup_data'] : [];
        $markupTables  = $md['tables'] ?? [];
        $markupFigures = $md['images'] ?? [];
        $markupSections = $md['sections'] ?? [];

        // FALLBACKS
        if (empty($sections) && !empty($markupSections)) {
            $sections = array_map(function($s, $i) {
                return [
                    'section_id' => 'sec-'.($i+1), 'section_type' => $s['type'] ?? 'other',
                    'title' => $s['type_name'] ?? ($s['title'] ?? ''), 'content' => $s['content'] ?? '',
                    'level' => $s['level'] ?? 1, 'section_order' => $i+1
                ];
            }, $markupSections, array_keys($markupSections));
        }

        if (empty($tables) && !empty($markupTables)) {
            $tables = $markupTables;
        } elseif (!empty($markupTables)) {
            $indexed = []; foreach($markupTables as $mt) $indexed[strtolower(trim($mt['label'] ?? ''))] = $mt;
            $merged = []; foreach($tables as $t) $merged[] = $indexed[strtolower(trim($t['label'] ?? ''))] ?? $t;
            foreach($markupTables as $mt) {
                $found = false; foreach($merged as $m) if(strtolower(trim($m['label'] ?? '')) === strtolower(trim($mt['label'] ?? ''))) { $found=true; break; }
                if(!$found) $merged[] = $mt;
            }
            $tables = $merged;
        }

        if (empty($authors) && !empty($md['authors'])) $authors = $md['authors'];
        
        if (empty($affiliations)) {
            $affiliations = $md['affiliations'] ?? [];
            if (empty($affiliations) && !empty($authors)) {
                $temp = [];
                foreach ($authors as &$author) {
                    if (!empty($author['affiliation']) && empty($author['affiliation_id'])) {
                        $id = 'aff-' . crc32($author['affiliation']);
                        $author['affiliation_id'] = $id;
                        $temp[$id] = ['affiliation_id' => $id, 'institution' => $author['affiliation']];
                    }
                }
                $affiliations = array_values($temp);
            }
        }

        if (empty($figures) && !empty($markupFigures)) $figures = $markupFigures;
        
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        
        $implementation = new DOMImplementation();
        $dtd = $implementation->createDocumentType('article', '-//NLM//DTD JATS (Z39.96) Journal Publishing DTD v1.1d3 20150301//EN', 'http://jats.nlm.nih.gov/publishing/1.1d3/JATS-journalpublishing1.dtd');
        $dom = $implementation->createDocument('', '', $dtd);
        $dom->encoding = 'UTF-8';
        
        $pi = $dom->createProcessingInstruction('xml-model', 'type="application/xml-dtd" href="http://jats.nlm.nih.gov/publishing/1.1d3/JATS-journalpublishing1.dtd"');
        $dom->insertBefore($pi, $dom->firstChild);
        
        $articleEl = $dom->createElement('article');
        $articleEl->setAttribute('xmlns:ali', 'http://www.niso.org/schemas/ali/1.0');
        $articleEl->setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
        $articleEl->setAttribute('xmlns:mml', 'http://www.w3.org/1998/Math/MathML');
        $articleEl->setAttribute('dtd-version', '1.1d3');
        $articleEl->setAttribute('specific-use', 'Marcalyc 1.2');
        $articleEl->setAttribute('article-type', $article['article_type'] ?? 'research-article');
        $articleEl->setAttribute('xml:lang', $article['language'] ?? 'es');
        $dom->appendChild($articleEl);
        
        $front = $dom->createElement('front');
        $articleEl->appendChild($front);
        $front->appendChild($this->createJournalMeta($dom, $journal));
        $front->appendChild($this->createArticleMeta($dom, $article, $authors, $affiliations));
        
        $articleEl->appendChild($this->createBody($dom, $sections, $tables, $figures));
        $articleEl->appendChild($this->createBack($dom, $references));
        
        $xmlContent = $dom->saveXML();
        
        $config = require __DIR__ . '/../../config/config.php';
        $articleDir = $config['paths']['articles'] . $article['article_id'];
        if (!is_dir($articleDir)) mkdir($articleDir, 0755, true);
        
        $xmlPath = $articleDir . '/redalyc.xml';
        file_put_contents($xmlPath, $xmlContent);
        
        $this->articleModel->addFile(['article_id' => $articleId, 'file_type' => 'xml_jats', 'file_path' => $xmlPath, 'file_size' => strlen($xmlContent), 'mime_type' => 'application/xml']);
        
        return ['success' => true, 'file_path' => $xmlPath, 'download_url' => 'articles/' . $article['article_id'] . '/redalyc.xml'];
    }
    
    private function createJournalMeta($dom, $jl) {
        $jm = $dom->createElement('journal-meta');
        $jm->appendChild($dom->createElement('journal-id', htmlspecialchars($jl['id'] ?? 'JOURNAL')))->setAttribute('journal-id-type', 'publisher-id');
        $jtg = $dom->createElement('journal-title-group');
        $jtg->appendChild($dom->createElement('journal-title', htmlspecialchars($jl['title'] ?? '')));
        $ab = $dom->createElement('abbrev-journal-title', htmlspecialchars($jl['abbrev_title'] ?? ($jl['title'] ?? '')));
        $ab->setAttribute('abbrev-type', 'publisher');
        $jtg->appendChild($ab);
        $jm->appendChild($jtg);
        if (!empty($jl['issn_print'])) $jm->appendChild($dom->createElement('issn', $jl['issn_print']))->setAttribute('pub-type', 'ppub');
        if (!empty($jl['issn_electronic'])) $jm->appendChild($dom->createElement('issn', $jl['issn_electronic']))->setAttribute('pub-type', 'epub');
        $jm->appendChild($dom->createElement('publisher'))->appendChild($dom->createElement('publisher-name', htmlspecialchars($jl['publisher'] ?? '')));
        return $jm;
    }
    
    private function createArticleMeta($dom, $article, $authors, $affiliations) {
        $am = $dom->createElement('article-meta');
        $am->appendChild($dom->createElement('article-id', htmlspecialchars($article['article_id'])))->setAttribute('pub-id-type', 'publisher-id');
        if (!empty($article['doi'])) $am->appendChild($dom->createElement('article-id', htmlspecialchars($article['doi'])))->setAttribute('pub-id-type', 'doi');
        
        if (!empty($article['article_type'])) {
            $cat = $dom->createElement('article-categories');
            $sg = $dom->createElement('subj-group'); $sg->setAttribute('subj-group-type', 'heading');
            $sg->appendChild($dom->createElement('subject', htmlspecialchars($article['article_type'])));
            $cat->appendChild($sg); $am->appendChild($cat);
        }
        
        $tg = $dom->createElement('title-group');
        $tit = $dom->createElement('article-title'); $tit->appendChild($dom->createTextNode($article['title']));
        $tg->appendChild($tit);
        
        $titleEn = $article['title_en'] ?? ($article['english_title'] ?? '');
        if (!empty($titleEn)) {
            $ttg = $dom->createElement('trans-title-group'); $ttg->setAttribute('xml:lang', 'en');
            $ttg->appendChild($dom->createElement('trans-title', htmlspecialchars($titleEn)));
            $tg->appendChild($ttg);
        }
        $am->appendChild($tg);
        $am->appendChild($this->createContribGroup($dom, $authors, $affiliations));
        foreach ($affiliations as $aff) $am->appendChild($this->createAffiliation($dom, $aff));
        
        if (!empty($article['published_date'])) {
            $pd = $dom->createElement('pub-date'); $pd->setAttribute('pub-type', 'epub'); $pd->setAttribute('date-type', 'pub'); $pd->setAttribute('publication-format', 'electronic');
            $this->addDateElements($dom, $pd, $article['published_date']);
            $am->appendChild($pd);
        }
        
        if (!empty($article['volume_number'])) $am->appendChild($dom->createElement('volume', htmlspecialchars($article['volume_number'])));
        if (!empty($article['issue_number'])) $am->appendChild($dom->createElement('issue', htmlspecialchars($article['issue_number'])));
        $am->appendChild($dom->createElement('elocation-id', htmlspecialchars($article['pages'] ?? 'e' . $article['article_id'])));
        
        $per = $dom->createElement('permissions');
        $per->appendChild($dom->createElement('copyright-statement', '© ' . date('Y') . ' Autores'));
        $per->appendChild($dom->createElement('copyright-year', date('Y')));
        $lic = $dom->createElement('license'); $lic->setAttribute('license-type', 'open-access'); $lic->setAttribute('xlink:href', 'https://creativecommons.org/licenses/by/4.0/');
        $lic->appendChild($dom->createElement('ali:license_ref', 'https://creativecommons.org/licenses/by/4.0/'));
        $lic->appendChild($dom->createElement('license-p', 'Este es un artículo de acceso abierto bajo la licencia CC BY 4.0'));
        $per->appendChild($lic); $am->appendChild($per);
        
        if (!empty($article['abstract'])) { $ab = $dom->createElement('abstract'); $ab->appendChild($dom->createElement('p', htmlspecialchars($article['abstract']))); $am->appendChild($ab); }
        if (!empty($article['abstract_en'])) { $tab = $dom->createElement('trans-abstract'); $tab->setAttribute('xml:lang', 'en'); $tab->appendChild($dom->createElement('p', htmlspecialchars($article['abstract_en']))); $am->appendChild($tab); }
        
        if (!empty($article['keywords'])) {
            $kg = $dom->createElement('kwd-group'); $kg->setAttribute('xml:lang', 'es');
            foreach(explode(',', $article['keywords']) as $k) $kg->appendChild($dom->createElement('kwd', htmlspecialchars(trim($k))));
            $am->appendChild($kg);
        }
        if (!empty($article['keywords_en'])) {
            $kg = $dom->createElement('kwd-group'); $kg->setAttribute('xml:lang', 'en');
            foreach(explode(',', $article['keywords_en']) as $k) $kg->appendChild($dom->createElement('kwd', htmlspecialchars(trim($k))));
            $am->appendChild($kg);
        }
        return $am;
    }
    
    private function createContribGroup($dom, $authors, $affs) {
        $cg = $dom->createElement('contrib-group');
        foreach ($authors as $au) {
            $c = $dom->createElement('contrib'); $c->setAttribute('contrib-type', 'author');
            if (!empty($au['orcid'])) { $cid = $dom->createElement('contrib-id', 'https://orcid.org/' . $au['orcid']); $cid->setAttribute('contrib-id-type', 'orcid'); $c->appendChild($cid); }
            $name = $dom->createElement('name');
            $name->appendChild($dom->createElement('surname', htmlspecialchars($au['surname'] ?? '')));
            $name->appendChild($dom->createElement('given-names', htmlspecialchars($au['given_names'] ?? '')));
            $c->appendChild($name);
            $aid = !empty($au['affiliation_id']) ? $au['affiliation_id'] : (isset($affs[0]['affiliation_id']) ? $affs[0]['affiliation_id'] : 'aff1');
            $xr = $dom->createElement('xref'); $xr->setAttribute('ref-type', 'aff'); $xr->setAttribute('rid', $aid); $c->appendChild($xr);
            if (!empty($au['email'])) $c->appendChild($dom->createElement('email', $au['email']));
            $cg->appendChild($c);
        }
        return $cg;
    }
    
    private function createAffiliation($dom, $aff) {
        $a = $dom->createElement('aff'); $a->setAttribute('id', $aff['affiliation_id'] ?? 'aff1');
        if (!empty($aff['institution'])) $a->appendChild($dom->createElement('institution', htmlspecialchars($aff['institution'])));
        if (!empty($aff['country'])) $a->appendChild($dom->createElement('country', htmlspecialchars($aff['country'])));
        return $a;
    }
    
    private function createBody($dom, $sections, $tables, $figures) {
        $b = $dom->createElement('body');
        foreach ($sections as $sec) $b->appendChild($this->createSection($dom, $sec, $tables, $figures));
        return $b;
    }
    
    private function createSection($dom, $section, $tables, $figures) {
        $s = $dom->createElement('sec'); $s->setAttribute('id', $section['section_id'] ?? 'sec-'.uniqid());
        if (!empty($section['title'])) $s->appendChild($dom->createElement('title', htmlspecialchars($section['title'])));
        if (!empty($section['content'])) {
            $blocks = $this->extractParagraphsFromHtml($section['content']);
            foreach ($blocks as $block) {
                if (empty(trim($block))) continue;
                $clean = trim(strip_tags($block));
                if (preg_match('/^\[(Tabla|Figura|Table|Figure)\s*([^\]]+)\]$/i', $clean, $m)) {
                    if (stripos($m[1], 'Tab') !== false) $this->appendTableByLabel($dom, $s, trim($m[1] . ' ' . $m[2]), $tables);
                    else $this->appendFigureByLabel($dom, $s, trim($m[1] . ' ' . $m[2]), $figures);
                    continue;
                }
                $parts = preg_split('/(\[(?:Tabla|Figura|Table|Figure)\s*[^\]]+\])/i', $block, -1, PREG_SPLIT_DELIM_CAPTURE);
                $curP = null;
                foreach ($parts as $part) {
                    if ($part === '') continue;
                    $cp = trim(strip_tags($part));
                    if (preg_match('/^\[(Tabla|Table)\s*([^\]]+)\]$/i', $cp, $m)) {
                        if ($curP) { $s->appendChild($curP); $curP = null; }
                        $this->appendTableByLabel($dom, $s, trim($m[1] . ' ' . $m[2]), $tables);
                    } elseif (preg_match('/^\[(Figura|Figure)\s*([^\]]+)\]$/i', $cp, $m)) {
                        if ($curP) { $s->appendChild($curP); $curP = null; }
                        $this->appendFigureByLabel($dom, $s, trim($m[1] . ' ' . $m[2]), $figures);
                    } else {
                        if (!$curP) $curP = $dom->createElement('p');
                        $txt = $this->htmlToXmlFragment($part);
                        if (trim($txt) !== '') {
                            $f = $dom->createDocumentFragment();
                            if (@$f->appendXML($txt)) $curP->appendChild($f);
                            else $curP->appendChild($dom->createTextNode(strip_tags($txt)));
                        }
                    }
                }
                if ($curP) $s->appendChild($curP);
            }
        }
        return $s;
    }

    private function extractParagraphsFromHtml(string $html): array {
        $html = str_replace(["\r\n", "\r", "\n"], " ", $html); $break = '||PARBREAK||';
        $html = preg_replace('/<\/(p|div|li|h[1-6]|blockquote)>/i', $break, $html);
        $html = preg_replace('/(<br\s*\/?>\s*){2,}/i', $break, $html);
        $html = preg_replace('/<(p|div|ul|ol|li|h[1-6]|blockquote)[^>]*>/i', '', $html);
        $chunks = explode($break, $html); $res = [];
        foreach ($chunks as $c) { $c = trim(preg_replace('/\s+/', ' ', $c)); if ($c !== '') $res[] = $c; }
        return $res;
    }

    private function htmlToXmlFragment(string $h): string {
        $m = ['&nbsp;'=>'&#160;', '&ndash;'=>'–', '&mdash;'=>'—', '&ldquo;'=>'"', '&rdquo;'=>'"'];
        foreach ($m as $e => $c) $h = str_replace($e, $c, $h);
        $h = html_entity_decode($h, ENT_HTML5 | ENT_QUOTES, 'UTF-8');
        return preg_replace('/&(?!(?:[a-zA-Z]+|#\d+|#x[0-9a-fA-F]+);)/', '&amp;', $h);
    }

    private function tableHtmlToXml(string $h): string {
        $h = preg_replace('/\s+(style|class|id)="[^"]*"/i', '', $h);
        libxml_use_internal_errors(true); $tD = new DOMDocument('1.0', 'UTF-8');
        $tD->loadHTML('<?xml encoding="UTF-8">' . $h, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors(); $tags = $tD->getElementsByTagName('table');
        return $tags->length > 0 ? $tD->saveXML($tags->item(0)) : '';
    }

    private function appendTableByLabel($dom, $p, $l, $ts) {
        $d = null; foreach ($ts as $t) if (strcasecmp(trim($t['label'] ?? ''), $l) === 0) { $d=$t; break; }
        if (!$d) return; $tw = $dom->createElement('table-wrap'); $tw->setAttribute('id', 't-'.uniqid()); $p->appendChild($tw);
        $tw->appendChild($dom->createElement('label', htmlspecialchars($d['label'] ?? 'Tabla')));
        $cap = $dom->createElement('caption'); $cap->appendChild($dom->createElement('p', htmlspecialchars($d['caption'] ?? ($d['title'] ?? '')))); $tw->appendChild($cap);
        if (!empty($d['src']) && ($d['type'] ?? '') === 'image') {
            $g = $dom->createElement('graphic'); $g->setAttribute('xlink:href', htmlspecialchars($d['src'])); $tw->appendChild($g);
        } elseif (!empty($d['html'])) {
            $f = $dom->createDocumentFragment(); $xml = $this->tableHtmlToXml($d['html']);
            if ($xml && @$f->appendXML($xml)) $tw->appendChild($f); else $tw->appendChild($dom->createElement('table'));
        }
    }

    private function appendFigureByLabel($dom, $p, $l, $fs) {
        $d = null; foreach ($fs as $f) if (strcasecmp(trim($f['label'] ?? ''), $l) === 0) { $d=$f; break; }
        if (!$d) return; $fig = $dom->createElement('fig'); $fig->setAttribute('id', 'f-'.uniqid()); $p->appendChild($fig);
        $fig->appendChild($dom->createElement('label', htmlspecialchars($d['label'] ?? 'Figura')));
        $cap = $dom->createElement('caption'); $cap->appendChild($dom->createElement('p', htmlspecialchars($d['alt'] ?? ($d['caption'] ?? '')))); $fig->appendChild($cap);
        $g = $dom->createElement('graphic'); $g->setAttribute('xlink:href', htmlspecialchars($d['src'] ?? '')); $fig->appendChild($g);
    }

    private function createBack($dom, $refs) {
        $b = $dom->createElement('back');
        if (!empty($refs)) { $rl = $dom->createElement('ref-list'); $rl->appendChild($dom->createElement('title', 'Referencias')); foreach ($refs as $r) $rl->appendChild($this->createReference($dom, $r)); $b->appendChild($rl); }
        return $b;
    }

    private function createReference($dom, $ref) {
        $rEl = $dom->createElement('ref'); $rEl->setAttribute('id', $ref['ref_id'] ?? 'ref-'.uniqid());
        $cit = $dom->createElement('element-citation'); $cit->setAttribute('publication-type', $ref['reference_type'] ?? 'journal');
        if (!empty($ref['authors'])) { $pg = $dom->createElement('person-group'); $pg->setAttribute('person-group-type', 'author'); $cit->appendChild($pg); }
        if (!empty($ref['year'])) $cit->appendChild($dom->createElement('year', $ref['year']));
        if (!empty($ref['title'])) $cit->appendChild($dom->createElement('article-title', htmlspecialchars($ref['title'])));
        $rEl->appendChild($cit);
        $mix = $dom->createElement('mixed-citation'); $txt = ($ref['authors'] ?? "") . ". (" . ($ref['year'] ?? "") . "). " . ($ref['title'] ?? "");
        $mix->appendChild($dom->createTextNode(trim($txt))); $rEl->appendChild($mix);
        return $rEl;
    }

    private function addDateElements($dom, $parentElement, $dateString) {
        try { $date = new DateTime($dateString); $parentElement->appendChild($dom->createElement('day', $date->format('d'))); $parentElement->appendChild($dom->createElement('month', $date->format('m'))); $parentElement->appendChild($dom->createElement('year', $date->format('Y'))); } catch (Exception $e) {}
    }

    private function getJournalInfo($article) {
        $sql = "SELECT j.* FROM journals j LEFT JOIN volumes v ON j.id = v.journal_id LEFT JOIN issues i ON v.id = i.volume_id WHERE i.id = :issue_id";
        $journal = $this->db->fetchOne($sql, ['issue_id' => $article['issue_id']]);
        if (!$journal) $journal = $this->db->fetchOne("SELECT * FROM journals WHERE active = TRUE LIMIT 1");
        return $journal ?: [];
    }
}
