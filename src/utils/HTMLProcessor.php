<?php
/**
 * Clase HTMLProcessor - Procesamiento y reconocimiento automático de elementos del artículo
 */

class HTMLProcessor {
    private $html;
    private $dom;
    private $xpath;
    
    public function __construct($htmlContent) {
        $this->html = $htmlContent;
        $this->initDom();
    }
    
    private function initDom() {
        $this->dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $this->dom->loadHTML(mb_convert_encoding($this->html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();
        $this->xpath = new DOMXPath($this->dom);
    }
    
    /**
     * Procesar documento completo y extraer todos los elementos
     */
    public function processDocument() {
        return [
            'metadata' => $this->extractMetadata(),
            'authors' => $this->extractAuthors(),
            'affiliations' => $this->extractAffiliations(),
            'abstract' => $this->extractAbstract(),
            'keywords' => $this->extractKeywords(),
            'sections' => $this->extractSections(),
            'tables' => $this->extractTables(),
            'figures' => $this->extractFigures(),
            'references' => $this->extractReferences(),
            'citations' => $this->extractCitations(),
        ];
    }
    
    /**
     * Extraer metadatos básicos (título, fechas)
     */
    private function extractMetadata() {
        $metadata = [];
        
        // Intentar extraer título
        $titlePatterns = [
            '//h1[1]',
            '//p[contains(@class, "title")]',
            '//div[contains(@class, "title")]//text()',
        ];
        
        foreach ($titlePatterns as $pattern) {
            $nodes = $this->xpath->query($pattern);
            if ($nodes->length > 0) {
                $titleText = trim($nodes->item(0)->textContent);
                // El título en español suele ser el primero
                if (!isset($metadata['title']) && !$this->isEnglish($titleText)) {
                    $metadata['title'] = $titleText;
                } elseif (!isset($metadata['title_en']) && $this->isEnglish($titleText)) {
                    $metadata['title_en'] = $titleText;
                }
            }
        }
        
        // Buscar fechas en el texto
        $textContent = $this->dom->textContent;
        $datePatterns = [
            '/Recibido.*?(\d{4}\/\d{2}\/\d{2})/i',
            '/Received.*?(\d{4}\/\d{2}\/\d{2})/i',
            '/Aceptado.*?(\d{4}\/\d{2}\/\d{2})/i',
            '/Accepted.*?(\d{4}\/\d{2}\/\d{2})/i',
            '/Publicado.*?(\d{4}\/\d{2}\/\d{2})/i',
            '/Published.*?(\d{4}\/\d{2}\/\d{2})/i',
        ];
        
        foreach ($datePatterns as $pattern) {
            if (preg_match($pattern, $textContent, $matches)) {
                $dateStr = str_replace('/', '-', $matches[1]);
                if (stripos($pattern, 'recib') !== false || stripos($pattern, 'received') !== false) {
                    $metadata['received_date'] = $dateStr;
                } elseif (stripos($pattern, 'acept') !== false || stripos($pattern, 'accepted') !== false) {
                    $metadata['accepted_date'] = $dateStr;
                } elseif (stripos($pattern, 'public') !== false || stripos($pattern, 'published') !== false) {
                    $metadata['published_date'] = $dateStr;
                }
            }
        }
        
        return $metadata;
    }
    
    /**
     * Extraer autores con patrones comunes
     */
    private function extractAuthors() {
        $authors = [];
        $authorOrder = 1;
        
        // Buscar patrones comunes de autores
        $authorNodes = $this->xpath->query('//p[contains(text(), "Universidad") or contains(text(), "University") or contains(@href, "orcid.org")]');
        
        $currentAuthor = null;
        foreach ($authorNodes as $node) {
            $text = trim($node->textContent);
            
            // Detectar nombre de autor (sin punto, sin números, letras mayúsculas)
            if (preg_match('/^([A-ZÁÉÍÓÚÑ][a-záéíóúñ]+(?:\s+[A-ZÁÉÍÓÚÑ][a-záéíóúñ]+)+)$/u', $text)) {
                if ($currentAuthor) {
                    $authors[] = $currentAuthor;
                }
                $currentAuthor = [
                    'given_names' => '',
                    'surname' => '',
                    'full_name' => $text,
                    'author_order' => $authorOrder++,
                ];
                
                // Separar nombre y apellido
                $parts = explode(' ', $text);
                if (count($parts) >= 2) {
                    $currentAuthor['surname'] = array_pop($parts);
                    $currentAuthor['given_names'] = implode(' ', $parts);
                }
            }
            
            // Detectar afiliación
            if ($currentAuthor && (stripos($text, 'universidad') !== false || stripos($text, 'university') !== false)) {
                $currentAuthor['affiliation'] = $text;
            }
            
            // Detectar ORCID
            if ($currentAuthor && stripos($text, 'orcid') !== false) {
                if (preg_match('/\d{4}-\d{4}-\d{4}-\d{4}/', $text, $matches)) {
                    $currentAuthor['orcid'] = $matches[0];
                }
            }
            
            // Detectar email
            if ($currentAuthor && filter_var($text, FILTER_VALIDATE_EMAIL)) {
                $currentAuthor['email'] = $text;
            }
        }
        
        if ($currentAuthor) {
            $authors[] = $currentAuthor;
        }
        
        return $authors;
    }
    
    /**
     * Extraer afiliaciones
     */
    private function extractAffiliations() {
        $affiliations = [];
        $affId = 1;
        
        $affNodes = $this->xpath->query('//p[contains(text(), "Universidad") or contains(text(), "University")]');
        
        foreach ($affNodes as $node) {
            $text = trim($node->textContent);
            
            // Extraer país
            $country = '';
            if (preg_match('/,\s*([A-Z][a-záéíóúñ]+)\s*\.?$/u', $text, $matches)) {
                $country = $matches[1];
            }
            
            $affiliations[] = [
                'affiliation_id' => 'aff' . $affId++,
                'institution' => $text,
                'country' => $country,
            ];
        }
        
        return $affiliations;
    }
    
    /**
     * Extraer resumen/abstract
     */
    private function extractAbstract() {
        $abstracts = ['es' => '', 'en' => ''];
        
        // Buscar "Resumen:" o "Abstract:"
        $text = $this->dom->textContent;
        
        if (preg_match('/Resumen:\s*(.+?)(?=Palabras|Abstract|Introducci[oó]n|\d+\.)/is', $text, $matches)) {
            $abstracts['es'] = trim($matches[1]);
        }
        
        if (preg_match('/Abstract:\s*(.+?)(?=Keywords|Resumen|Introduction|\d+\.)/is', $text, $matches)) {
            $abstracts['en'] = trim($matches[1]);
        }
        
        return $abstracts;
    }
    
    /**
     * Extraer palabras clave
     */
    private function extractKeywords() {
        $keywords = ['es' => [], 'en' => []];
        
        $text = $this->dom->textContent;
        
        if (preg_match('/Palabras\s+claves?:\s*(.+?)(?=Abstract|Keywords|\d+\.)/is', $text, $matches)) {
            $keywords['es'] = array_map('trim', explode(',', $matches[1]));
        }
        
        if (preg_match('/Keywords:\s*(.+?)(?=Resumen|Palabras|\d+\.)/is', $text, $matches)) {
            $keywords['en'] = array_map('trim', explode(',', $matches[1]));
        }
        
        return $keywords;
    }
    
    /**
     * Extraer secciones del documento
     */
    private function extractSections() {
        $sections = [];
        $sectionOrder = 1;
        
        // Buscar encabezados (h1, h2, h3, strong con números)
        $headings = $this->xpath->query('//h1 | //h2 | //h3 | //strong[contains(text(), ".")]');
        
        $currentSection = null;
        $contentBuffer = '';
        
        foreach ($headings as $heading) {
            $headingText = trim($heading->textContent);
            
            // Detectar si es un título de sección (contiene número o palabras clave)
            if (preg_match('/^(\d+\.?\d*)\s+(.+)/', $headingText, $matches) || 
                in_array(strtolower($headingText), ['introducción', 'introduction', 'metodología', 'methodology', 'resultados', 'results', 'discusión', 'discussion', 'conclusiones', 'conclusions'])) {
                
                if ($currentSection) {
                    $currentSection['content'] = $contentBuffer;
                    $sections[] = $currentSection;
                    $contentBuffer = '';
                }
                
                $level = 1;
                if ($heading->nodeName === 'h2') $level = 2;
                elseif ($heading->nodeName === 'h3') $level = 3;
                
                $currentSection = [
                    'section_id' => 's' . $sectionOrder,
                    'title' => isset($matches[2]) ? $matches[2] : $headingText,
                    'section_order' => $sectionOrder++,
                    'level' => $level,
                ];
            } else {
                // Agregar al contenido de la sección actual
                $node = $heading;
                while ($node = $node->nextSibling) {
                    if ($node->nodeType === XML_ELEMENT_NODE && 
                        in_array($node->nodeName, ['h1', 'h2', 'h3'])) {
                        break;
                    }
                    if ($node->nodeType === XML_TEXT_NODE || $node->nodeName === 'p') {
                        $contentBuffer .= trim($node->textContent) . "\n\n";
                    }
                }
            }
        }
        
        if ($currentSection) {
            $currentSection['content'] = $contentBuffer;
            $sections[] = $currentSection;
        }
        
        return $sections;
    }
    
    /**
     * Extraer tablas
     */
    private function extractTables() {
        $tables = [];
        $tableOrder = 1;
        
        $tableNodes = $this->xpath->query('//table');
        
        foreach ($tableNodes as $table) {
            // Buscar caption anterior o posterior
            $caption = '';
            $prevNode = $table->previousSibling;
            while ($prevNode && $prevNode->nodeType !== XML_ELEMENT_NODE) {
                $prevNode = $prevNode->previousSibling;
            }
            if ($prevNode && stripos($prevNode->textContent, 'tabla') !== false) {
                $caption = trim($prevNode->textContent);
            }
            
            // Guardar HTML de la tabla
            $tableHTML = $this->dom->saveHTML($table);
            
            $tables[] = [
                'table_id' => 't' . $tableOrder,
                'label' => 'Tabla ' . $tableOrder,
                'caption' => $caption,
                'html_content' => $tableHTML,
                'table_order' => $tableOrder++,
            ];
        }
        
        return $tables;
    }
    
    /**
     * Extraer figuras/imágenes
     */
    private function extractFigures() {
        $figures = [];
        $figureOrder = 1;
        
        $imgNodes = $this->xpath->query('//img');
        
        foreach ($imgNodes as $img) {
            $src = $img->getAttribute('src');
            $alt = $img->getAttribute('alt');
            
            // Buscar caption
            $caption = $alt;
            $parentNode = $img->parentNode;
            if ($parentNode && $parentNode->nodeName === 'figure') {
                $figcaption = $parentNode->getElementsByTagName('figcaption');
                if ($figcaption->length > 0) {
                    $caption = trim($figcaption->item(0)->textContent);
                }
            }
            
            $figures[] = [
                'figure_id' => 'fig' . $figureOrder,
                'label' => 'Figura ' . $figureOrder,
                'caption' => $caption,
                'file_path' => $src,
                'figure_order' => $figureOrder++,
            ];
        }
        
        return $figures;
    }
    
    /**
     * Extraer referencias bibliográficas (formato APA)
     */
    private function extractReferences() {
        $references = [];
        $refOrder = 1;
        
        $text = $this->dom->textContent;
        
        // Buscar sección de referencias
        if (preg_match('/Referencias?|Bibliography/i', $text, $matches, PREG_OFFSET_CAPTURE)) {
            $startPos = $matches[0][1];
            $referencesText = substr($text, $startPos);
            
            // Dividir por líneas/párrafos
            $lines = preg_split('/\n{2,}/', $referencesText);
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || stripos($line, 'referencias') !== false) continue;
                
                // Parsear formato APA básico
                $reference = $this->parseAPAReference($line);
                if ($reference) {
                    $reference['ref_id'] = 'ref' . $refOrder;
                    $reference['reference_order'] = $refOrder++;
                    $reference['full_citation'] = $line;
                    $references[] = $reference;
                }
            }
        }
        
        return $references;
    }
    
