<?php
/**
 * Clase RedalycGenerator - Generación de XML-JATS con estándares de Redalyc (JATS4R)
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
    
    public function generateXML($articleId) {
        $article = $this->articleModel->getById($articleId);
        if (!$article) throw new Exception("Artículo no encontrado");
        
        $authors = $this->articleModel->getAuthors($articleId);
        $affiliations = $this->articleModel->getAffiliations($articleId);
        $sections = $this->articleModel->getSections($articleId);
        $tables = $this->articleModel->getTables($articleId);
        $figures = $this->articleModel->getFigures($articleId);
        $references = $this->articleModel->getReferences($articleId);
        
        $journal = $this->getJournalInfo($article);
        $markup = $this->articleModel->getMarkup($articleId);
        $md = ($markup && isset($markup['markup_data'])) ? $markup['markup_data'] : [];
        $markupSections = $md['sections'] ?? [];

        if (empty($sections) && !empty($markupSections)) {
            $sections = array_map(function($s, $i) {
                return ['section_id'=>'sec-'.($i+1), 'section_type'=>$s['type']??'other', 'title'=>$s['type_name']??($s['title']??''), 'content'=>$s['content']??'', 'level'=>$s['level']??1, 'section_order'=>$i+1];
            }, $markupSections, array_keys($markupSections));
        }

        // Carga de referencias con fallback a markup_data
        if (empty($references) && !empty($md['references'])) $references = $md['references'];
        
        // Carga de notas al pie con fallback a markup_data
        $footnotes = $this->articleModel->getFootnotes($articleId);
        if (empty($footnotes) && !empty($md['footnotes'])) $footnotes = $md['footnotes'];

        // Si no hay afiliaciones en BD, construirlas desde los autores (incluyendo country)
        if (empty($affiliations) && !empty($authors)) {
            $temp = [];
            foreach ($authors as &$au) {
                if (!empty($au['affiliation']) && empty($au['affiliation_id'])) {
                    $id = 'aff-' . crc32($au['affiliation']);
                    $au['affiliation_id'] = $id;
                    $temp[$id] = [
                        'affiliation_id' => $id,
                        'institution'    => $au['affiliation'],
                        'country'        => $au['country'] ?? '',
                    ];
                }
            }
            if (!empty($temp)) $affiliations = array_values($temp);
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        
        $impl = new DOMImplementation();
        $dtd = $impl->createDocumentType('article', '-//NLM//DTD JATS (Z39.96) Journal Publishing DTD v1.1 20151215//EN', 'http://jats.nlm.nih.gov/publishing/1.1/JATS-journalpublishing1.dtd');
        $dom = $impl->createDocument('', '', $dtd);
        $dom->encoding = 'UTF-8';
        $dom->formatOutput = true;
        
        $articleEl = $dom->createElement('article');
        $articleEl->setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
        $articleEl->setAttribute('xmlns:mml', 'http://www.w3.org/1998/Math/MathML');
        $articleEl->setAttribute('xmlns:ali', 'http://www.niso.org/schemas/ali/1.0/'); // JATS4R requirement for ali:license_ref
        $articleEl->setAttribute('dtd-version', '1.1');
        $articleEl->setAttribute('article-type', 'research-article');
        $articleEl->setAttribute('xml:lang', 'es');
        $dom->appendChild($articleEl);
        
        $front = $dom->createElement('front');
        $articleEl->appendChild($front);
        $front->appendChild($this->createJournalMeta($dom, $journal));
        $front->appendChild($this->createArticleMeta($dom, $article, $authors, $affiliations));
        
        $articleEl->appendChild($this->createBody($dom, $sections));
        $articleEl->appendChild($this->createBack($dom, $references, $footnotes));
        
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
        $jm->appendChild($jtg);
        if (!empty($jl['issn_print'])) $jm->appendChild($dom->createElement('issn', $jl['issn_print']))->setAttribute('pub-type', 'ppub');
        $jm->appendChild($dom->createElement('publisher'))->appendChild($dom->createElement('publisher-name', htmlspecialchars($jl['publisher'] ?? '')));
        return $jm;
    }
    
    private function createArticleMeta($dom, $article, $authors, $affiliations) {
        $am = $dom->createElement('article-meta');
        if (!empty($article['doi'])) {
            $cleanDoi = preg_replace('/^https?:\/\/(dx\.)?doi\.org\//i', '', $article['doi']);
            $am->appendChild($dom->createElement('article-id', htmlspecialchars($cleanDoi)))->setAttribute('pub-id-type', 'doi');
        }
        
        $tg = $dom->createElement('title-group');
        $tg->appendChild($dom->createElement('article-title', htmlspecialchars($article['title'])));
        $am->appendChild($tg);
        $am->appendChild($this->createContribGroup($dom, $authors, $affiliations));
        
        if (empty($affiliations)) {
            $am->appendChild($this->createAffiliation($dom, ['affiliation_id' => 'aff1', 'institution' => $article['publisher'] ?? 'UNAP', 'country' => 'Perú']));
        } else {
            foreach ($affiliations as $aff) $am->appendChild($this->createAffiliation($dom, $aff));
        }
        
        if (!empty($article['published_date'])) {
            $pd = $dom->createElement('pub-date'); $pd->setAttribute('pub-type', 'epub'); $pd->setAttribute('date-type', 'pub'); $pd->setAttribute('publication-format', 'electronic');
            $this->addDateElements($dom, $pd, $article['published_date']); $am->appendChild($pd);
        }
        if (!empty($article['volume_number'])) $am->appendChild($dom->createElement('volume', htmlspecialchars($article['volume_number'])));
        if (!empty($article['issue_number'])) $am->appendChild($dom->createElement('issue', htmlspecialchars($article['issue_number'])));
        if (!empty($article['pages'])) $am->appendChild($dom->createElement('fpage', htmlspecialchars($article['pages'])));
        
        // JATS4R: Required permissions element
        $per = $dom->createElement('permissions');
        $per->appendChild($dom->createElement('copyright-statement', '© ' . date('Y') . ' Autores (Authors)'));
        $per->appendChild($dom->createElement('copyright-year', date('Y')));
        $per->appendChild($dom->createElement('copyright-holder', 'Autores (Authors)')); // Added copyright-holder
        
        $licenseUrl = 'https://creativecommons.org/licenses/by/4.0/'; // Added https and trailing slash
        $lic = $dom->createElement('license'); 
        $lic->setAttribute('license-type', 'open-access'); 
        $lic->setAttribute('xlink:href', $licenseUrl); 
        $lic->setAttribute('xml:lang', 'es');
        
        // ali:license_ref child (JATS4R/JATS 1.1 requirement)
        $licRef = $dom->createElement('ali:license_ref', $licenseUrl);
        $lic->appendChild($licRef);
        
        $lic->appendChild($dom->createElement('license-p', 'Este es un artículo de acceso abierto bajo la licencia CC BY 4.0'));
        $per->appendChild($lic); $am->appendChild($per);

        if (!empty($article['abstract'])) { $ab = $dom->createElement('abstract'); $ab->appendChild($dom->createElement('p', htmlspecialchars($article['abstract']))); $am->appendChild($ab); }
        if (!empty($article['keywords'])) { $kg=$dom->createElement('kwd-group'); foreach(explode(',',$article['keywords']) as $k) $kg->appendChild($dom->createElement('kwd',htmlspecialchars(trim($k)))); $am->appendChild($kg); }
        return $am;
    }
    
    private function createContribGroup($dom, $authors, $affs) {
        $cg = $dom->createElement('contrib-group');
        foreach ($authors as $au) {
            $c = $dom->createElement('contrib'); $c->setAttribute('contrib-type', 'author');
            if (!empty($au['orcid'])) {
                $orcidClean = preg_replace('/^(https?:\/\/)?(www\.)?orcid\.org\//i', '', $au['orcid']);
                $cid = $dom->createElement('contrib-id', 'https://orcid.org/' . $orcidClean);
                $cid->setAttribute('contrib-id-type', 'orcid');
                $c->appendChild($cid);
            }
            $name = $dom->createElement('name'); $name->appendChild($dom->createElement('surname', htmlspecialchars($au['surname'] ?? ''))); $name->appendChild($dom->createElement('given-names', htmlspecialchars($au['given_names'] ?? ''))); $c->appendChild($name);
            $cg->appendChild($c);
        }
        return $cg;
    }
    
    private static function countryIso($name) {
        $map = [
            'Perú'                => 'PE', 'Peru'               => 'PE',
            'Argentina'           => 'AR', 'Bolivia'            => 'BO',
            'Brasil'              => 'BR', 'Brazil'             => 'BR',
            'Chile'               => 'CL', 'Colombia'           => 'CO',
            'Costa Rica'          => 'CR', 'Cuba'               => 'CU',
            'Ecuador'             => 'EC', 'El Salvador'        => 'SV',
            'España'              => 'ES', 'Spain'              => 'ES',
            'Guatemala'           => 'GT', 'Honduras'           => 'HN',
            'México'              => 'MX', 'Mexico'             => 'MX',
            'Nicaragua'           => 'NI', 'Panamá'             => 'PA',
            'Paraguay'            => 'PY', 'República Dominicana' => 'DO',
            'Uruguay'             => 'UY', 'Venezuela'          => 'VE',
            'Estados Unidos'      => 'US', 'United States'      => 'US',
            'Reino Unido'         => 'GB', 'United Kingdom'     => 'GB',
            'Alemania'            => 'DE', 'Germany'            => 'DE',
            'Francia'             => 'FR', 'France'             => 'FR',
            'Italia'              => 'IT', 'Italy'              => 'IT',
            'Portugal'            => 'PT', 'Canadá'             => 'CA',
            'Canada'              => 'CA', 'China'              => 'CN',
        ];
        return $map[trim($name)] ?? 'XX';
    }

    private function createAffiliation($dom, $aff) {
        $a = $dom->createElement('aff'); $a->setAttribute('id', $aff['affiliation_id'] ?? 'aff1');
        $a->appendChild($dom->createElement('institution', htmlspecialchars($aff['institution'] ?? '')))->setAttribute('content-type', 'original');
        $countryName = !empty($aff['country']) ? $aff['country'] : 'Perú';
        $c = $dom->createElement('country', htmlspecialchars($countryName));
        $c->setAttribute('country', self::countryIso($countryName));
        $a->appendChild($c);
        return $a;
    }
    
    private function createBody($dom, $sections) {
        $b = $dom->createElement('body');
        foreach ($sections as $s) $b->appendChild($this->createSection($dom, $s));
        return $b;
    }
    
    private function createSection($dom, $section) {
        $s = $dom->createElement('sec'); $s->setAttribute('id', $section['section_id'] ?? 'sec-'.uniqid());
        if (!empty($section['title'])) $s->appendChild($dom->createElement('title', htmlspecialchars($section['title'])));
        if (!empty($section['content'])) {
            $blocks = $this->extractParagraphsFromHtml($section['content']);
            foreach ($blocks as $block) {
                if (empty(trim($block))) continue;
                $p = $dom->createElement('p');
                $txt = $this->htmlToXmlFragment($block);
                $f = $dom->createDocumentFragment();
                if (@$f->appendXML($txt)) $p->appendChild($f); else $p->appendChild($dom->createTextNode(strip_tags($txt)));
                $s->appendChild($p);
            }
        }
        return $s;
    }

    private function extractParagraphsFromHtml(string $h): array {
        $h = str_replace(["\r\n", "\r", "\n"], " ", $h);
        $h = preg_replace('/<\/(p|div|li|h[1-6]|blockquote)>/i', '||PARBREAK||', $h);
        $h = preg_replace('/(<br\s*\/?>\s*){2,}/i', '||PARBREAK||', $h);
        $h = preg_replace('/<(p|div|ul|ol|li|h[1-6]|blockquote)[^>]*>/i', '', $h);
        $chunks = explode('||PARBREAK||', $h); $res = [];
        foreach ($chunks as $c) { $c = trim(preg_replace('/\s+/', ' ', $c)); if ($c !== '') $res[] = $c; }
        return $res;
    }

    private function htmlToXmlFragment(string $h): string {
        $h = preg_replace('/<b\s*[^>]*>(.*?)<\/b>/is', '<bold>$1</bold>', $h);
        $h = preg_replace('/<strong\s*[^>]*>(.*?)<\/strong>/is', '<bold>$1</bold>', $h);
        $h = preg_replace('/<i\s*[^>]*>(.*?)<\/i>/is', '<italic>$1</italic>', $h);
        $h = preg_replace('/<em\s*[^>]*>(.*?)<\/em>/is', '<italic>$1</italic>', $h);
        $h = preg_replace('/<u\s*[^>]*>(.*?)<\/u>/is', '<underline>$1</underline>', $h);
        
        $h = preg_replace_callback('/<a\s+[^>]*href=[\'"]([^\'"]+)[\'"][^>]*>(.*?)<\/a>/is', function($m) {
            $href = $m[1]; $text = $m[2];
            if (strpos($href, '#') === 0) {
                $rid = substr($href, 1); $type = 'other';
                if (strpos($rid, 'ref') !== false) {
                    $type = 'bibr';
                } elseif (strpos($rid, 'fn') !== false || strpos($rid, 'ftn') !== false) {
                    $type = 'fn';
                    if (preg_match('/^(?:_?ftn|fn)-?(\d+)$/i', $rid, $map)) $rid = 'fn-' . $map[1];
                }
                return "<xref ref-type=\"$type\" rid=\"$rid\">$text</xref>";
            }
            return "<ext-link ext-link-type=\"uri\" xlink:href=\"$href\">$text</ext-link>";
        }, $h);

        $h = preg_replace('/&(?!(?:[a-zA-Z]+|#\d+|#x[0-9a-fA-F]+);)/', '&amp;', $h);
        $h = preg_replace('/\s+(style|class|align|lang|xml:lang|data-[a-z0-9\-]+)="[^"]*"/i', '', $h);
        return strip_tags($h, '<bold><italic><underline><sub><sup><xref><ext-link>');
    }

    private function createBack($dom, $references, $footnotes = []) {
        $back = $dom->createElement('back');
        if (!empty($footnotes)) {
            $fnGroup = $dom->createElement('fn-group');
            foreach ($footnotes as $i => $fn) {
                $fid = $fn['fn_id'] ?? ($fn['id'] ?? ($i + 1));
                if (is_string($fid) && strpos($fid, '#') === 0) $fid = substr($fid, 1);
                if (preg_match('/^(?:_?ftn|fn)-?(\d+)$/i', $fid, $m)) $fid = 'fn-' . $m[1];
                if (preg_match('/^\d/', $fid)) $fid = 'fn-' . $fid;
                
                $fnEl = $dom->createElement('fn');
                $fnEl->setAttribute('id', $fid);
                $fnEl->setAttribute('fn-type', 'other');
                
                $content = $fn['text'] ?? ($fn['content'] ?? '');
                $fnEl->appendChild($dom->createElement('p', htmlspecialchars($content)));
                $fnGroup->appendChild($fnEl);
            }
            $back->appendChild($fnGroup);
        }
        if (!empty($references)) {
            $refList = $dom->createElement('ref-list'); $refList->appendChild($dom->createElement('title', 'Referencias'));
            foreach ($references as $i => $ref) {
                if (empty($ref['ref_id']) || !preg_match('/^ref-\d+$/i', $ref['ref_id'])) $ref['ref_id'] = 'ref-' . ($i + 1);
                $refList->appendChild($this->createReference($dom, $ref));
            }
            $back->appendChild($refList);
        }
        return $back;
    }

    private function createReference($dom, $ref) {
        $rEl = $dom->createElement('ref'); $rEl->setAttribute('id', $ref['ref_id']);
        $type = $ref['reference_type'] ?? 'journal';
        $cit = $dom->createElement('element-citation'); $cit->setAttribute('publication-type', $type);
        if (!empty($ref['authors'])) { 
            $pg = $dom->createElement('person-group'); $pg->setAttribute('person-group-type', 'author'); 
            foreach (explode(';', $ref['authors']) as $author) {
                $name = $dom->createElement('name');
                $parts = explode(',', trim($author));
                if (count($parts) > 1) { $name->appendChild($dom->createElement('surname', trim($parts[0]))); $name->appendChild($dom->createElement('given-names', trim($parts[1]))); }
                else { $name->appendChild($dom->createElement('surname', trim($author))); }
                $pg->appendChild($name);
            }
            $cit->appendChild($pg); 
        }
        if (!empty($ref['year'])) $cit->appendChild($dom->createElement('year', $ref['year']));
        if (!empty($ref['title'])) $cit->appendChild($dom->createElement('article-title', htmlspecialchars($ref['title'])));
        if (!empty($ref['source'])) $cit->appendChild($dom->createElement('source', htmlspecialchars($ref['source'])));
        if (!empty($ref['pages'])) $cit->appendChild($dom->createElement('fpage', htmlspecialchars($ref['pages'])));
        if (!empty($ref['doi'])) { $cleanRefDoi = preg_replace('/^https?:\/\/(dx\.)?doi\.org\//i', '', $ref['doi']); $doi=$dom->createElement('pub-id', htmlspecialchars($cleanRefDoi)); $doi->setAttribute('pub-id-type', 'doi'); $cit->appendChild($doi); }
        if (!empty($ref['url'])) { $ext=$dom->createElement('ext-link', htmlspecialchars($ref['url'])); $ext->setAttribute('ext-link-type', 'uri'); $ext->setAttribute('xlink:href', $ref['url']); $cit->appendChild($ext); }
        $rEl->appendChild($cit);
        
        $mixed = $dom->createElement('mixed-citation');
        $mixed->setAttribute('publication-type', $type);
        if (!empty($ref['authors'])) {
             $pg2 = $dom->createElement('person-group'); $pg2->setAttribute('person-group-type', 'author');
             foreach (explode(';', $ref['authors']) as $author) {
                $name2 = $dom->createElement('name'); $parts2 = explode(',', trim($author));
                if (count($parts2) > 1) { $name2->appendChild($dom->createElement('surname', trim($parts2[0]))); $name2->appendChild($dom->createElement('given-names', trim($parts2[1]))); }
                else { $name2->appendChild($dom->createElement('surname', trim($author))); }
                $pg2->appendChild($name2);
             }
             $mixed->appendChild($pg2);
             $mixed->appendChild($dom->createTextNode(' '));
        }
        $full = '(' . ($ref['year'] ?? '') . '). ' . ($ref['title'] ?? '') . '. ' . ($ref['source'] ?? '');
        if (!empty($ref['pages'])) $full .= ' pp. ' . $ref['pages'];
        if (!empty($ref['doi'])) {
            $cleanDoi = preg_replace('/^https?:\/\/(dx\.)?doi\.org\//i', '', $ref['doi']);
            $full .= '. https://doi.org/' . $cleanDoi;
        } elseif (!empty($ref['url'])) {
            $full .= '. ' . $ref['url'];
        }
        $mixed->appendChild($dom->createTextNode(trim($full, ' .'))); $rEl->appendChild($mixed);
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
