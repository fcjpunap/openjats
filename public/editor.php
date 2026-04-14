<?php
session_start();

// Verificar sesión
if (!isset($_SESSION['user_id']) || !$_SESSION['logged_in']) {
    header('Location: login-directo.php');
    exit;
}

// Verificar ID
$articleId = $_GET['id'] ?? null;
if (!$articleId) {
    header('Location: index.php');
    exit;
}

$userName = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Usuario');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor de Marcación - OpenJATS</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .editor-page {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
        
        .editor-header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .editor-header h1 {
            font-size: 20px;
            margin: 0;
        }
        
        .editor-actions {
            display: flex;
            gap: 10px;
        }
        
        .editor-container {
            flex: 1;
            display: grid;
            grid-template-columns: 450px 1fr 300px;
            gap: 0;
            overflow: hidden;
        }
        
        .editor-content {
            background: white;
            padding: 40px;
            overflow-y: auto;
            border-right: 1px solid #e5e7eb;
        }
        
        .editor-sidebar {
            background: #f8f9fa;
            padding: 20px;
            overflow-y: auto;
        }
        
        .sidebar-section {
            background: white;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        
        .sidebar-section h3 {
            margin-top: 0;
            font-size: 16px;
            color: #2563eb;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #2563eb;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1e40af;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }
        
        .error {
            text-align: center;
            padding: 40px;
            color: #ef4444;
        }
        
        /* Estilos para elementos marcables */
        .markable-table {
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }
        
        .markable-table:hover {
            outline: 2px solid #2563eb;
            outline-offset: 2px;
        }
        
        .markable-table.marked {
            outline: 3px solid #10b981;
            outline-offset: 2px;
        }
        
        .markable-image {
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }
        
        .markable-image:hover {
            outline: 2px solid #2563eb;
            outline-offset: 2px;
        }
        
        .markable-image.marked {
            outline: 3px solid #10b981;
            outline-offset: 2px;
        }
        
        .mark-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            background: #10b981;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            z-index: 10;
        }
        
        .tool-btn {
            width: 100%;
            margin-bottom: 8px;
            padding: 10px;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            cursor: pointer;
            text-align: left;
            transition: all 0.2s;
        }
        
        .tool-btn:hover {
            border-color: #2563eb;
            background: #eff6ff;
        }
        
        .tool-btn.active {
            border-color: #10b981;
            background: #d1fae5;
        }
        
        .marked-items-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .marked-item {
            padding: 10px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            margin-bottom: 8px;
            font-size: 13px;
        }
        
        .marked-item .item-type {
            font-weight: 600;
            color: #10b981;
            margin-bottom: 4px;
        }
        
        .marked-item .item-caption {
            color: #6b7280;
        }
        
        .marked-item button {
            margin-top: 6px;
            padding: 4px 8px;
            font-size: 11px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
    </style>
</head>
<body class="editor-page">
    <header class="editor-header">
        <div>
            <h1 id="articleTitle">Cargando artículo...</h1>
            <p id="articleMeta" style="margin: 5px 0 0; font-size: 14px; color: #6b7280;"></p>
        </div>
        <div class="editor-actions">
            <div style="display:flex; align-items:center; gap:5px; border-right:1px solid #ccc; padding-right:15px; margin-right:5px;">
                <label for="historySelect" style="font-size:12px; font-weight:bold;">🕒 Historial:</label>
                <select id="historySelect" onchange="restoreVersion()" style="padding:5px; border-radius:4px; border:1px solid #ccc; font-size:12px;">
                    <option value="">Actual (Último guardado)</option>
                </select>
            </div>
            <button class="btn btn-primary" onclick="saveCombinedData()">💾 Guardar Todo</button>
            <a href="index.php" class="btn" onclick="return confirm('¿Seguro que quieres salir sin guardar los últimos cambios?')">← Volver</a>
        </div>
    </header>
    
    <div class="editor-container">
        <aside class="editor-sidebar" style="border-right: 1px solid #e5e7eb; display:flex; flex-direction:column; gap:10px; overflow-y:auto; padding-bottom:80px;">
            <div class="sidebar-section">
                <h3>📄 Metadatos Básicos</h3>
                <div style="display:grid; gap:5px; margin-bottom:10px; font-size:12px;">
                    <label><strong>Título:</strong> <button class="btn-secondary" onclick="extractTo('meta_title')">🎯</button></label>
                    <input type="text" id="meta_title" style="padding:4px;" placeholder="Título en español">

                    <label><strong>Título (EN):</strong> <button class="btn-secondary" onclick="extractTo('meta_title_en')">🎯</button></label>
                    <input type="text" id="meta_title_en" style="padding:4px;" placeholder="Title in English">

                    <label><strong>DOI:</strong> <button class="btn-secondary" onclick="extractTo('meta_doi')">🎯</button></label>
                    <input type="text" id="meta_doi" placeholder="10.1234/ejemplo" style="padding:4px;">

                    <label title="Ej: e202603"><strong>Paginación (E-loc):</strong> <button class="btn-secondary" onclick="extractTo('meta_pagination')">🎯</button></label>
                    <input type="text" id="meta_pagination" placeholder="e202603" style="padding:4px;">
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:5px; margin-bottom:10px; font-size:12px;">
                    <div><label><strong>Recibido:</strong></label><input type="date" id="meta_received_date" style="width:100%; padding:4px;"></div>
                    <div><label><strong>Aceptado:</strong></label><input type="date" id="meta_accepted_date" style="width:100%; padding:4px;"></div>
                    <div style="grid-column: span 2;"><label><strong>Publicado:</strong></label><input type="date" id="meta_published_date" style="width:100%; padding:4px;"></div>
                </div>

                <div style="display:grid; gap:5px; margin-bottom:10px; font-size:12px;">
                    <label><strong>Resumen (ES):</strong> <button class="btn-secondary" onclick="extractTo('meta_abstract')">🎯</button></label>
                    <textarea id="meta_abstract" style="height:60px; padding:4px; font-family:inherit;"></textarea>

                    <label><strong>Resumen (EN):</strong> <button class="btn-secondary" onclick="extractTo('meta_abstract_en')">🎯</button></label>
                    <textarea id="meta_abstract_en" style="height:60px; padding:4px; font-family:inherit;"></textarea>
                </div>

                <div style="display:grid; gap:5px; font-size:12px;">
                    <label><strong>Palabras Clave (ES):</strong> <button class="btn-secondary" onclick="extractTo('meta_keywords')">🎯</button></label>
                    <input type="text" id="meta_keywords" style="padding:4px;">

                    <label><strong>Palabras Clave (EN):</strong> <button class="btn-secondary" onclick="extractTo('meta_keywords_en')">🎯</button></label>
                    <input type="text" id="meta_keywords_en" style="padding:4px;">
                </div>
            </div>

            <div class="sidebar-section">
                <h3>👥 Autores</h3>
                <div style="display:grid; gap:5px; font-size:12px;">
                    <div style="display:flex; gap:5px;">
                        <input type="text" id="auth_given" placeholder="Nombres" style="flex:1;"><button class="btn-secondary" onclick="extractTo('auth_given')">🎯</button>
                    </div>
                    <div style="display:flex; gap:5px;">
                        <input type="text" id="auth_sur" placeholder="Apellidos" style="flex:1;"><button class="btn-secondary" onclick="extractTo('auth_sur')">🎯</button>
                    </div>
                    <div style="display:flex; gap:5px;">
                        <input type="text" id="auth_aff" placeholder="Afiliación" style="flex:1;"><button class="btn-secondary" onclick="extractTo('auth_aff')">🎯</button>
                    </div>
                    <div style="display:flex; gap:5px;">
                        <input type="text" id="auth_email" placeholder="Email" style="flex:1;"><button class="btn-secondary" onclick="extractTo('auth_email')">🎯</button>
                    </div>
                    <div style="display:flex; gap:5px;">
                        <input type="text" id="auth_orcid" placeholder="ORCID (0000-...)" style="flex:1;"><button class="btn-secondary" onclick="extractTo('auth_orcid')">🎯</button>
                    </div>
                    <label><input type="checkbox" id="auth_corr"> Es autor de correspondencia</label>
                    <button id="btnAddAuthor" class="btn-secondary" onclick="addAuthor()">Añadir Autor a la lista</button>
                </div>
                <div id="authorsList" style="margin-top:10px; max-height:150px; overflow-y:auto; font-size:11px;"></div>
            </div>

            <div class="sidebar-section">
                <h3>📑 Secciones y Contenido</h3>
                <div style="display:grid; gap:5px; font-size:12px;">
                    <select id="section_type" style="padding:4px;">
                        <option value="intro">Introducción</option>
                        <option value="methods">Metodología</option>
                        <option value="theory">Marco Teórico</option>
                        <option value="results">Resultados</option>
                        <option value="discussion">Discusión</option>
                        <option value="conclusions">Conclusiones</option>
                        <option value="declarations">Declaraciones</option>
                        <option value="other">Otros</option>
                    </select>
                    <div style="display:flex; gap:5px;">
                        <input type="text" id="section_title" placeholder="Rótulo / Título (Ej: 1.1 Intro)" style="flex:1;">
                        <button class="btn-secondary" onclick="extractTo('section_title')">🎯</button>
                    </div>
                    <select id="section_level" style="padding:4px;">
                        <option value="1">H1 (Sección Principal)</option>
                        <option value="2">H2 (Subsección)</option>
                        <option value="3">H3 (Subsección nivel 3)</option>
                    </select>
                    <div style="background:#f3f4f6; padding:2px; display:flex; gap:2px;">
                        <button class="btn-secondary" onclick="document.execCommand('bold',false,null)"><b>B</b></button>
                        <button class="btn-secondary" onclick="document.execCommand('italic',false,null)"><i>I</i></button>
                        <button class="btn-secondary" onclick="document.execCommand('insertUnorderedList',false,null)">•</button>
                        <button class="btn-secondary" onclick="document.execCommand('insertOrderedList',false,null)">1.</button>
                        <button class="btn-secondary" title="Izquierda" onclick="document.execCommand('justifyLeft',false,null)">⫷</button>
                        <button class="btn-secondary" title="Centro" onclick="document.execCommand('justifyCenter',false,null)">≡</button>
                        <button class="btn-secondary" title="Derecha" onclick="document.execCommand('justifyRight',false,null)">⫸</button>
                        <button class="btn-secondary" title="Justificar" onclick="document.execCommand('justifyFull',false,null)">▤</button>
                        <div style="border-left:1px solid #ccc; margin:0 5px; min-height:15px;"></div>
                        <input type="number" id="manual_link_ref" placeholder="Ref N°" title="Número de la referencia (ej: 1)" style="width:50px; padding:2px; font-size:11px;" min="1">
                        <button class="btn-secondary" title="Vincular selección a referencia" onclick="manualLinkCitation()" style="background:#dbeafe; color:#1d4ed8; font-weight:bold; font-size:10px;">🔗 Ref</button>
                        <input type="number" id="manual_link_fn" placeholder="Nota N°" title="Número de la nota al pie (ej: 1)" style="width:50px; padding:2px; font-size:11px; margin-left:5px;" min="1">
                        <button class="btn-secondary" title="Vincular selección a nota al pie" onclick="manualLinkFn()" style="background:#fef08a; color:#854d0e; font-weight:bold; font-size:10px;">🔗 Nota pie</button>
                        <button class="btn-secondary" title="Insertar Tabla" onclick="insertTablePlaceholder()" style="background:#eff6ff; color:#1e40af; font-weight:bold; font-size:10px;">📊 Tabla</button>
                        <button class="btn-secondary" title="Insertar Figura" onclick="insertFigurePlaceholder()" style="background:#f0fdf4; color:#166534; font-weight:bold; font-size:10px;">🖼️ Figura</button>
                        <button class="btn-secondary" style="margin-left:auto" onclick="extractSectionContent()">Extraer 🎯</button>
                    </div>
                    <div id="section_editor" contenteditable="true" style="min-height:80px; max-height:200px; overflow-y:auto; background:white; border:1px solid #ccc; padding:5px;"></div>
                    <button id="btnAddSection" class="btn-secondary" onclick="addSectionItem()">Guardar Sección a la lista</button>
                </div>
                <div id="sectionsList" style="margin-top:10px; font-size:11px; max-height:150px; overflow-y:auto;"></div>
            </div>

            <div class="sidebar-section">
                <h3>📚 Referencias (Inteligente)</h3>
                <p style="font-size:11px; margin-bottom:5px;">Selecciona una referencia y presiona el botón para rellenar las cajas:</p>
                <button class="btn-secondary" onclick="analyzeReferences()" style="width:100%; margin-bottom:5px;">🔍 Extraer Referencia Seleccionada</button>
                <div style="display:flex; gap:5px; margin-bottom:10px;">
                    <button class="btn-secondary" onclick="linkCitations()" style="flex:1; background-color:#eff6ff; border-color:#bfdbfe; color:#1e40af; font-size:11px;">🔗 Vincular Citas Inteligente</button>
                    <button class="btn-secondary" onclick="autoLinkFootnotes()" style="flex:1; background-color:#fefce8; border-color:#fef08a; color:#854d0e; font-size:11px;">🔗 Vincular Notas [1] Inteligente</button>
                </div>
                <div style="display:grid; gap:5px; font-size:12px;">
                    <div style="display:flex; gap:5px;"><input type="text" id="ref_authors" placeholder="Autores" style="flex:1"><button class="btn-secondary" onclick="extractTo('ref_authors')">🎯</button></div>
                    <div style="display:flex; gap:5px;"><input type="text" id="ref_year" placeholder="Año" style="flex:1"><button class="btn-secondary" onclick="extractTo('ref_year')">🎯</button></div>
                    <div style="display:flex; gap:5px;"><input type="text" id="ref_title" placeholder="Título Original" style="font-weight:bold; flex:1"><button class="btn-secondary" onclick="extractTo('ref_title')">🎯</button></div>
                    <div style="display:flex; gap:5px;"><input type="text" id="ref_source" placeholder="Revista / Editorial" style="flex:1"><button class="btn-secondary" onclick="extractTo('ref_source')">🎯</button></div>
                    <div style="display:flex; gap:5px;"><input type="text" id="ref_pages" placeholder="Páginas (Opc. ej: 1-12)" style="flex:1"><button class="btn-secondary" onclick="extractTo('ref_pages')">🎯</button></div>
                    <div style="display:flex; gap:5px;"><input type="text" id="ref_doi" placeholder="DOI (Opcional)" style="flex:1"><button class="btn-secondary" onclick="extractTo('ref_doi')">🎯</button></div>
                    <div style="display:flex; gap:5px;"><input type="text" id="ref_url" placeholder="URL (Opcional)" style="flex:1"><button class="btn-secondary" onclick="extractTo('ref_url')">🎯</button></div>
                    <button id="btnAddRef" class="btn-secondary" onclick="addReferenceItem()">Añadir a lista manual</button>
                </div>
                <div id="referencesList" style="margin-top:10px; max-height:150px; overflow-y:auto; font-size:11px;"></div>
                
                <hr style="margin:10px 0; border:none; border-top:1px solid #e5e7eb;">
                <h3>📝 Notas al pie</h3>
                <button class="btn-secondary" onclick="analyzeFootnotes()" style="width:100%; margin-bottom:5px;">🔍 Extraer de Selección (Múltiple)</button>
                <div style="display:flex; gap:5px; margin-bottom:5px;">
                    <textarea id="fn_text" placeholder="Texto de la nota al pie (selecciona y pulsa 🎯)" style="flex:1; height:40px; font-size:10px; resize:vertical; padding:5px;"></textarea>
                    <div style="display:flex; flex-direction:column; justify-content:space-between; gap:2px;">
                        <button class="btn-secondary" style="padding:2px 5px;" onclick="extractTo('fn_text')">🎯</button>
                        <button id="btnAddFn" class="btn-secondary" style="padding:2px 5px; font-size:9px;" onclick="addFootnoteItem()">+ Add</button>
                    </div>
                </div>
                <div id="footnotesList" style="margin-top:10px; max-height:120px; overflow-y:auto; font-size:11px;"></div>
            </div>

        </aside>

        <div class="editor-content">
            <div id="loadingMessage" class="loading">
                Cargando artículo...
            </div>
            <div id="errorMessage" class="error" style="display: none;"></div>
            <div id="articleContent" style="display: none;">
                <!-- El contenido del artículo se cargará aquí -->
            </div>
        </div>
        
        <aside class="editor-sidebar">
            <div class="sidebar-section">
                <h3>📋 Información del Artículo</h3>
                <p id="articleInfo">Cargando...</p>
            </div>
            
            <div class="sidebar-section">
                <h3>📊 Tablas (APA 7)</h3>
                <div style="display:flex; flex-direction:column; gap:5px; margin-bottom:10px;">
                    <button class="btn-secondary" onclick="analyzeSmartTables()" style="font-size:11px; background:#eff6ff; color:#1e40af; border-color:#bfdbfe;">🔍 Extracción Inteligente de Tablas</button>
                    <button class="btn-secondary" onclick="addTableItemManual()" style="font-size:11px;">➕ Añadir a la lista manual de tablas</button>
                </div>
                <div id="smartTablesList" style="max-height:180px; overflow-y:auto; font-size:11px; display:grid; gap:4px;">
                    <p style="color: #6b7280; font-style:italic;">No hay tablas marcadas.</p>
                </div>
            </div>

            <div class="sidebar-section">
                <h3>🖼️ Figuras / Imágenes</h3>
                <div style="display:flex; flex-direction:column; gap:5px; margin-bottom:10px;">
                    <button class="btn-secondary" onclick="analyzeSmartFigures()" style="font-size:11px; background:#f0fdf4; color:#166534; border-color:#bbf7d0;">🔍 Extracción Inteligente de Figuras</button>
                    <button class="btn-secondary" onclick="addFigureItemManual()" style="font-size:11px;">➕ Añadir a la lista manual de figuras</button>
                </div>
                <div id="smartFiguresList" style="max-height:180px; overflow-y:auto; font-size:11px; display:grid; gap:4px;">
                    <p style="color: #6b7280; font-style:italic;">No hay figuras marcadas.</p>
                </div>
            </div>

            <div class="sidebar-section" style="display:none;">
                <h3>🎯 Herramientas de Marcación Originales</h3>
                <button class="tool-btn" id="tableTool" onclick="activateTool('table')">📊 Marcar Tablas</button>
                <button class="tool-btn" id="imageTool" onclick="activateTool('image')">🖼️ Marcar Imágenes</button>
            </div>
            
            <div class="sidebar-section" style="display:none;">
                <h3>✅ Elementos Marcados (<span id="markedCount">0</span>)</h3>
                <div id="markedItemsList" class="marked-items-list">
                    <p style="color: #6b7280; font-size: 13px;">No hay elementos marcados aún</p>
                </div>
            </div>
            
            <div class="sidebar-section">
                <h3>⚙️ Acciones</h3>
                <button class="btn btn-secondary" style="width: 100%; margin-bottom: 5px;" onclick="generateXML()">
                    📄 Generar XML-JATS
                </button>
                <button class="btn btn-secondary" style="width: 100%; margin-bottom: 5px; background-color: #f59e0b;" onclick="generateSciELO()">
                    📄 XML SciELO
                </button>
                <button class="btn btn-secondary" style="width: 100%; margin-bottom: 5px; background-color: #dc2626;" onclick="generateRedalyc()">
                    📄 XML Redalyc
                </button>
                <button class="btn btn-secondary" style="width: 100%; margin-bottom: 5px;" onclick="generatePDF()">
                    📕 Generar PDF
                </button>
                <button class="btn btn-secondary" style="width: 100%; margin-bottom: 5px;" onclick="generateHTML()">
                    🌐 Generar HTML
                </button>
                <button class="btn btn-secondary" style="width: 100%; margin-bottom: 5px;" onclick="generateEPUB()">
                    📚 Generar EPUB
                </button>
                <button class="btn btn-secondary" style="width: 100%; background-color: #059669; margin-top: 10px;" onclick="exportOJS()">
                    📦 Exportar OJS (ZIP)
                </button>
            </div>
        </aside>
    </div>
    
    <script>
        const articleId = <?php echo json_encode($articleId); ?>;
        let customSections = [];
        let customReferences = [];
        let customAuthors = [];
        let customFootnotes = [];
        let customTables = [];
        let customFigures = [];
        let activeTool = null;
        let markedElements = { tables: [], images: [] };
        
        function extractTo(inputId) {
            const selection = window.getSelection().toString().trim();
            if (selection) { document.getElementById(inputId).value = selection; }
        }

        function extractSectionContent() {
            const html = getSelectionHtml();
            if(html) { document.getElementById('section_editor').innerHTML += html + "<br>"; }
        }
        
        function getSelectionHtml() {
            let html = "";
            if (typeof window.getSelection != "undefined") {
                let sel = window.getSelection();
                if (sel.rangeCount) {
                    let container = document.createElement("div");
                    for (let i = 0, len = sel.rangeCount; i < len; ++i) {
                        container.appendChild(sel.getRangeAt(i).cloneContents());
                    }
                    html = container.innerHTML;
                }
            }
            return html;
        }

        // TOOL: Manual Link Citation
        function manualLinkCitation() {
            let sel = window.getSelection();
            if (sel.rangeCount > 0 && !sel.isCollapsed) {
                let refIdNum = document.getElementById('manual_link_ref').value;
                if(!refIdNum) return alert('Debes ingresar el N° de la referencia a la cual apuntar (Ejemplo: 1).');
                let refIdx = parseInt(refIdNum) - 1;
                if(refIdx < 0 || refIdx >= customReferences.length) return alert('Esta referencia no existe en la pestaña Referencias.');
                
                if(!customReferences[refIdx].linked_count) customReferences[refIdx].linked_count = 0;
                customReferences[refIdx].linked_count++;
                
                let selectedText = sel.toString();
                document.execCommand("insertHTML", false, `<a href="#ref-${refIdNum}" class="citation-link" data-rid="ref-${refIdNum}" style="color:#2563eb; text-decoration:none; border-bottom:1px dotted #2563eb;">${selectedText}</a>`);
                updateReferencesList();
            } else {
                alert("Primero debes seleccionar el texto en el editor de contenido, y digitar el número de Referencia.");
            }
        }

        // TOOL: Manual Link Footnote
        function manualLinkFn() {
            let sel = window.getSelection();
            if (sel.rangeCount > 0 && !sel.isCollapsed) {
                let fnIdNum = document.getElementById('manual_link_fn').value;
                if(!fnIdNum) return alert('Debes ingresar el N° de la nota al pie (Ejemplo: 1).');
                
                let selectedText = sel.toString();
                document.execCommand("insertHTML", false, `<sup><a href="#fn-${fnIdNum}" class="fn-link" data-fnid="fn-${fnIdNum}" style="color:#d97706; text-decoration:none;">${selectedText}</a></sup>`);
            } else {
                alert("Primero debes seleccionar el texto en el editor de contenido, y digitar el número de Nota al pie.");
            }
        }

        // TOOL: Insert Table Placeholder
        function insertTablePlaceholder() {
            if (customTables.length === 0) return alert("Primero debes marcar o añadir tablas en la pestaña de Tablas.");
            
            let options = customTables.map((t, i) => `${i+1}: ${t.label}`).join("\n");
            let num = prompt("Selecciona el número de la tabla a insertar:\n" + options, "1");
            
            if (num) {
                let idx = parseInt(num) - 1;
                if (customTables[idx]) {
                    let label = `[${customTables[idx].label}]`;
                    insertPlaceholderLabel(label, '#2563eb', '#eff6ff');
                } else {
                    alert("Número de tabla no válido.");
                }
            }
        }

        // TOOL: Insert Figure Placeholder
        function insertFigurePlaceholder() {
            if (customFigures.length === 0) return alert("Primero debes marcar o añadir figuras en la pestaña de Figuras.");
            
            let options = customFigures.map((f, i) => `${i+1}: ${f.label}`).join("\n");
            let num = prompt("Selecciona el número de la figura a insertar:\n" + options, "1");
            
            if (num) {
                let idx = parseInt(num) - 1;
                if (customFigures[idx]) {
                    let label = `[${customFigures[idx].label}]`;
                    insertPlaceholderLabel(label, '#166534', '#f0fdf4');
                } else {
                    alert("Número de figura no válido.");
                }
            }
        }

        function insertPlaceholderLabel(text, color, bg) {
            const html = `<span contenteditable="false" style="background:${bg}; color:${color}; padding:2px 4px; border-radius:3px; font-weight:bold; margin:0 2px; border:1px solid ${color}44;">${text}</span>&nbsp;`;
            document.getElementById('section_editor').focus();
            document.execCommand("insertHTML", false, html);
        }

        // TOOL: Auto Link Footnotes
        function autoLinkFootnotes() {
            let totalLinksCount = 0;
            customSections.forEach((sec, sIdx) => {
                let html = sec.content;
                // find [1] or [2]
                html = html.replace(/\[(\d+)\]/g, (match, p1) => {
                    totalLinksCount++;
                    return `<sup><a href="#fn-${p1}" class="fn-link" data-fnid="fn-${p1}" style="color:#d97706; text-decoration:none;">${match}</a></sup>`;
                });
                customSections[sIdx].content = html;
            });
            updateSectionsList();
            alert(`✅ Se han convertido ${totalLinksCount} instancias de "[X]" a hipervínculos de Notas al pie.`);
        }

        // ----------- AUTORES -------------
        let editingAuthorIndex = -1;
        function editAuthor(i) {
            let a = customAuthors[i];
            document.getElementById('auth_given').value = a.given_names || '';
            document.getElementById('auth_sur').value = a.surname || '';
            document.getElementById('auth_aff').value = a.affiliation || '';
            document.getElementById('auth_email').value = a.email || '';
            document.getElementById('auth_orcid').value = a.orcid || '';
            document.getElementById('auth_corr').checked = a.corresponding == 1;
            editingAuthorIndex = i;
            document.getElementById('btnAddAuthor').textContent = 'Actualizar Autor';
            document.getElementById('auth_given').focus();
        }

        function addAuthor() {
            const given = document.getElementById('auth_given').value;
            const sur = document.getElementById('auth_sur').value;
            if(!sur) return alert("Apellidos obligatorios");
            
            let obj = {
                given_names: given, surname: sur,
                affiliation: document.getElementById('auth_aff').value,
                email: document.getElementById('auth_email').value,
                orcid: document.getElementById('auth_orcid').value,
                corresponding: document.getElementById('auth_corr').checked ? 1 : 0
            };
            
            if (editingAuthorIndex >= 0) {
                customAuthors[editingAuthorIndex] = obj;
                editingAuthorIndex = -1;
                document.getElementById('btnAddAuthor').textContent = 'Añadir Autor a la lista';
            } else {
                customAuthors.push(obj);
            }
            
            document.getElementById('auth_given').value = '';
            document.getElementById('auth_sur').value = '';
            document.getElementById('auth_aff').value = '';
            document.getElementById('auth_email').value = '';
            document.getElementById('auth_orcid').value = '';
            document.getElementById('auth_corr').checked = false;
            updateAuthorsList();
        }
        function updateAuthorsList() {
            const lst = document.getElementById('authorsList');
            lst.innerHTML = customAuthors.map((a, i) => `
                <div style="background:#f8f9fa; padding:4px; margin-bottom:4px; border:1px solid #ddd;">
                    ${a.given_names} <b>${a.surname}</b> ${a.corresponding ? '(Corr.)' : ''}<br>
                    <span style="color:gray">${a.affiliation} / ${a.email}</span>
                    <div style="margin-top: 3px; display:flex; justify-content: flex-end; gap:5px;">
                        <button onclick="editAuthor(${i})" style="color:#2563eb; cursor:pointer; border:1px solid #ccc; padding:2px 5px; border-radius:3px; background:white;">✏️</button>
                        <button onclick="customAuthors.splice(${i},1); updateAuthorsList();" style="color:red; cursor:pointer; border:1px solid #ccc; padding:2px 5px; border-radius:3px; background:white;">🗑️</button>
                    </div>
                </div>
            `).join('');
        }

        // ----------- SECCIONES -------------
        let editingSectionIndex = -1;
        function editSection(i) {
            let s = customSections[i];
            document.getElementById('section_title').value = s.type_name;
            document.getElementById('section_type').value = s.type;
            document.getElementById('section_level').value = s.level || 1;
            document.getElementById('section_editor').innerHTML = s.content;
            editingSectionIndex = i;
            document.getElementById('btnAddSection').textContent = 'Actualizar Sección';
            document.getElementById('section_title').focus();
        }

        function addSectionItem() {
            const title = document.getElementById('section_title').value;
            if(!title) return alert("Ingrese un rótulo/título");
            
            let obj = {
                type: document.getElementById('section_type').value,
                type_name: title,
                content: document.getElementById('section_editor').innerHTML,
                level: document.getElementById('section_level').value
            };
            
            if(editingSectionIndex >= 0) {
                customSections[editingSectionIndex] = obj;
                editingSectionIndex = -1;
                document.getElementById('btnAddSection').textContent = 'Guardar Sección a la lista';
            } else {
                customSections.push(obj);
            }
            
            document.getElementById('section_title').value = '';
            document.getElementById('section_editor').innerHTML = '';
            updateSectionsList();
        }
        function updateSectionsList() {
            document.getElementById('sectionsList').innerHTML = customSections.map((s, i) => `
                <div style="background:#f8f9fa; padding:4px; margin-bottom:4px; border:1px solid #ddd;">
                    <div style="font-weight:bold;">[H${Math.min(s.level||1, 3)}] ${s.type_name}</div>
                    <div style="max-height: 40px; overflow: hidden; font-size: 10px; color:#555;">${s.content.replace(/<[^>]+>/g, '').substring(0,80)}...</div>
                    <div style="margin-top: 3px; display:flex; justify-content: flex-end; gap:5px;">
                        <button onclick="editSection(${i})" style="color:#2563eb; cursor:pointer; border:1px solid #ccc; padding:2px 5px; border-radius:3px; background:white;">✏️ Editar</button>
                        <button onclick="customSections.splice(${i},1); updateSectionsList();" style="color:red; cursor:pointer; border:1px solid #ccc; padding:2px 5px; border-radius:3px; background:white;">🗑️ Eliminar</button>
                    </div>
                </div>
            `).join('');
        }

        // ----------- REFERENCIAS -------------
        function extractDoiUrl(text) {
            const urlMatch = text.match(/(http[s]?:\/\/[^\s]+)/);
            const doiMatch = text.match(/10\.\d{4,9}\/[-._;()/:A-Z0-9]+/i);
            return {
                url: urlMatch ? urlMatch[1] : '',
                doi: doiMatch ? '10.' + doiMatch[0].split('10.')[1] : ''
            };
        }

        function analyzeReferences() {
            const sel = window.getSelection().toString().trim();
            if(!sel) return alert("Selecciona el texto de UNA referencia para extraerla.");
            
            // Heuristic APA parser
            let year = sel.match(/\((\d{4}[a-z]?)\)/);
            year = year ? year[1] : '';
            
            let authors = '';
            let title = '';
            let source = '';
            let pages = '';
            
            let partes = [];
            if(year) partes = sel.split(`(${year})`);
            
            if(partes.length >= 2) {
                authors = partes[0].trim();
                let resto = partes[1].split('.');
                if(resto.length >= 2) {
                    title = resto[0].trim();
                    let srcFull = resto.slice(1).join('.').replace(/(http(.*))|doi:.*$/i, '').trim();
                    let ppMatch = srcFull.match(/,\s*(\d+\s*-\s*\d+)/);
                    if(ppMatch) {
                        pages = ppMatch[1];
                        source = srcFull.replace(/,\s*\d+\s*-\s*\d+/, '').replace(/^,/, '').trim();
                    } else {
                        source = srcFull;
                    }
                } else {
                    title = partes[1].replace(/(http(.*))|doi:.*$/i, '').trim();
                }
            } else {
                let firstDot = sel.indexOf('.');
                if (firstDot > 10) {
                    authors = sel.substring(0, firstDot).trim();
                    title = sel.substring(firstDot + 1).replace(/(http(.*))|doi:.*$/i, '').trim();
                } else {
                    title = sel; 
                }
            }
            
            authors = authors.replace(/\.$/, '').trim();
            title = title.replace(/^\.$/, '').trim();
            source = source.replace(/\.$/, '').trim();
            
            let links = extractDoiUrl(sel);
            
            document.getElementById('ref_authors').value = authors;
            document.getElementById('ref_year').value = year;
            document.getElementById('ref_title').value = title;
            document.getElementById('ref_source').value = source;
            document.getElementById('ref_pages').value = pages;
            document.getElementById('ref_doi').value = links.doi;
            document.getElementById('ref_url').value = links.url;
        }

        let editingReferenceIndex = -1;
        function editReference(i) {
            let r = customReferences[i];
            document.getElementById('ref_authors').value = r.authors || '';
            document.getElementById('ref_year').value = r.year || '';
            document.getElementById('ref_title').value = r.title || '';
            document.getElementById('ref_source').value = r.source || '';
            document.getElementById('ref_pages').value = r.pages || '';
            document.getElementById('ref_doi').value = r.doi || '';
            document.getElementById('ref_url').value = r.url || '';
            editingReferenceIndex = i;
            document.getElementById('btnAddRef').textContent = 'Actualizar Referencia';
            document.getElementById('ref_authors').focus();
        }

        function addReferenceItem() {
            const r_authors = document.getElementById('ref_authors').value;
            const r_title = document.getElementById('ref_title').value;
            if(!r_authors && !r_title) return alert("Ingresa autores o título para guardar");
            
            let obj = {
                authors: r_authors,
                year: document.getElementById('ref_year').value,
                title: r_title,
                source: document.getElementById('ref_source').value,
                url: document.getElementById('ref_url').value
            };
            
            if(editingReferenceIndex >= 0) {
                // conservar linked_count si existe
                if (customReferences[editingReferenceIndex].linked_count) {
                    obj.linked_count = customReferences[editingReferenceIndex].linked_count;
                }
                customReferences[editingReferenceIndex] = obj;
                editingReferenceIndex = -1;
                document.getElementById('btnAddRef').textContent = 'Añadir a lista manual';
            } else {
                customReferences.push(obj);
            }

            document.getElementById('ref_authors').value = '';
            document.getElementById('ref_year').value = '';
            document.getElementById('ref_title').value = '';
            document.getElementById('ref_source').value = '';
            document.getElementById('ref_url').value = '';
            updateReferencesList();
        }

        function updateReferencesList() {
            document.getElementById('referencesList').innerHTML = customReferences.map((r, i) => {
                let lCountHtml = r.linked_count ? `<span style="background:#dbeafe; color:#1d4ed8; padding:1px 4px; border-radius:3px; font-size:9px; margin-left:5px;">Vinculada ${r.linked_count}x</span>` : '';
                return `
                <div style="background:#f8f9fa; padding:4px; margin-bottom:4px; border:1px solid #ddd;">
                    [${i+1}] ${r.authors} (${r.year}). <b>${r.title}</b>. ${r.source} 
                    ${r.url ? `<a href="${r.url}" target="_blank">🔗</a>` : ''}
                    ${lCountHtml}
                    <div style="margin-top: 3px; display:flex; justify-content: flex-end; gap:5px;">
                        <button onclick="editReference(${i})" style="color:#2563eb; cursor:pointer; border:1px solid #ccc; padding:2px 5px; border-radius:3px; background:white;">✏️</button>
                        <button onclick="customReferences.splice(${i},1); updateReferencesList();" style="color:red; cursor:pointer; border:1px solid #ccc; padding:2px 5px; border-radius:3px; background:white;">🗑️</button>
                    </div>
                </div>`;
            }).join('');
        }

        // ----------- NOTAS AL PIE -------------
        function analyzeFootnotes() {
            const sel = window.getSelection().toString().trim();
            if(!sel) return alert("Selecciona el texto agrupado de TODAS las notas al pie juntas para extraerlas.");
            
            let fnLines = sel.split(/\n+/);
            let fnCount = 0;
            fnLines.forEach(line => {
                let match = line.match(/^\[?(\d+)\]?\s*(.*)$/);
                if(match) {
                    let fnId = match[1];
                    let text = match[2].trim();
                    let exist = customFootnotes.findIndex(f => f.fn_id === fnId);
                    if(exist >= 0) customFootnotes[exist].text = text;
                    else customFootnotes.push({fn_id: fnId, text: text});
                    fnCount++;
                } else {
                    if(customFootnotes.length > 0 && line.trim()) {
                        customFootnotes[customFootnotes.length-1].text += " " + line.trim();
                        fnCount++; // Feedback helper
                    }
                }
            });
            if(fnCount > 0) {
                updateFootnotesList();
                alert(`✅ Se extrajeron o actualizaron notas al pie agrupadas.`);
            } else {
                alert("No se detectó el formato numerado al inicio en ninguna línea. Asegúrate de que las notas inician en un renglón nuevo como: '1. ' o '[1]'.");
            }
        }

        let editingFootnoteIndex = -1;
        function addFootnoteItem() {
            const txt = document.getElementById('fn_text').value.trim();
            if(!txt) return alert("Ingresa el texto de la nota al pie");
            
            if(editingFootnoteIndex >= 0) {
                customFootnotes[editingFootnoteIndex].text = txt;
                editingFootnoteIndex = -1;
                document.getElementById('btnAddFn').textContent = '+ Add';
            } else {
                let nextId = customFootnotes.length + 1;
                if (customFootnotes.length > 0) {
                    let maxId = Math.max(...customFootnotes.map(f => parseInt(f.fn_id) || 0));
                    nextId = maxId + 1;
                }
                customFootnotes.push({
                    fn_id: nextId.toString(),
                    text: txt
                });
            }

            document.getElementById('fn_text').value = '';
            updateFootnotesList();
        }

        function editFootnote(i) {
            let fn = customFootnotes[i];
            document.getElementById('fn_text').value = fn.text || '';
            editingFootnoteIndex = i;
            document.getElementById('btnAddFn').textContent = 'Guardar';
            document.getElementById('fn_text').focus();
        }

        function updateFootnotesList() {
            document.getElementById('footnotesList').innerHTML = customFootnotes.map((fn, i) => {
                let dispText = fn.text || '[Nota pendiente de edición]';
                if(dispText.length > 80) dispText = dispText.substring(0,80) + '...';
                return `
                <div style="background:#fefce8; padding:4px; margin-bottom:4px; border:1px solid #fef08a;">
                    <b>[${fn.fn_id}]</b> ${dispText}
                    <div style="margin-top: 3px; display:flex; justify-content: flex-end; gap:5px;">
                        <button onclick="editFootnote(${i})" style="color:#854d0e; cursor:pointer; border:1px solid #ccc; padding:2px 5px; border-radius:3px; background:white;">✏️</button>
                        <button onclick="customFootnotes.splice(${i},1); updateFootnotesList();" style="color:red; cursor:pointer; border:1px solid #ccc; padding:2px 5px; border-radius:3px; background:white;">🗑️</button>
                    </div>
                </div>`;
            }).join('');
        }

        function linkCitations() {
            if(customReferences.length === 0) {
                alert("Primero debes extraer o añadir referencias.");
                return;
            }
            if(customSections.length === 0) {
                alert("No hay secciones guardadas en las cuales buscar citas.");
                return;
            }
            
            let totalLinksCount = 0;
            customSections.forEach((sec, sIdx) => {
                let html = sec.content;
                
                customReferences.forEach((ref, rIdx) => {
                    if(!ref.authors || !ref.year) return;
                    let firstAuthor = ref.authors.split(',')[0].replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ]/g, '').trim();
                    let year = ref.year.replace(/[^0-9a-z]/g, '').trim();
                    if(firstAuthor.length < 3 || year.length < 4) return;
                    
                    try {
                        let regex = new RegExp(`\\([^)]*?${firstAuthor}[^)]*?${year}[^)]*?\\)`, "gi");
                        html = html.replace(regex, match => {
                            if(match.includes('href=')) return match;
                            totalLinksCount++;
                            if(!customReferences[rIdx].linked_count) customReferences[rIdx].linked_count = 0;
                            customReferences[rIdx].linked_count++;
                            return `<a href="#ref-${rIdx+1}" class="citation-link" data-rid="ref-${rIdx+1}" style="color:#2563eb; text-decoration:none; border-bottom:1px dotted #2563eb;">${match}</a>`;
                        });
                    } catch(e) {}
                });
                
                customSections[sIdx].content = html;
            });
            
            updateSectionsList();
            updateReferencesList();
            alert(`✅ Se han vinculado ${totalLinksCount} instancias NUEVAS de citas automáticamente en TODAS tus secciones guardadas.`);
        }

        // ----------- GUARDADO TOTAL -------------
        async function saveCombinedData() {
            const markupData = {
                tables: customTables.map((t, idx) => ({ 
                    number: idx + 1, 
                    label: t.label,
                    title: t.title || t.caption || '',
                    caption: t.caption || t.title || '',
                    html: t.html || t.content || '',
                    content: t.content || t.html || '',
                    nota: t.nota || '',
                    type: t.type || 'html',
                    src: t.src || '',
                    id: t.id
                })),
                images: customFigures.map((i, idx) => ({ 
                    number: idx + 1, 
                    label: i.label,
                    caption: i.caption || i.alt || '',
                    alt: i.alt || i.caption || '',
                    src: i.src || '',
                    nota: i.nota || '',
                    width: i.width || '100%',
                    id: i.id
                }))
            };
            
            const metaData = {
                article_id: articleId,
                title: document.getElementById('meta_title').value,
                title_en: document.getElementById('meta_title_en').value,
                doi: document.getElementById('meta_doi').value,
                pagination: document.getElementById('meta_pagination').value,
                received_date: document.getElementById('meta_received_date').value,
                accepted_date: document.getElementById('meta_accepted_date').value,
                published_date: document.getElementById('meta_published_date').value,
                abstract: document.getElementById('meta_abstract').value,
                abstract_en: document.getElementById('meta_abstract_en').value,
                keywords: document.getElementById('meta_keywords').value,
                keywords_en: document.getElementById('meta_keywords_en').value,
                custom_authors: customAuthors,
                custom_sections: customSections,
                custom_references: customReferences,
                custom_footnotes: customFootnotes
            };

            try {
                let r1 = await fetch('api.php?action=update_metadata', {
                    method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(metaData)
                });
                let result = await r1.json();
                
                if(markupData.tables.length > 0 || markupData.images.length > 0) {
                    await fetch('api.php?action=save_markup', {
                        method: 'POST', headers: {'Content-Type':'application/json'}, 
                        body: JSON.stringify({article_id: articleId, markup_data: markupData})
                    });
                }
                
                if(result.success) {
                    alert("✅ Documento guardado integralmente.");
                } else {
                    alert("❌ Error: " + result.message);
                }
            } catch(e) {
                alert("❌ Error: " + e.message);
            }
        }

        
        // Cargar artículo al inicio
        async function loadArticle() {
            try {
                const response = await fetch(`api.php?action=get_article&id=${articleId}`);
                const data = await response.json();
                
                if (data.success && data.article) {
                    document.getElementById('loadingMessage').style.display = 'none';
                    document.getElementById('articleContent').style.display = 'block';
                    
                    // Mostrar información
                    document.getElementById('articleTitle').textContent = data.article.title || 'Sin título';
                    document.getElementById('articleMeta').textContent = `ID: ${data.article.id} | Estado: ${data.article.status}`;
                    
                    if(data.article) {
                        document.getElementById('meta_title').value = data.article.title || '';
                        document.getElementById('meta_title_en').value = data.article.title_en || '';
                        document.getElementById('meta_doi').value = data.article.doi || '';
                        document.getElementById('meta_pagination').value = data.article.pagination || '';
                        document.getElementById('meta_received_date').value = data.article.received_date ? data.article.received_date.split(' ')[0] : '';
                        document.getElementById('meta_accepted_date').value = data.article.accepted_date ? data.article.accepted_date.split(' ')[0] : '';
                        document.getElementById('meta_published_date').value = data.article.published_date ? data.article.published_date.split(' ')[0] : '';
                        
                        document.getElementById('meta_abstract').value = data.article.abstract || '';
                        document.getElementById('meta_abstract_en').value = data.article.abstract_en || '';
                        document.getElementById('meta_keywords').value = data.article.keywords || '';
                        document.getElementById('meta_keywords_en').value = data.article.keywords_en || '';
                    }
                    if(data.article.authors && data.article.authors.length > 0) {
                        customAuthors = data.article.authors;
                        updateAuthorsList();
                    }
                    if(data.article.sections && data.article.sections.length > 0) {
                        customSections = data.article.sections.map(s => ({
                            type: 'general', type_name: s.title, content: s.content, level: s.level
                        }));
                        updateSectionsList();
                    }
                    if(data.article.references && data.article.references.length > 0) {
                        customReferences = data.article.references.map(r => {
                            return { 
                                authors: r.authors || r.full_citation || '', 
                                year: r.year || '', 
                                title: r.title || '', 
                                source: r.source || '', 
                                pages: r.pages || '', 
                                doi: r.doi || '', 
                                url: r.url || '' 
                            };
                        });
                        updateReferencesList();
                    }
                    if(data.article.footnotes && data.article.footnotes.length > 0) {
                        customFootnotes = data.article.footnotes;
                        updateFootnotesList();
                    }
                    
                    // Cargar Marcación de Tablas y Figuras
                    if(data.article.markup && data.article.markup.markup_data) {
                        const m = data.article.markup.markup_data;
                        if(m.tables && m.tables.length > 0) {
                            customTables = m.tables.map(t => ({
                                label: t.label || t.title || 'Tabla',
                                title: t.title || t.caption || '',
                                caption: t.caption || t.title || '',
                                html: t.html || t.content || '',
                                content: t.content || t.html || '',
                                nota: t.nota || '',
                                type: t.type || 'html',
                                src: t.src || '',
                                id: t.id || `table-${Date.now()}-${Math.random()}`
                            }));
                            updateTablesList();
                        }
                        if(m.images && m.images.length > 0) {
                            customFigures = m.images.map(i => ({
                                label: i.label || i.caption || 'Figura',
                                caption: i.caption || i.alt || '',
                                src: i.src || '',
                                nota: i.nota || '',
                                width: i.width || '100%',
                                id: i.id || `fig-${Date.now()}-${Math.random()}`
                            }));
                            updateFiguresList();
                        }
                    }
                    
                    // Mostrar contenido HTML
                    if (data.article.html_content) {
                        document.getElementById('articleContent').innerHTML = data.article.html_content;
                        
                        // Inicializar marcación de elementos
                        initializeMarkableElements();
                    } else {
                        document.getElementById('articleContent').innerHTML = '<p style="color: #6b7280;">No hay contenido HTML disponible.</p>';
                    }
                    
                    // Información lateral
                    document.getElementById('articleInfo').innerHTML = `
                        <strong>Título:</strong> ${data.article.title || 'N/A'}<br>
                        <strong>Estado:</strong> ${data.article.status}<br>
                        <strong>Subido:</strong> ${new Date(data.article.created_at).toLocaleDateString()}
                    `;
                    
                    loadHistoryVersions();
                } else {
                    showError(data.message || 'No se pudo cargar el artículo');
                }
            } catch (error) {
                showError('Error de conexión: ' + error.message);
            }
        }
        
        function showError(message) {
            document.getElementById('loadingMessage').style.display = 'none';
            document.getElementById('errorMessage').style.display = 'block';
            document.getElementById('errorMessage').textContent = '❌ ' + message;
        }
        
        // Inicializar elementos marcables
        function initializeMarkableElements() {
            const content = document.getElementById('articleContent');
            
            // Encontrar todas las tablas
            const tables = content.querySelectorAll('table');
            tables.forEach((table, index) => {
                table.classList.add('markable-table');
                table.dataset.tableIndex = index;
                table.addEventListener('click', function(e) {
                    if (activeTool === 'table') {
                        e.preventDefault();
                        e.stopPropagation();
                        markTable(this);
                    }
                });
            });
            
            // Encontrar todas las imágenes
            const images = content.querySelectorAll('img');
            images.forEach((img, index) => {
                img.classList.add('markable-image');
                img.dataset.imageIndex = index;
                img.addEventListener('click', function(e) {
                    if (activeTool === 'image') {
                        e.preventDefault();
                        e.stopPropagation();
                        markImage(this);
                    }
                });
            });
        }
        
        // Activar/desactivar herramienta
        function activateTool(tool) {
            if (activeTool === tool) {
                // Desactivar
                activeTool = null;
                document.getElementById(tool + 'Tool').classList.remove('active');
            } else {
                // Desactivar otras
                if (activeTool) {
                    document.getElementById(activeTool + 'Tool').classList.remove('active');
                }
                // Activar nueva
                activeTool = tool;
                document.getElementById(tool + 'Tool').classList.add('active');
            }
        }
        
        // Marcar tabla
        function markTable(tableElement) {
            const index = tableElement.dataset.tableIndex;
            
            // Verificar si ya está marcada
            const existingIndex = markedElements.tables.findIndex(t => t.index === index);
            
            if (existingIndex >= 0) {
                // Desmarcar
                markedElements.tables.splice(existingIndex, 1);
                tableElement.classList.remove('marked');
                const badge = tableElement.querySelector('.mark-badge');
                if (badge) badge.remove();
            } else {
                // Marcar
                const caption = tableElement.querySelector('caption')?.textContent || 
                               tableElement.querySelector('th')?.textContent || 
                               'Sin título';
                
                markedElements.tables.push({
                    index: index,
                    element: tableElement,
                    caption: caption,
                    html: tableElement.outerHTML
                });
                
                tableElement.classList.add('marked');
                
                // Agregar badge
                const badge = document.createElement('span');
                badge.className = 'mark-badge';
                badge.textContent = 'Tabla ' + (markedElements.tables.length);
                tableElement.style.position = 'relative';
                tableElement.appendChild(badge);
            }
            
            updateMarkedList();
        }
        
        // ----------- GESTIÓN DE TABLAS (APA 7) -------------
        let editingItemType = ''; // 'table' o 'figure'
        let editingItemIndex = -1;

        function showTableModal(index = -1) {
            editingItemType = 'table';
            editingItemIndex = index;
            
            const modal = document.getElementById('manualItemModal');
            const title = document.getElementById('modalTitle');
            
            // Reset editors visibility
            document.getElementById('itemHtmlEditorContainer').style.display = 'block';
            document.getElementById('itemHtmlSourceContainer').style.display = 'none';
            document.getElementById('btnToggleHtml').textContent = 'Ver HTML';
            
            // Reset fields
            document.getElementById('itemLabel').value = index === -1 ? `Tabla ${customTables.length + 1}` : customTables[index].label;
            document.getElementById('itemTitle').value = index === -1 ? '' : (customTables[index].title || customTables[index].caption || '');
            document.getElementById('itemNote').value = index === -1 ? '' : (customTables[index].nota || '');
            document.getElementById('itemType').value = index === -1 ? 'html' : (customTables[index].type || 'html');
            
            // Editor content
            const htmlContent = index === -1 ? '<table><tr><td>Contenido</td></tr></table>' : (customTables[index].html || customTables[index].content || '');
            document.getElementById('itemHtmlEditor').innerHTML = htmlContent;
            
            // Image preview
            const src = index === -1 ? '' : (customTables[index].src || '');
            updateModalPreview(src);
            
            toggleItemTypeFields();
            
            title.textContent = index === -1 ? 'Añadir Tabla (APA 7)' : 'Editar Tabla';
            modal.style.display = 'flex';
        }

        function showFigureModal(index = -1) {
            editingItemType = 'figure';
            editingItemIndex = index;
            
            const modal = document.getElementById('manualItemModal');
            const title = document.getElementById('modalTitle');
            
            // Reset fields
            document.getElementById('itemLabel').value = index === -1 ? `Figura ${customFigures.length + 1}` : customFigures[index].label;
            document.getElementById('itemTitle').value = index === -1 ? '' : (customFigures[index].caption || '');
            document.getElementById('itemNote').value = index === -1 ? '' : (customFigures[index].nota || '');
            document.getElementById('itemType').value = 'image';
            document.getElementById('itemWidth').value = index === -1 ? '100%' : (customFigures[index].width || '100%');
            
            // Image preview
            const src = index === -1 ? '' : (customFigures[index].src || '');
            updateModalPreview(src);
            
            toggleItemTypeFields();
            
            title.textContent = index === -1 ? 'Añadir Figura / Imagen' : 'Editar Figura';
            modal.style.display = 'flex';
        }

        function toggleItemTypeFields() {
            const type = document.getElementById('itemType').value;
            const htmlSection = document.getElementById('htmlEditorSection');
            const imageSection = document.getElementById('imageUploadSection');
            const figureOptions = document.getElementById('figureOptions');
            
            if (editingItemType === 'table') {
                document.getElementById('itemType').style.display = 'block';
                if (type === 'html') {
                    htmlSection.style.display = 'block';
                    imageSection.style.display = 'none';
                } else {
                    htmlSection.style.display = 'none';
                    imageSection.style.display = 'block';
                }
                figureOptions.style.display = 'none';
            } else {
                document.getElementById('itemType').style.display = 'none';
                htmlSection.style.display = 'none';
                imageSection.style.display = 'block';
                figureOptions.style.display = 'block';
            }
        }

        function updateModalPreview(src) {
            const preview = document.getElementById('itemImagePreview');
            if (src) {
                preview.innerHTML = `<img src="${src}" style="max-width:100%; max-height:200px; display:block; margin:10px auto; border:1px solid #ddd;">`;
                document.getElementById('itemImageUrl').value = src;
            } else {
                preview.innerHTML = '<p style="color:#888; font-size:12px; text-align:center; padding:20px; border:1px dashed #ccc;">Sin imagen seleccionada</p>';
                document.getElementById('itemImageUrl').value = '';
            }
        }

        async function uploadItemImage(input) {
            if (!input.files || !input.files[0]) return;
            
            const file = input.files[0];
            const formData = new FormData();
            formData.append('image', file);
            formData.append('article_id', articleId);
            formData.append('action', 'upload_image');
            
            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    updateModalPreview(result.url);
                } else {
                    alert('Error al subir imagen: ' + result.message);
                }
            } catch (err) {
                console.error(err);
                alert('Error de conexión al subir imagen');
            }
        }

        function closeModal() {
            document.getElementById('manualItemModal').style.display = 'none';
        }

        function saveManualItem() {
            const label = document.getElementById('itemLabel').value;
            const title = document.getElementById('itemTitle').value;
            const note = document.getElementById('itemNote').value;
            const type = document.getElementById('itemType').value;
            
            // Si el editor de código está activo, sincronizamos al revés si fuera necesario, 
            // pero lo más seguro es leer del que esté visible
            const visual = document.getElementById('itemHtmlEditorContainer');
            let html = '';
            if (visual.style.display !== 'none') {
                html = document.getElementById('itemHtmlEditor').innerHTML;
            } else {
                html = document.getElementById('itemHtmlSource').value;
            }

            const src = document.getElementById('itemImageUrl').value;
            const width = document.getElementById('itemWidth').value;
            
            if (!label) return alert('La etiqueta es obligatoria');
            
            const item = {
                id: editingItemIndex === -1 ? (editingItemType === 'table' ? `table-${Date.now()}` : `fig-${Date.now()}`) : (editingItemType === 'table' ? customTables[editingItemIndex].id : customFigures[editingItemIndex].id),
                label: label,
                nota: note,
                type: type
            };
            
            if (editingItemType === 'table') {
                item.title = title || 'Sin título';
                if (type === 'html') {
                    item.html = html;
                    item.content = html;
                    item.src = '';
                } else {
                    item.html = '';
                    item.content = '';
                    item.src = src;
                }
                
                if (editingItemIndex === -1) {
                    customTables.push(item);
                } else {
                    customTables[editingItemIndex] = item;
                }
                updateTablesList();
            } else {
                item.caption = title || 'Sin descripción';
                item.src = src;
                item.width = width;
                
                if (editingItemIndex === -1) {
                    customFigures.push(item);
                } else {
                    customFigures[editingItemIndex] = item;
                }
                updateFiguresList();
            }
            
            closeModal();
        }

        function analyzeSmartTables() {
            const content = document.getElementById('articleContent');
            const tables = content.querySelectorAll('table');
            if (tables.length === 0) return alert('No se encontraron tablas HTML en el contenido.');
            
            let count = 0;
            tables.forEach((t, i) => {
                const label = `Tabla ${customTables.length + 1}`;
                const title = t.querySelector('caption')?.textContent || t.querySelector('th')?.textContent || 'Sin título';
                const html = t.outerHTML;
                
                customTables.push({
                    label: label,
                    title: title,
                    html: html,
                    content: html,
                    nota: '',
                    id: `table-${Date.now()}-${i}`
                });
                count++;
            });
            updateTablesList();
            alert(`✅ Se han extraído e identificado ${count} tablas.`);
        }

        function addTableItemManual() {
            showTableModal();
        }

        function editTable(i) {
            showTableModal(i);
        }

        function updateTablesList() {
            const list = document.getElementById('smartTablesList');
            if (customTables.length === 0) {
                list.innerHTML = '<p style="color: #6b7280; font-style:italic;">No hay tablas marcadas.</p>';
                return;
            }
            list.innerHTML = customTables.map((t, i) => {
                let noteHtml = (t.nota && t.nota.trim()) ? `<div style="font-size:9px; color:#059669; font-style:italic; margin-top:2px;">📝 ${t.nota.substring(0, 30)}${t.nota.length > 30 ? '...' : ''}</div>` : '';
                return `
                <div style="background:#f9fafb; border:1px solid #e5e7eb; padding:8px; border-radius:4px;">
                    <div style="font-weight:bold; color:#2563eb;">${t.label}</div>
                    <div style="font-size:10px; color:#4b5563;">${t.title || t.caption || ''}</div>
                    ${noteHtml}
                    <div style="display:flex; gap:3px; margin-top:5px;">
                        <button onclick="editTable(${i})" style="flex:1; padding:2px; font-size:10px; background:#eff6ff; border:1px solid #bfdbfe; cursor:pointer;">✏️ Edit</button>
                        <button onclick="customTables.splice(${i},1); updateTablesList();" style="flex:1; padding:2px; font-size:10px; background:#fef2f2; border:1px solid #fecaca; color:#dc2626; cursor:pointer;">🗑️ Borrar</button>
                    </div>
                </div>
            `;}).join('');
        }

        // ----------- GESTIÓN DE FIGURAS -------------
        function analyzeSmartFigures() {
            const content = document.getElementById('articleContent');
            const imgs = content.querySelectorAll('img');
            if (imgs.length === 0) return alert('No se encontraron imágenes en el contenido.');
            
            let count = 0;
            imgs.forEach((img, i) => {
                const label = `Figura ${customFigures.length + 1}`;
                const caption = img.alt || img.title || 'Sin descripción';
                
                customFigures.push({
                    label: label,
                    caption: caption,
                    src: img.src,
                    nota: '',
                    id: `fig-${Date.now()}-${i}`
                });
                count++;
            });
            updateFiguresList();
            alert(`✅ Se han identificado ${count} figuras.`);
        }

        function addFigureItemManual() {
            showFigureModal();
        }

        function editFigure(i) {
            showFigureModal(i);
        }

        function updateFiguresList() {
            const list = document.getElementById('smartFiguresList');
            if (customFigures.length === 0) {
                list.innerHTML = '<p style="color: #6b7280; font-style:italic;">No hay figuras marcadas.</p>';
                return;
            }
            list.innerHTML = customFigures.map((f, i) => {
                let noteHtml = (f.nota && f.nota.trim()) ? `<div style="font-size:9px; color:#059669; font-style:italic; margin-top:2px;">📝 ${f.nota.substring(0, 30)}${f.nota.length > 30 ? '...' : ''}</div>` : '';
                return `
                <div style="background:#f0fdf4; border:1px solid #bbf7d0; padding:8px; border-radius:4px;">
                    <div style="font-weight:bold; color:#166534;">${f.label}</div>
                    <div style="font-size:10px; color:#4b5563;">${f.caption || f.alt || ''}</div>
                    ${noteHtml}
                    <div style="display:flex; gap:3px; margin-top:5px;">
                        <button onclick="editFigure(${i})" style="flex:1; padding:2px; font-size:10px; background:#f0fdf4; border:1px solid #bbf7d0; cursor:pointer;">✏️ Edit</button>
                        <button onclick="customFigures.splice(${i},1); updateFiguresList();" style="flex:1; padding:2px; font-size:10px; background:#fef2f2; border:1px solid #fecaca; color:#dc2626; cursor:pointer;">🗑️ Borrar</button>
                    </div>
                </div>
            `;}).join('');
        }
        
        // Actualizar lista de elementos marcados
        function updateMarkedList() {
            const total = markedElements.tables.length + markedElements.images.length;
            document.getElementById('markedCount').textContent = total;
            
            const listDiv = document.getElementById('markedItemsList');
            
            if (total === 0) {
                listDiv.innerHTML = '<p style="color: #6b7280; font-size: 13px;">No hay elementos marcados aún</p>';
                return;
            }
            
            let html = '';
            
            // Tablas
            markedElements.tables.forEach((table, idx) => {
                html += `
                    <div class="marked-item">
                        <div class="item-type">📊 Tabla ${idx + 1}</div>
                        <div class="item-caption">${table.caption.substring(0, 50)}...</div>
                        <button onclick="unmarkTable(${idx})">Eliminar</button>
                    </div>
                `;
            });
            
            // Imágenes
            markedElements.images.forEach((img, idx) => {
                html += `
                    <div class="marked-item">
                        <div class="item-type">🖼️ Figura ${idx + 1}</div>
                        <div class="item-caption">${img.alt.substring(0, 50)}...</div>
                        <button onclick="unmarkImage(${idx})">Eliminar</button>
                    </div>
                `;
            });
            
            listDiv.innerHTML = html;
        }
        
        // Desmarcar tabla por índice
        function unmarkTable(idx) {
            const table = markedElements.tables[idx];
            if (table && table.element) {
                table.element.classList.remove('marked');
                const badge = table.element.querySelector('.mark-badge');
                if (badge) badge.remove();
            }
            markedElements.tables.splice(idx, 1);
            updateMarkedList();
        }
        
        // Desmarcar imagen por índice
        function unmarkImage(idx) {
            const img = markedElements.images[idx];
            if (img && img.element) {
                img.element.classList.remove('marked');
                const badge = img.element.parentElement.querySelector('.mark-badge');
                if (badge) badge.remove();
            }
            markedElements.images.splice(idx, 1);
            updateMarkedList();
        }
        
        async function saveMarkup() {
            if (markedElements.tables.length === 0 && markedElements.images.length === 0) {
                alert('No hay elementos marcados para guardar.');
                return;
            }
            
            const markupData = {
                tables: markedElements.tables.map((t, idx) => ({
                    number: idx + 1,
                    caption: t.caption,
                    html: t.html
                })),
                images: markedElements.images.map((i, idx) => ({
                    number: idx + 1,
                    alt: i.alt,
                    src: i.src
                }))
            };
            
            try {
                const response = await fetch(`api.php?action=save_markup`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        article_id: articleId,
                        markup_data: markupData
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ Marcaciones guardadas exitosamente\n\nTablas: ' + markedElements.tables.length + '\nImágenes: ' + markedElements.images.length);
                } else {
                    alert('❌ Error al guardar: ' + (data.message || 'Error desconocido'));
                }
            } catch (error) {
                alert('❌ Error de conexión: ' + error.message);
            }
        }
        
        // Success Download Popup
        function showSuccessDownload(msg, url) {
            let fullUrl = window.location.origin + window.location.pathname.replace('editor.php', '') + url;
            let modalHTML = `<div id="dlModal" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; display:flex; align-items:center; justify-content:center;">
                <div style="background:white; padding:20px; border-radius:8px; width:450px; text-align:center; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
                    <h3 style="color:#16a34a; margin-top:0;">✅ Éxito</h3>
                    <p style="margin:10px 0; font-size:14px;">${msg}</p>
                    <p style="margin:15px 0 5px; font-size:12px; color:#555;">Puedes abrir el archivo generado de forma directa a continuación, o bien copiar este enlace:</p>
                    <input type="text" value="${fullUrl}" style="width:100%; padding:8px; margin-bottom:15px; border:1px solid #ccc; border-radius:4px; font-size:12px; box-sizing:border-box;" readonly onclick="this.select()">
                    <div style="display:flex; justify-content:center; gap:10px;">
                        <button onclick="document.body.removeChild(this.closest('#dlModal'))" style="padding:8px 15px; border:none; background:#e5e7eb; border-radius:4px; cursor:pointer;">Cerrar</button>
                        <a href="${fullUrl}" target="_blank" onclick="document.body.removeChild(this.closest('#dlModal'))" style="padding:8px 15px; border:none; background:#2563eb; color:white; border-radius:4px; cursor:pointer; text-decoration:none; font-weight:bold;">Abrir Documento</a>
                    </div>
                </div>
            </div>`;
            document.body.insertAdjacentHTML('beforeend', modalHTML);
        }

        async function generateXML() {
            if (!confirm('¿Generar archivo XML-JATS?')) return;
            
            try {
                const response = await fetch(`api.php?action=generate_xml&article_id=${articleId}`);
                const data = await response.json();
                
                if (data.success) {
                    if (data.download_url) showSuccessDownload('XML-JATS generado exitosamente', data.download_url);
                    else alert('✅ XML-JATS generado exitosamente');
                } else {
                    alert('❌ Error: ' + (data.message || 'No se pudo generar XML'));
                }
            } catch (error) {
                alert('❌ Error de conexión: ' + error.message);
            }
        }
        
        async function generateSciELO() {
            if (!confirm('¿Generar archivo XML SciELO?')) return;
            
            try {
                const response = await fetch(`api.php?action=generate_scielo&article_id=${articleId}`);
                const data = await response.json();
                
                if (data.success) {
                    if (data.download_url) showSuccessDownload('XML SciELO generado exitosamente', data.download_url);
                    else alert('✅ XML SciELO generado exitosamente');
                } else {
                    alert('❌ Error: ' + (data.message || 'No se pudo generar XML SciELO'));
                }
            } catch (error) {
                alert('❌ Error de conexión: ' + error.message);
            }
        }
        
        async function generateRedalyc() {
            if (!confirm('¿Generar archivo XML Redalyc?')) return;
            
            try {
                const response = await fetch(`api.php?action=generate_redalyc&article_id=${articleId}`);
                const data = await response.json();
                
                if (data.success) {
                    if (data.download_url) showSuccessDownload('XML Redalyc generado exitosamente', data.download_url);
                    else alert('✅ XML Redalyc generado exitosamente');
                } else {
                    alert('❌ Error: ' + (data.message || 'No se pudo generar XML Redalyc'));
                }
            } catch (error) {
                alert('❌ Error de conexión: ' + error.message);
            }
        }
        
        async function generatePDF() {
            if (!confirm('¿Generar archivo PDF?')) return;
            
            try {
                const response = await fetch(`api.php?action=generate_pdf&article_id=${articleId}`);
                const data = await response.json();
                
                if (data.success) {
                    if (data.download_url) showSuccessDownload('PDF generado exitosamente', data.download_url);
                    else alert('✅ PDF generado exitosamente\n\n' + data.message);
                } else {
                    alert('❌ Error: ' + (data.message || 'No se pudo generar PDF'));
                }
            } catch (error) {
                alert('❌ Error de conexión: ' + error.message);
            }
        }
        
        async function generateHTML() {
            if (!confirm('¿Generar archivo HTML?')) return;
            
            try {
                const response = await fetch(`api.php?action=generate_html&article_id=${articleId}`);
                const data = await response.json();
                
                if (data.success) {
                    if (data.download_url) showSuccessDownload('HTML generado exitosamente', data.download_url);
                    else alert('✅ HTML generado exitosamente');
                } else {
                    alert('❌ Error: ' + (data.message || 'No se pudo generar HTML'));
                }
            } catch (error) {
                alert('❌ Error de conexión: ' + error.message);
            }
        }
        
        async function generateEPUB() {
            if (!confirm('¿Generar archivo EPUB?')) return;
            
            try {
                const response = await fetch(`api.php?action=generate_epub&article_id=${articleId}`);
                const data = await response.json();
                
                if (data.success) {
                    if (data.download_url) showSuccessDownload('EPUB generado exitosamente', data.download_url);
                    else alert('✅ EPUB generado exitosamente');
                } else {
                    alert('❌ Error: ' + (data.message || 'No se pudo generar EPUB'));
                }
            } catch (error) {
                alert('❌ Error de conexión: ' + error.message);
            }
        }
        
        async function exportOJS() {
            if (!confirm('¿Agrupar galeras (XML, HTML, PDF, EPUB) e imágenes en un archivo ZIP para OJS?')) return;
            
            try {
                const response = await fetch(`api.php?action=generate_ojs_zip&article_id=${articleId}`);
                const data = await response.json();
                
                if (data.success) {
                    if (data.download_url) showSuccessDownload('Paquete OJS generado exitosamente', data.download_url);
                    else alert('✅ Paquete OJS generado exitosamente');
                } else {
                    alert('❌ Error: ' + (data.message || 'No se pudo generar el ZIP para OJS'));
                }
            } catch (error) {
                alert('❌ Error de conexión: ' + error.message);
            }
        }
        
        // Cargar artículo al abrir la página
        loadArticle();
    </script>
    <!-- Modal para añadir/editar tablas y figuras de forma manual -->
    <div id="manualItemModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10000; justify-content:center; align-items:center;">
        <div style="background:white; width:90%; max-width:600px; padding:25px; border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,0.2); max-height:90vh; overflow-y:auto;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid #eee; padding-bottom:10px;">
                <h2 id="modalTitle" style="margin:0; font-size:1.25rem; color:#1f2937;">Configuración de Elemento</h2>
                <button onclick="closeModal()" style="background:none; border:none; font-size:24px; cursor:pointer; color:#9ca3af;">&times;</button>
            </div>
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                <div class="form-group">
                    <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:5px;">Etiqueta (Label)</label>
                    <input type="text" id="itemLabel" style="width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px; font-size:14px;" placeholder="Ej: Tabla 1">
                </div>
                <div class="form-group">
                    <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:5px;">Título / Descripción</label>
                    <input type="text" id="itemTitle" style="width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px; font-size:14px;" placeholder="Título del elemento">
                </div>
            </div>

            <div class="form-group" style="margin-bottom:15px;">
                <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:5px;">Nota (APA 7) - Opcional</label>
                <textarea id="itemNote" style="width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px; font-size:14px; min-height:60px;" placeholder="Nota. Adaptado de..."></textarea>
            </div>

            <div class="form-group" style="margin-bottom:15px;">
                <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:5px;">Tipo de Contenido</label>
                <select id="itemType" onchange="toggleItemTypeFields()" style="width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px; font-size:14px; background-color:#fff;">
                    <option value="html">Tabla HTML (Editable)</option>
                    <option value="image">Imagen / Foto</option>
                </select>
            </div>

            <!-- Sección de Editor HTML -->
            <div id="htmlEditorSection">
                <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:5px;">Editor de Tabla (HTML)</label>
                <div style="display:flex; flex-wrap:wrap; gap:5px; margin-bottom:10px; background:#f3f4f6; padding:8px; border-radius:6px; border:1px solid #e5e7eb;">
                    <button onclick="document.execCommand('bold')" title="Negrita" style="padding:4px 10px; background:white; border:1px solid #ccc; cursor:pointer; font-weight:bold;">B</button>
                    <button onclick="document.execCommand('italic')" title="Cursiva" style="padding:4px 10px; background:white; border:1px solid #ccc; cursor:pointer; font-style:italic;">I</button>
                    <div style="width:1px; background:#ccc; margin:0 5px;"></div>
                    <button onclick="insertRow('above')" title="Insertar fila arriba" style="padding:2px 8px; font-size:11px; background:white; border:1px solid #ccc; cursor:pointer;">+ Fila ↑</button>
                    <button onclick="insertRow('below')" title="Insertar fila abajo" style="padding:2px 8px; font-size:11px; background:white; border:1px solid #ccc; cursor:pointer;">+ Fila ↓</button>
                    <button onclick="insertColumn('left')" title="Insertar columna izquierda" style="padding:2px 8px; font-size:11px; background:white; border:1px solid #ccc; cursor:pointer;">+ Col ←</button>
                    <button onclick="insertColumn('right')" title="Insertar columna derecha" style="padding:2px 8px; font-size:11px; background:white; border:1px solid #ccc; cursor:pointer;">+ Col →</button>
                    <div style="width:1px; background:#ccc; margin:0 5px;"></div>
                    <button onclick="deleteRow()" title="Eliminar fila" style="padding:2px 8px; font-size:11px; background:#fee2e2; border:1px solid #fecaca; color:#dc2626; cursor:pointer;">- Fila</button>
                    <button onclick="deleteColumn()" title="Eliminar columna" style="padding:2px 8px; font-size:11px; background:#fee2e2; border:1px solid #fecaca; color:#dc2626; cursor:pointer;">- Col</button>
                    <div style="width:1px; background:#ccc; margin:0 5px;"></div>
                    <button onclick="mergeCells('right')" title="Unir con derecha" style="padding:2px 8px; font-size:11px; background:white; border:1px solid #ccc; cursor:pointer;">Unir →</button>
                    <button onclick="mergeCells('down')" title="Unir con abajo" style="padding:2px 8px; font-size:11px; background:white; border:1px solid #ccc; cursor:pointer;">Unir ↓</button>
                    <button onclick="splitCell()" title="Dividir celda" style="padding:2px 8px; font-size:11px; background:white; border:1px solid #ccc; cursor:pointer;">Dividir</button>
                    <div style="width:1px; background:#ccc; margin:0 5px;"></div>
                    <button onclick="insertCustomTable()" title="Nueva tabla personalizada" style="padding:2px 8px; font-size:11px; background:#f5f3ff; border:1px solid #ddd6fe; color:#7c3aed; cursor:pointer; font-weight:600;">Regilla ▦</button>
                    <button onclick="applyAPAStyle()" title="Estilo APA (Bordes)" style="padding:2px 8px; font-size:11px; background:#ecfdf5; border:1px solid #a7f3d0; color:#059669; cursor:pointer; font-weight:600;">Estilo APA</button>
                    <input type="color" onchange="setCellBg(this.value)" title="Color de fondo de celda" style="width:30px; height:24px; padding:0; border:1px solid #ccc; cursor:pointer;">
                    <div style="width:1px; background:#ccc; margin:0 5px;"></div>
                    <button onclick="toggleHtmlSource()" id="btnToggleHtml" title="Ver código HTML" style="padding:2px 8px; font-size:11px; background:#eff6ff; border:1px solid #bfdbfe; color:#2563eb; cursor:pointer;">Ver HTML</button>
                </div>
                <!-- Editor Visual -->
                <div id="itemHtmlEditorContainer">
                    <div id="itemHtmlEditor" contenteditable="true" style="width:100%; min-height:180px; padding:10px; border:1px solid #d1d5db; border-radius:6px; font-size:14px; overflow-y:auto; background:#fff; outline:none;">
                    </div>
                </div>
                <!-- Editor Código -->
                <div id="itemHtmlSourceContainer" style="display:none;">
                    <textarea id="itemHtmlSource" style="width:100%; min-height:180px; padding:10px; border:1px solid #d1d5db; border-radius:6px; font-family:monospace; font-size:12px; background:#1e293b; color:#f8fafc; outline:none;"></textarea>
                </div>
            </div>

            <!-- Sección de Carga de Imagen -->
            <div id="imageUploadSection" style="display:none;">
                <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:5px;">Subir Imagen</label>
                <div style="border:2px dashed #d1d5db; border-radius:8px; padding:15px; text-align:center;">
                    <input type="file" id="itemFileUpload" onchange="uploadItemImage(this)" accept="image/*" style="display:none;">
                    <button onclick="document.getElementById('itemFileUpload').click()" style="background:#4f46e5; color:white; border:none; padding:8px 16px; border-radius:6px; cursor:pointer; font-size:13px; font-weight:500;">
                        Seleccionar Archivo
                    </button>
                    <p style="font-size:11px; color:#6b7280; margin-top:8px;">Formatos aceptados: PNG, JPG, WebP, SVG</p>
                    <input type="hidden" id="itemImageUrl">
                    <div id="itemImagePreview"></div>
                </div>
                
                <div id="figureOptions" style="margin-top:15px; display:none;">
                   <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:5px;">Ancho de visualización</label>
                   <select id="itemWidth" style="width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px; font-size:14px;">
                       <option value="100%">Ancho completo (100%)</option>
                       <option value="80%">Mediano (80%)</option>
                       <option value="60%">Pequeño (60%)</option>
                       <option value="400px">Fijo (400px)</option>
                   </select>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:25px; border-top:1px solid #eee; padding-top:15px;">
                <button onclick="closeModal()" style="padding:10px 20px; background:#f3f4f6; color:#374151; border:none; border-radius:8px; cursor:pointer; font-size:14px; font-weight:500;">Cancelar</button>
                <button onclick="saveManualItem()" style="padding:10px 25px; background:#10b981; color:white; border:none; border-radius:8px; cursor:pointer; font-size:14px; font-weight:600;">Guardar Cambios</button>
            </div>
        </div>
    </div>

    <script>
        function insertTableStructure() {
            const html = '<table style="border-collapse:collapse; width:100%; border:1px solid #ddd;"><thead><tr style="border-bottom:2px solid #000; border-top:2px solid #000;"><th style="padding:8px; text-align:left;">Cabecera 1</th><th style="padding:8px; text-align:left;">Cabecera 2</th></tr></thead><tbody><tr><td style="padding:8px; border-bottom:1px solid #ddd;">Dato A</td><td style="padding:8px; border-bottom:1px solid #ddd;">Dato B</td></tr></tbody></table>';
            const editor = document.getElementById('itemHtmlEditor');
            editor.focus();
            document.execCommand('insertHTML', false, html);
        }

        function insertCustomTable() {
            const rows = prompt("Número de filas:", 3);
            const cols = prompt("Número de columnas:", 3);
            if (!rows || !cols) return;

            let html = '<table style="border-collapse:collapse; width:100%; border:1px solid #ddd;">';
            for (let r = 0; r < rows; r++) {
                html += '<tr>';
                for (let c = 0; c < cols; c++) {
                    const tag = r === 0 ? 'th' : 'td';
                    html += `<${tag} style="padding:8px; border:1px solid #ddd; text-align:left;">&nbsp;</${tag}>`;
                }
                html += '</tr>';
            }
            html += '</table>';
            
            const editor = document.getElementById('itemHtmlEditor');
            editor.focus();
            document.execCommand('insertHTML', false, html);
        }

        // --- Funciones para manejo de tablas ---
        function getActiveCell() {
            const selection = window.getSelection();
            if (!selection.rangeCount) return null;
            let node = selection.getRangeAt(0).startContainer;
            while (node && node.nodeName !== 'TD' && node.nodeName !== 'TH' && node.nodeName !== 'TABLE' && node.id !== 'itemHtmlEditor') {
                node = node.parentNode;
            }
            return (node && (node.nodeName === 'TD' || node.nodeName === 'TH')) ? node : null;
        }

        function mergeCells(dir) {
            const cell = getActiveCell();
            if (!cell) return alert('Selecciona una celda primero');
            
            const table = cell.closest('table');
            const rowIndex = cell.parentNode.rowIndex;
            const colIndex = cell.cellIndex;
            
            if (dir === 'right') {
                const nextCell = cell.parentNode.cells[colIndex + 1];
                if (!nextCell) return alert('No hay celda a la derecha');
                
                const colspan = parseInt(cell.getAttribute('colspan') || 1);
                const nextColspan = parseInt(nextCell.getAttribute('colspan') || 1);
                
                cell.setAttribute('colspan', colspan + nextColspan);
                cell.innerHTML += ' ' + nextCell.innerHTML;
                nextCell.parentNode.removeChild(nextCell);
            } else if (dir === 'down') {
                const nextRow = table.rows[rowIndex + 1];
                if (!nextRow) return alert('No hay fila debajo');
                
                const nextCell = nextRow.cells[colIndex];
                if (!nextCell) return alert('Celda inferior no disponible (podría estar ya unida)');
                
                const rowspan = parseInt(cell.getAttribute('rowspan') || 1);
                const nextRowspan = parseInt(nextCell.getAttribute('rowspan') || 1);
                
                cell.setAttribute('rowspan', rowspan + nextRowspan);
                cell.innerHTML += ' ' + nextCell.innerHTML;
                nextCell.parentNode.removeChild(nextCell);
            }
        }

        function splitCell() {
            const cell = getActiveCell();
            if (!cell) return;
            
            const colspan = parseInt(cell.getAttribute('colspan') || 1);
            const rowspan = parseInt(cell.getAttribute('rowspan') || 1);
            
            if (colspan === 1 && rowspan === 1) return alert('La celda no está unida');
            
            const table = cell.closest('table');
            const rowIndex = cell.parentNode.rowIndex;
            const colIndex = cell.cellIndex;
            
            // Re-insertar celdas a la derecha si había colspan
            if (colspan > 1) {
                for (let i = 1; i < colspan; i++) {
                    const newCell = cell.parentNode.insertCell(colIndex + 1);
                    newCell.innerHTML = '&nbsp;';
                    newCell.style.padding = '8px';
                    newCell.style.border = '1px solid #ddd';
                }
                cell.removeAttribute('colspan');
            }
            
            // El rowspan es mucho más complejo de "separar" automáticamente en este editor básico.
            // Por simplicidad, alertamos que el split de rowspan debe ser manual o vía HTML
            if (rowspan > 1) {
                alert('La división de filas unidas debe realizarse de forma manual o mediante el código HTML para mantener la integridad de la tabla.');
                cell.removeAttribute('rowspan');
            }
        }

        function insertRow(pos) {
            const cell = getActiveCell();
            if (!cell) {
                if (document.getElementById('itemHtmlEditor').innerHTML.trim() === '') insertTableStructure();
                return;
            }
            const row = cell.parentNode;
            const table = row.parentNode.closest('table');
            const newRow = table.insertRow(pos === 'above' ? row.rowIndex : row.rowIndex + 1);
            for (let i = 0; i < row.cells.length; i++) {
                const newCell = newRow.insertCell();
                newCell.innerHTML = '&nbsp;';
                newCell.style.padding = '8px';
                newCell.style.borderBottom = '1px solid #ddd';
            }
        }

        function deleteRow() {
            const cell = getActiveCell();
            if (!cell) return;
            const row = cell.parentNode;
            const table = row.parentNode.closest('table');
            table.deleteRow(row.rowIndex);
        }

        function insertColumn(pos) {
            const cell = getActiveCell();
            if (!cell) return;
            const table = cell.parentNode.closest('table');
            const colIndex = cell.cellIndex;
            const targetIndex = pos === 'left' ? colIndex : colIndex + 1;
            
            for (let i = 0; i < table.rows.length; i++) {
                const newCell = table.rows[i].insertCell(targetIndex);
                newCell.innerHTML = '&nbsp;';
                newCell.style.padding = '8px';
                newCell.style.borderBottom = '1px solid #ddd';
            }
        }

        function deleteColumn() {
            const cell = getActiveCell();
            if (!cell) return;
            const table = cell.parentNode.closest('table');
            const colIndex = cell.cellIndex;
            for (let i = 0; i < table.rows.length; i++) {
                table.rows[i].deleteCell(colIndex);
            }
        }

        function applyAPAStyle() {
            const editor = document.getElementById('itemHtmlEditor');
            const tables = editor.querySelectorAll('table');
            tables.forEach(table => {
                table.style.borderCollapse = 'collapse';
                table.style.width = '100%';
                table.style.border = 'none';
                table.style.marginBottom = '1.5em';
                
                const cells = table.querySelectorAll('th, td');
                cells.forEach(c => {
                    c.style.border = 'none';
                    c.style.padding = '8px';
                    // Por defecto quitamos bordes internos según APA
                });

                // Cabecera: Borde superior e inferior
                const thead = table.querySelector('thead');
                if (thead) {
                    thead.querySelectorAll('tr').forEach(tr => {
                        tr.style.borderTop = '2px solid #000';
                        tr.style.borderBottom = '2px solid #000';
                    });
                } else if (table.rows.length > 0) {
                    // Si no hay thead, aplicamos al primer row
                    table.rows[0].style.borderTop = '2px solid #000';
                    table.rows[0].style.borderBottom = '2px solid #000';
                }

                // Última fila: Borde inferior
                if (table.rows.length > 0) {
                    table.rows[table.rows.length - 1].style.borderBottom = '2px solid #000';
                }
            });
        }

        function setCellBg(color) {
            const cell = getActiveCell();
            if (cell) cell.style.backgroundColor = color;
        }

        function toggleHtmlSource() {
            const visual = document.getElementById('itemHtmlEditorContainer');
            const code = document.getElementById('itemHtmlSourceContainer');
            const btn = document.getElementById('btnToggleHtml');
            const editor = document.getElementById('itemHtmlEditor');
            const textarea = document.getElementById('itemHtmlSource');

            if (visual.style.display !== 'none') {
                textarea.value = editor.innerHTML;
                visual.style.display = 'none';
                code.style.display = 'block';
                btn.textContent = 'Ver Visual';
            } else {
                editor.innerHTML = textarea.value;
                visual.style.display = 'block';
                code.style.display = 'none';
                btn.textContent = 'Ver HTML';
            }
        }

        async function loadHistoryVersions() {
            try {
                const response = await fetch(`api.php?action=list_markup_versions&article_id=${articleId}`);
                const data = await response.json();
                if (data.success && data.versions && data.versions.length > 0) {
                    const select = document.getElementById('historySelect');
                    select.innerHTML = '<option value="">Actual (Último guardado)</option>';
                    data.versions.forEach(v => {
                        const date = new Date(v.created_at).toLocaleString();
                        select.options.add(new Option(`Versión del ${date}`, v.id));
                    });
                }
            } catch (error) {
                console.error("Error cargando historial de versiones: ", error);
            }
        }

        async function restoreVersion() {
            const versionId = document.getElementById('historySelect').value;
            if (!versionId) return; // Si es 'Actual' no se recarga (ya está en la actual al cargar la página)

            if (!confirm("¿Deseas restaurar esta versión anterior? Se sobreescribirá lo que ves en pantalla en las pestañas de marcación.")) {
                document.getElementById('historySelect').value = "";
                return;
            }

            try {
                const response = await fetch(`api.php?action=restore_markup_version&markup_id=${versionId}`);
                const data = await response.json();
                if (data.success && data.markup_data) {
                    let m = data.markup_data;
                    customSections = m.sections || [];
                    customReferences = m.references || [];
                    customAuthors = m.authors || [];
                    customFootnotes = m.footnotes || [];
                    customTables = m.tables || [];
                    customFigures = m.images || [];

                    updateSectionsList();
                    updateReferencesList();
                    updateAuthorsList();
                    updateFootnotesList();
                    updateTablesList();
                    updateFiguresList();
                    alert("Versión restaurada en pantalla. Revisa las pestañas y haz clic en 'Guardar Todo' para confirmarla.");
                } else {
                    alert("Error al restaurar: " + (data.message || ""));
                }
            } catch (error) {
                alert("Error de conexión: " + error.message);
            }
        }
    </script>
</body>
</html>