    /**
     * Parsear referencia en formato APA
     */
    private function parseAPAReference($text) {
        $reference = ['reference_type' => 'journal'];
        
        // Extraer año (YYYY)
        if (preg_match('/\((\d{4})\)/', $text, $matches)) {
            $reference['year'] = $matches[1];
        }
        
        // Extraer DOI
        if (preg_match('/doi\.org\/([^\s]+)/', $text, $matches)) {
            $reference['doi'] = $matches[1];
        } elseif (preg_match('/doi:\s*([^\s]+)/i', $text, $matches)) {
            $reference['doi'] = $matches[1];
        }
        
        // Extraer URL
        if (preg_match('/<?(https?:\/\/[^\s>]+)>?/', $text, $matches)) {
            $reference['url'] = trim($matches[1], '<>');
        }
        
        // Extraer título (texto en cursiva o entre comillas)
        if (preg_match('/"([^"]+)"/', $text, $matches)) {
            $reference['title'] = $matches[1];
        } elseif (preg_match('/_([^_]+)_/', $text, $matches)) {
            $reference['title'] = $matches[1];
        }
        
        // Autores (antes del año)
        if (isset($reference['year']) && preg_match('/^(.+?)\s*\(' . $reference['year'] . '\)/', $text, $matches)) {
            $reference['authors'] = trim($matches[1]);
        }
        
        return $reference;
    }
    
    /**
     * Extraer citas in-text
     */
    private function extractCitations() {
        $citations = [];
        
        $text = $this->dom->textContent;
        
        // Buscar citas (Autor, año) o (Autor et al., año)
        preg_match_all('/\(([A-Z][a-záéíóúñ]+(?:\s+(?:et\s+al\.|&|y)\s+[A-Z][a-záéíóúñ]+)?),?\s*(\d{4})\)/u', $text, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $citations[] = [
                'citation_text' => $match[0],
                'author' => $match[1],
                'year' => $match[2],
            ];
        }
        
        return $citations;
    }
    
    /**
     * Detectar si el texto es en inglés
     */
    private function isEnglish($text) {
        $englishWords = ['the', 'of', 'and', 'to', 'in', 'for', 'on', 'with', 'by'];
        $spanishWords = ['el', 'la', 'de', 'en', 'y', 'para', 'con', 'por'];
        
        $lowerText = strtolower($text);
        $englishCount = 0;
        $spanishCount = 0;
        
        foreach ($englishWords as $word) {
            if (strpos($lowerText, " $word ") !== false) $englishCount++;
        }
        
        foreach ($spanishWords as $word) {
            if (strpos($lowerText, " $word ") !== false) $spanishCount++;
        }
        
        return $englishCount > $spanishCount;
    }
}
