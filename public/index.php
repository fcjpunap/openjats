<?php
session_start();

if (!isset($_SESSION['user_id']) || !$_SESSION['logged_in']) {
    header('Location: login-directo.php');
    exit;
}

$userName = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Usuario');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - OpenJATS</title>
    <link rel="stylesheet" href="css/styles.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <style>
        /* Ajustes DataTables */
        .dataTables_wrapper { font-size: 13px; background: white; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb; }
        .dataTable thead th { background: #f9fafb; border-bottom: 2px solid #e5e7eb; color: #374151; }
        .dataTable tbody td { vertical-align: middle; }
    </style>
</head>
<body class="dashboard">
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>OpenJATS</h2>
        </div>
        

        <nav>
            <ul class="sidebar-menu">
                <li><a href="index.php" class="active">📊 Dashboard</a></li>
                <li><a href="configuracion.php">⚙️ Configuración</a></li>
            </ul>
        </nav>
        
        <div class="sidebar-footer">
            <div class="user-info">
                <p id="userName"><?php echo $userName; ?></p>
                <a href="logout-directo.php">Cerrar Sesión</a>
            </div>
        </div>
    </aside>
    
    <main class="main-content">
        <header class="header">
            <h1>Panel de Control</h1>
            <div class="header-actions">
                <button class="btn btn-secondary" onclick="showEditorialManager()" style="background:#f3f4f6; color:#374151; margin-right: 10px;">
                    📚 Gestor Editorial
                </button>
                <button class="btn btn-info" onclick="showOAIHarvester()" style="background:#0ea5e9; color:white; margin-right: 10px; border:none; padding:12px 24px; border-radius:6px; cursor:pointer; font-size:14px; font-weight:500;">
                    🔄 Cosechar OAI
                </button>
                <button class="btn btn-primary" onclick="showUploadDialog()">
                    + Nuevo Artículo
                </button>
            </div>
        </header>
        
        <section class="content">
            <!-- Dialog de Upload -->
            <div id="uploadSection" class="upload-section" style="display: none;">
                <div class="upload-dialog">
                    <div class="upload-dialog-header">
                        <h3 id="uploadTitle">Subir Nuevo Artículo</h3>
                        <button onclick="hideUploadDialog()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
                    </div>
                    
                    <div class="upload-zone" id="uploadZone">
                        <div class="upload-icon">📁</div>
                        <h3 id="uploadZoneTitle">Subir Artículo</h3>
                        <p>Selecciona un archivo .zip o arrástralo aquí</p>
                        <p class="upload-hint">El archivo debe contener HTML de Word con sus imágenes</p>
                        
                        <div id="upload_issue_id_container" style="margin: 15px 0; text-align: left; background:#f9fafb; padding:10px; border-radius:6px; border:1px solid #e5e7eb;">
                            <label style="font-size: 13px; font-weight: bold;">Asignar a Número de Revista (Opcional):</label>
                            <select id="upload_issue_id" style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px;">
                                <option value="">-- Sin asignar --</option>
                            </select>
                        </div>
                        
                        <input type="hidden" id="upload_existing_article_id" value="">
                        
                        <input type="file" id="articleZipInput" accept=".zip" style="display: none;">
                        
                        <button class="btn btn-primary" id="selectFileBtn">
                            Seleccionar Archivo
                        </button>
                    </div>
                    
                    <div id="uploadProgress" style="display: none; margin-top: 20px;">
                        <p id="uploadStatus">Subiendo archivo...</p>
                        <div style="background: #e5e7eb; height: 8px; border-radius: 4px; overflow: hidden;">
                            <div id="progressBar" style="background: #2563eb; height: 100%; width: 0%; transition: width 0.3s;"></div>
                        </div>
                    </div>
                    
                    <div id="debugInfo" style="margin-top: 20px; padding: 15px; background: #f3f4f6; border-radius: 6px; display: none; font-size: 12px; font-family: monospace;">
                        <strong>Debug Info:</strong>
                        <div id="debugContent"></div>
                    </div>
                </div>
            </div>

            <!-- Dialog Gestor Editorial -->
            <div id="editorialManagerModal" class="upload-section" style="display: none; z-index:9999;">
                <div class="upload-dialog" style="max-width: 600px;">
                    <div class="upload-dialog-header">
                        <h3>Gestor Editorial (Números y Secciones)</h3>
                        <button onclick="hideEditorialManager()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
                    </div>
                    <div style="padding: 15px; display:flex; gap:20px;">
                        <div style="flex:1;">
                            <h4>Crear Número</h4>
                            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; margin-bottom:15px;">
                                <div><label>Año:</label><input type="text" id="em_year" class="form-control" style="width:100%; padding:8px; display:block;" placeholder="Ej: 2024"></div>
                                <div><label>Volumen:</label><input type="text" id="em_vol" class="form-control" style="width:100%; padding:8px; display:block;" placeholder="Ej: 14"></div>
                                <div><label>Fascículo:</label><input type="text" id="em_iss" class="form-control" style="width:100%; padding:8px; display:block;" placeholder="Ej: 2"></div>
                            </div>
                            <button class="btn btn-primary" style="width:100%; margin-bottom:20px;" onclick="createIssue()">+ Crear Número de Revista</button>
                            
                            <h4>Números Existentes</h4>
                            <div id="managerIssuesList" style="max-height: 250px; overflow-y:auto; border:1px solid #ccc; padding:10px; font-size:12px;">Cargando...</div>
                        </div>
                        <div style="flex:1; border-left:1px solid #ccc; padding-left:20px;">
                            <h4>Crear Sección</h4>
                            <div style="margin-bottom:15px;">
                                <label>Nombre de la Sección (Ej: ARTÍCULOS DE INVESTIGACIÓN):</label><br><br>
                                <input type="text" id="em_sec" class="form-control" style="width:100%; padding:8px; display:block;">
                            </div>
                            <button class="btn btn-primary" style="width:100%; margin-bottom:20px;" onclick="createSection()">+ Crear Sección de Revista</button>
                            
                            <h4>Secciones Existentes</h4>
                            <div id="managerSectionsList" style="max-height: 250px; overflow-y:auto; border:1px solid #ccc; padding:10px; font-size:12px;">Cargando...</div>
                </div>
            </div>
            <!-- Dialog OAI Harvester -->
            <!-- Dialog OAI Harvester -->
            <div id="oaiHarvesterModal" class="upload-section" style="display: none; z-index:9999;">
                <div class="upload-dialog" style="max-width: 500px;">
                    <div class="upload-dialog-header">
                        <h3>Cosechar Metadatos OAI-PMH</h3>
                        <button onclick="hideOAIHarvester()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
                    </div>
                    <div style="padding: 15px;">
                        <p style="font-size: 13px; color: #4b5563; margin-bottom: 15px;">
                            Introduce la URL base del OAI-PMH de tu revista OJS para importar automáticamente los volúmenes, fascículos, secciones y títulos de manuscritos como pendientes (permitiéndote luego subir su HTML y empaquetarlos).<br><br>
                            Ejemplo: <code>https://tu-revista.com/index.php/journal/oai</code>
                        </p>
                        <div style="margin-bottom: 15px;">
                            <label style="font-weight: bold; font-size: 14px; margin-bottom: 5px; display:block;">OAI Endpoint URL:</label>
                            <input type="url" id="oai_endpoint" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;" placeholder="https://...">
                        </div>
                        <div id="oaiProgress" style="display:none; margin:15px 0;">
                            <p id="oaiStatus" style="font-size:13px; font-weight:bold; color:#2563eb;">Conectando e importando...</p>
                            <progress style="width:100%; height:10px;"></progress>
                        </div>
                        <button id="btnHarvest" class="btn btn-primary" style="width:100%; margin-top:10px;" onclick="startOAIHarvest()">Cosechar Artículos</button>
                        <button class="btn" style="width:100%; margin-top:10px; background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5;" onclick="cleanOAI()">🗑️ Limpiar Cosecha (Reiniciar)</button>
                    </div>
                </div>
            </div>
            
            <!-- Lista de artículos -->
            <div class="articles-section" style="margin-bottom:40px;">
                <div class="section-header" style="display:flex; justify-content:space-between; align-items:center;">
                    <h2>Manuscritos Pendientes de Agrupar</h2>
                </div>
                <div class="table-responsive">
                    <table id="pendingArticlesTable" class="display" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Título</th>
                                <th>Fecha de Subida</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <div class="articles-section">
                <div class="section-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                    <h2>Manuscritos Agrupados (En su Número y Sección)</h2>
                    <div style="display:flex; gap:10px; font-size:13px;">
                        <select id="filter_issue" style="padding:6px 10px; border:1px solid #ccc; border-radius:4px; background:#fff; min-width:180px;">
                            <option value="">Todos los Números</option>
                        </select>
                        <select id="filter_section" style="padding:6px 10px; border:1px solid #ccc; border-radius:4px; background:#fff; min-width:180px;">
                            <option value="">Todas las Secciones</option>
                        </select>
                        <button class="btn btn-primary" onclick="generateTOC()" style="background:#10b981; border:1px solid #059669; padding:6px 12px; cursor:pointer;"><span style="margin-right:5px;">📄</span> Índice PDF (Scopus)</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="groupedArticlesTable" class="display" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Título</th>
                                <th>Vol/Núm</th>
                                <th>Sección</th>
                                <th>Fecha de Subida</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <!-- Modal for Assigning -->
            <div id="assignModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); align-items:center; justify-content:center; z-index:999;">
                <div style="background:white; padding:30px; border-radius:10px; width:400px; max-width:90%;">
                    <h3>Asignar Número y Sección</h3>
                    <input type="hidden" id="assign_article_id" value="">
                    
                    <label style="display:block; margin:15px 0 5px;">Número Editorial:</label>
                    <select id="assign_issue_id" style="width:100%; padding:10px; text-transform:uppercase;">
                        <option value="">Cargando números...</option>
                    </select>

                    <label style="display:block; margin:15px 0 5px;">Sección de la Revista:</label>
                    <select id="assign_article_type" style="width:100%; padding:10px; text-transform:uppercase;">
                        <option value="">-- Sin Sección --</option>
                    </select>
                    
                    <div style="margin-top:20px; text-align:right;">
                        <button onclick="document.getElementById('assignModal').style.display='none'" style="cursor:pointer; padding:10px; border:none; background:#ccc; margin-right:10px; border-radius:5px;">Cancelar</button>
                        <button onclick="confirmAssign()" style="cursor:pointer; padding:10px; border:none; background:#2563eb; color:white; border-radius:5px;">Agrupar Manuscrito</button>
                    </div>
                </div>
            </div>
            
            <!-- Modal for Assigning Template -->
            <div id="assignTemplateModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); align-items:center; justify-content:center; z-index:999;">
                <div style="background:white; padding:30px; border-radius:10px; width:400px; max-width:90%;">
                    <h3>Asignar Plantilla</h2>
                    <input type="hidden" id="assign_template_article_id" value="">
                    
                    <label style="display:block; margin:15px 0 5px;">Seleccionar Plantilla:</label>
                    <select id="assign_template_id" style="width:100%; padding:10px;">
                        <option value="">Cargando plantillas...</option>
                    </select>
                    
                    <div style="margin-top:20px; text-align:right;">
                        <button onclick="document.getElementById('assignTemplateModal').style.display='none'" style="cursor:pointer; padding:10px; border:none; background:#ccc; margin-right:10px; border-radius:5px;">Cancelar</button>
                        <button onclick="confirmTemplateAssign()" style="cursor:pointer; padding:10px; border:none; background:#2563eb; color:white; border-radius:5px;">Aplicar Plantilla</button>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    <!-- jQuery and DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script>
        // Debug mode
        const DEBUG = true;
        
        function log(msg) {
            if (DEBUG) {
                console.log('[Upload Debug]', msg);
                const debugContent = document.getElementById('debugContent');
                if (debugContent) {
                    debugContent.innerHTML += '<br>' + new Date().toLocaleTimeString() + ': ' + msg;
                    document.getElementById('debugInfo').style.display = 'block';
                }
            }
        }
        
        function showUploadDialog() {
            let existingIdEl = document.getElementById('upload_existing_article_id');
            if (existingIdEl) existingIdEl.value = '';
            let issueCont = document.getElementById('upload_issue_id_container');
            if (issueCont) issueCont.style.display = 'block';
            let titleEl = document.getElementById('uploadTitle');
            if (titleEl) titleEl.innerText = 'Subir Nuevo Artículo';
            
            log('Mostrando dialog de upload');
            document.getElementById('uploadSection').style.display = 'flex';
        }
        
        function openReplaceZip(artId) {
            let existingIdEl = document.getElementById('upload_existing_article_id');
            if (existingIdEl) existingIdEl.value = artId;
            let issueCont = document.getElementById('upload_issue_id_container');
            if (issueCont) issueCont.style.display = 'none';
            let titleEl = document.getElementById('uploadTitle');
            if (titleEl) titleEl.innerText = 'Subir ZIP (Actualizar Artículo ' + artId + ')';
            
            log('Mostrando dialog de upload para articulo existente ' + artId);
            document.getElementById('uploadSection').style.display = 'flex';
        }
        
        function hideUploadDialog() {
            log('Ocultando dialog de upload');
            document.getElementById('uploadSection').style.display = 'none';
            document.getElementById('debugInfo').style.display = 'none';
            document.getElementById('debugContent').innerHTML = '';
        }
        
        // Referencias a elementos
        const fileInput = document.getElementById('articleZipInput');
        const uploadZone = document.getElementById('uploadZone');
        const selectFileBtn = document.getElementById('selectFileBtn');
        
        log('Script cargado. Elementos encontrados: fileInput=' + !!fileInput + ', uploadZone=' + !!uploadZone + ', selectFileBtn=' + !!selectFileBtn);
        
        // Botón de seleccionar archivo
        selectFileBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            log('Click en botón Seleccionar Archivo');
            fileInput.click();
        });
        
        // Cuando se selecciona un archivo
        fileInput.addEventListener('change', function(e) {
            log('Evento change disparado en input file');
            const file = e.target.files[0];
            if (file) {
                log('Archivo seleccionado: ' + file.name + ' (' + file.size + ' bytes)');
                uploadArticle(file);
            } else {
                log('No se seleccionó ningún archivo');
            }
        });
        
        // Drag and drop
        uploadZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            uploadZone.style.borderColor = '#2563eb';
            uploadZone.style.background = '#eff6ff';
        });
        
        uploadZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            uploadZone.style.borderColor = '#d1d5db';
            uploadZone.style.background = 'transparent';
        });
        
        uploadZone.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            uploadZone.style.borderColor = '#d1d5db';
            uploadZone.style.background = 'transparent';
            
            log('Archivo arrastrado');
            const file = e.dataTransfer.files[0];
            if (file && file.name.endsWith('.zip')) {
                log('Archivo ZIP válido: ' + file.name);
                uploadArticle(file);
            } else {
                log('Archivo no válido: ' + (file ? file.name : 'ninguno'));
                alert('Por favor selecciona un archivo .zip');
            }
        });
        
        // Función de upload
        async function uploadArticle(file) {
            log('Iniciando upload de: ' + file.name);
            
            const progressDiv = document.getElementById('uploadProgress');
            const progressBar = document.getElementById('progressBar');
            const statusText = document.getElementById('uploadStatus');
            
            progressDiv.style.display = 'block';
            progressBar.style.width = '10%';
            statusText.textContent = 'Preparando archivo...';
            
            const formData = new FormData();
            formData.append('article_zip', file);
            
            const issueId = document.getElementById('upload_issue_id');
            if(issueId && issueId.value) formData.append('issue_id', issueId.value);
            
            const existingId = document.getElementById('upload_existing_article_id');
            if(existingId && existingId.value) formData.append('existing_article_id', existingId.value);
            
            log('FormData creado, enviando a api.php');
            
            try {
                progressBar.style.width = '30%';
                statusText.textContent = 'Subiendo archivo...';
                
                log('Haciendo fetch a api.php?action=upload_article');
                
                const response = await fetch('api.php?action=upload_article', {
                    method: 'POST',
                    body: formData
                });
                
                log('Respuesta recibida. Status: ' + response.status);
                
                progressBar.style.width = '60%';
                statusText.textContent = 'Procesando...';
                
                const responseText = await response.text();
                log('Respuesta RAW: ' + responseText.substring(0, 200));
                
                let data;
                try {
                    data = JSON.parse(responseText);
                    log('JSON parseado correctamente');
                } catch (parseError) {
                    log('ERROR al parsear JSON: ' + parseError.message);
                    throw new Error('Respuesta no es JSON válido: ' + responseText.substring(0, 100));
                }
                
                progressBar.style.width = '100%';
                
                if (data.success) {
                    log('Upload exitoso! Article ID: ' + data.article_id);
                    statusText.textContent = '¡Completado!';
                    
                    setTimeout(function() {
                        let isUpdate = false;
                        if (existingId && existingId.value) isUpdate = true;
                        
                        if (isUpdate) {
                            alert('¡Archivo ZIP actualizado exitosamente!');
                            hideUploadDialog();
                            loadArticles(); // recargar vista
                        } else {
                            alert('¡Artículo subido exitosamente!\n\nID: ' + data.article_id);
                            hideUploadDialog();
                            window.location.href = 'editor.php?id=' + data.article_id;
                        }
                    }, 500);
                } else {
                    log('Upload falló: ' + data.message);
                    throw new Error(data.message || 'Error desconocido');
                }
                
            } catch (error) {
                log('ERROR en upload: ' + error.message);
                console.error('Error completo:', error);
                alert('Error al subir artículo:\n\n' + error.message + '\n\nRevisa la consola (F12) para más detalles.');
                progressDiv.style.display = 'none';
                progressBar.style.width = '0%';
            }
        }
        
        log('Listeners configurados correctamente');
        
        function openAssignModal(artId, issueId, artType) {
            document.getElementById('assign_article_id').value = artId;
            document.getElementById('assign_issue_id').innerHTML = document.getElementById('upload_issue_id').innerHTML; // clone options
            document.getElementById('assign_issue_id').value = issueId || '';
            document.getElementById('assign_article_type').value = artType || '';
            document.getElementById('assignModal').style.display = 'flex';
        }

        async function confirmAssign() {
            let formData = new FormData();
            formData.append('action', 'assign_issue');
            formData.append('article_id', document.getElementById('assign_article_id').value);
            formData.append('issue_id', document.getElementById('assign_issue_id').value);
            formData.append('article_type', document.getElementById('assign_article_type').value.toUpperCase());

            try {
                let res = await fetch('manager_api.php', { method: 'POST', body: formData });
                let data = await res.json();
                if(data.success) {
                    alert('Manuscrito agrupado correctamente');
                    document.getElementById('assignModal').style.display = 'none';
                    loadArticles(); // reload view
                } else alert('Error: ' + data.message);
            } catch(e) {
                alert('Error al asignar el manuscrito');
            }
        }
        
        async function openAssignTemplateModal(artId, templateId) {
            document.getElementById('assign_template_article_id').value = artId;
            let tempSelect = document.getElementById('assign_template_id');
            tempSelect.innerHTML = '<option value="">Cargando plantillas...</option>';
            document.getElementById('assignTemplateModal').style.display = 'flex';
            
            try {
                let res = await fetch('api.php?action=list_templates');
                let data = await res.json();
                if(data.success && data.templates) {
                    tempSelect.innerHTML = '<option value="">-- Ninguna --</option>';
                    data.templates.forEach(t => {
                        let opt = document.createElement('option');
                        opt.value = t.id;
                        opt.textContent = t.name;
                        if(t.id == templateId) opt.selected = true;
                        tempSelect.appendChild(opt);
                    });
                } else {
                    tempSelect.innerHTML = '<option value="">No hay plantillas</option>';
                }
            } catch(e) {
                tempSelect.innerHTML = '<option value="">Error cargando plantillas</option>';
            }
        }
        
        async function confirmTemplateAssign() {
            let formData = new FormData();
            formData.append('action', 'assign_template');
            formData.append('article_id', document.getElementById('assign_template_article_id').value);
            formData.append('template_id', document.getElementById('assign_template_id').value);

            try {
                let res = await fetch('manager_api.php', { method: 'POST', body: formData });
                let data = await res.json();
                if(data.success) {
                    document.getElementById('assignTemplateModal').style.display = 'none';
                    loadArticles(); // reload view
                } else alert('Error: ' + data.message);
            } catch(e) {
                alert('Error al asignar plantilla');
            }
        }

        let pendingTable = null;
        let groupedTable = null;

        async function loadArticles() {
            try {
                const response = await fetch('api.php?action=list_articles');
                const data = await response.json();
                
                if(data.success && data.articles) {
                    let pending = data.articles.filter(a => !a.issue_id || !a.article_type);
                    let grouped = data.articles.filter(a => a.issue_id && a.article_type);

                    // Preparar datos para DT Pendientes
                    let pendingData = pending.map(art => [
                        art.article_id,
                        art.title || 'Manuscrito sin título',
                        new Date(art.created_at).toLocaleDateString(),
                        `<div style="display:flex; gap:5px; flex-wrap:wrap;">
                            <button onclick="openReplaceZip(${art.id})" class="btn" style="border-radius:4px; padding:5px; font-size:12px; background:#10b981; border:1px solid #059669; color:white; cursor:pointer;">📁 Subir ZIP</button>
                            <button onclick="openAssignModal(${art.id}, '${art.issue_id || ''}', '${(art.article_type || '').replace(/'/g, "\\'")}')" class="btn" style="border-radius:4px; padding:5px; font-size:12px; background:#e5e7eb; border:1px solid #ccc; cursor:pointer;">🏷️ Agrupar</button>
                            <a href="editor.php?id=${art.id}" class="btn btn-primary" style="padding:5px 10px; border-radius:4px; text-decoration:none; font-size:12px;">📝 Marcar</a>
                            <button onclick="duplicateArticle(${art.id}, 'en')" class="btn" style="border-radius:4px; padding:5px; font-size:12px; background:#fef08a; border:1px solid #ca8a04; color:#854d0e; cursor:pointer;" title="Crear una copia exacta del artículo y marcación para el idioma inglés">🔄 Clonar (EN)</button>
                            <button onclick="deleteArticle(${art.id})" style="background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; border-radius:4px; padding:5px 10px; cursor:pointer; font-size:12px;" title="Eliminar manuscrito permanentemente">🗑️</button>
                        </div>`
                    ]);

                    // Preparar datos para DT Agrupados
                    let groupedData = grouped.map(art => {
                        let volNum = 'Falta';
                        if (art.volume_number && art.issue_number) {
                            volNum = 'Vol. ' + art.volume_number + ' Núm. ' + art.issue_number;
                            if (art.year) volNum += ' (' + art.year + ')';
                        } else if (art.issue_id) {
                            volNum = 'ID ' + art.issue_id;
                        }
                        
                        return [
                            art.article_id,
                            art.title || 'Manuscrito sin título',
                            volNum,
                            art.article_type || 'Falta',
                        new Date(art.created_at).toLocaleDateString(),
                        `<div style="display:flex; gap:5px; flex-wrap:wrap;">
                            <button onclick="openReplaceZip(${art.id})" class="btn" style="border-radius:4px; padding:5px; font-size:12px; background:#10b981; border:1px solid #059669; color:white; cursor:pointer;">📁 Subir ZIP</button>
                            <button onclick="openAssignModal(${art.id}, '${art.issue_id || ''}', '${(art.article_type || '').replace(/'/g, "\\'")}')" class="btn" style="border-radius:4px; padding:5px; font-size:12px; background:#e5e7eb; border:1px solid #ccc; cursor:pointer;">🏷️ Reasignar</button>
                            <button onclick="openAssignTemplateModal(${art.id}, ${art.template_id || 'null'})" class="btn" style="border-radius:4px; padding:5px; font-size:12px; background:#dbeafe; border:1px solid #bfdbfe; color:#1e40af; cursor:pointer;">📄 Plantilla</button>
                            <a href="editor.php?id=${art.id}" class="btn btn-primary" style="padding:5px 10px; border-radius:4px; text-decoration:none; font-size:12px;">📝 Marcar</a>
                            <button onclick="duplicateArticle(${art.id}, 'en')" class="btn" style="border-radius:4px; padding:5px; font-size:12px; background:#fef08a; border:1px solid #ca8a04; color:#854d0e; cursor:pointer;" title="Crear una copia exacta del artículo y marcación para el idioma inglés">🔄 Clonar (EN)</button>
                            <button onclick="deleteArticle(${art.id})" style="background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; border-radius:4px; padding:5px 10px; cursor:pointer; font-size:12px;" title="Eliminar manuscrito permanentemente">🗑️</button>
                        </div>`
                        ];
                    });

                    // Inicializar o actualizar DataTables Pendientes
                    if (pendingTable) {
                        pendingTable.clear().rows.add(pendingData).draw();
                    } else {
                        pendingTable = $('#pendingArticlesTable').DataTable({
                            data: pendingData,
                            language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
                            pageLength: 5,
                            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]]
                        });
                    }

                    // Inicializar o actualizar DataTables Agrupados
                    if (groupedTable) {
                        groupedTable.clear().rows.add(groupedData).draw();
                    } else {
                        groupedTable = $('#groupedArticlesTable').DataTable({
                            data: groupedData,
                            language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
                            pageLength: 5,
                            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]]
                        });
                        
                        // Configurar listeners para filtros
                        $('#filter_issue').on('change', function() {
                            let val = $(this).val();
                            groupedTable.column(2).search(val ? '^' + $.fn.dataTable.util.escapeRegex(val) + '$' : '', true, false).draw();
                        });
                        $('#filter_section').on('change', function() {
                            let val = $(this).val();
                            groupedTable.column(3).search(val ? '^' + $.fn.dataTable.util.escapeRegex(val) + '$' : '', true, false).draw();
                        });
                    }

                    // Actualizar opciones de los selectores dinámicamente según los datos disponibles
                    let uniqueIssues = [...new Set(groupedData.map(item => item[2]))].sort();
                    let uniqueSections = [...new Set(groupedData.map(item => item[3]))].sort();
                    
                    let issueSel = $('#filter_issue');
                    let currentIssueVal = issueSel.val();
                    issueSel.empty().append('<option value="">Todos los Números</option>');
                    uniqueIssues.forEach(i => issueSel.append(new Option(i, i)));
                    if (uniqueIssues.includes(currentIssueVal)) { issueSel.val(currentIssueVal); } else { groupedTable.column(2).search('').draw(); }
                    
                    let secSel = $('#filter_section');
                    let currentSecVal = secSel.val();
                    secSel.empty().append('<option value="">Todas las Secciones</option>');
                    uniqueSections.forEach(s => secSel.append(new Option(s, s)));
                    if (uniqueSections.includes(currentSecVal)) { secSel.val(currentSecVal); } else { groupedTable.column(3).search('').draw(); }
                    
                    // Re-aplicar filtros si existían
                    if (issueSel.val()) issueSel.trigger('change');
                    if (secSel.val()) secSel.trigger('change');
                }
            } catch(e) {
                console.error(e);
            }
        }
        
        function generateTOC() {
            if (!groupedTable) return;
            let currentData = groupedTable.rows({ filter: 'applied' }).data().toArray();
            let ids = currentData.map(row => row[0]); // article_id
            
            if (ids.length === 0) {
                alert("No hay artículos en la lista filtrada.");
                return;
            }
            
            let form = document.createElement('form');
            form.method = 'POST';
            form.action = 'api.php';
            form.target = '_blank';
            
            let actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'generate_toc_pdf';
            form.appendChild(actionInput);
            
            let idsInput = document.createElement('input');
            idsInput.type = 'hidden';
            idsInput.name = 'article_ids';
            idsInput.value = JSON.stringify(ids);
            form.appendChild(idsInput);
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }

        // --- OAI Harvester JS ---
        function showOAIHarvester() {
            // Try fetching configured OAI URL when opening the modal
            fetch('api.php?action=get_oai_url')
                .then(r=>r.json())
                .then(d=>{
                    if(d.success && d.oai_url) {
                        document.getElementById('oai_endpoint').value = d.oai_url;
                    }
                }).catch(e=>console.log(e));
            document.getElementById('oaiHarvesterModal').style.display = 'flex';
        }
        function hideOAIHarvester() {
            document.getElementById('oaiHarvesterModal').style.display = 'none';
        }

        async function startOAIHarvest(token = '') {
            let url = document.getElementById('oai_endpoint').value;
            if(!url) return alert('Ingresa la URL del endpoint OAI');
            
            document.getElementById('oaiProgress').style.display = 'block';
            document.getElementById('oaiStatus').innerText = token ? "Cosechando lote OAI (" + token + ")..." : "Iniciando cosecha del OAI. Esto puede demorar varios segundos...";
            document.getElementById('btnHarvest').disabled = true;
            
            try {
                let formData = new URLSearchParams();
                formData.append('action', 'harvest_oai');
                formData.append('oai_url', url);
                if (token) formData.append('resumptionToken', token);
                
                let res = await fetch('manager_api.php', { method: 'POST', body: formData });
                let data = await res.json();
                
                if(data.success) {
                    if (data.resumptionToken) {
                        document.getElementById('oaiStatus').innerText = "Cosechado lote. Pidiendo más a OJS...";
                        await startOAIHarvest(data.resumptionToken);
                        return;
                    } else {
                        alert('Cosecha finalizada con éxito. Todos los registros integrados.');
                        hideOAIHarvester();
                        loadArticles();
                        loadIssues();
                        loadSections();
                    }
                } else alert('Error: ' + data.message);
            } catch(e) {
                alert('Error de conexión o fallo al cosechar OAI');
            }
            
            document.getElementById('oaiProgress').style.display = 'none';
            document.getElementById('btnHarvest').disabled = false;
        }

        async function cleanOAI() {
            if(!confirm("¿Deseas borrar permanentemente TODOS los manuscritos importados y vaciar las secciones para reiniciar la cosecha desde cero? (No afecta a artículos de carga manual que ya tengan otro estado)")) return;
            try {
                let formData = new URLSearchParams();
                formData.append('action', 'clean_oai');
                let res = await fetch('manager_api.php', { method: 'POST', body: formData });
                let data = await res.json();
                if(data.success) {
                    alert('Limpio: ' + data.message);
                    loadArticles();
                    loadIssues();
                    loadSections();
                } else alert('Error: ' + data.message);
            } catch(e) { alert('Error al limpiar OAI'); }
        }

        // --- Gestor Editorial JS ---
        function showEditorialManager() {
            document.getElementById('editorialManagerModal').style.display = 'flex';
            loadIssues();
        }
        function hideEditorialManager() {
            document.getElementById('editorialManagerModal').style.display = 'none';
        }
        
        function loadIssues() {
            fetch('manager_api.php?action=list_issues')
            .then(r => r.json())
            .then(data => {
                let listHtml = 'No hay números creados.';
                let selectHtml = '<option value="">-- Sin asignar --</option>';
                if(data.success && data.issues && data.issues.length > 0) {
                    listHtml = '';
                    data.issues.forEach(i => {
                        listHtml += `<div style="padding:5px; border-bottom:1px solid #eee; display:flex; justify-content:space-between;"><span>Vol. ${i.volume_number}, Núm. ${i.issue_number} (${i.year}) - <i>${i.title}</i></span> <div><button onclick="editIssue(${i.issue_id}, '${i.volume_number}', '${i.issue_number}', '${i.year}')" style="color:blue; background:none; border:none; cursor:pointer; margin-right:5px;" title="Editar">✏️</button><button onclick="deleteIssue(${i.issue_id})" style="color:red; background:none; border:none; cursor:pointer;" title="Eliminar">🗑️</button></div></div>`;
                        selectHtml += `<option value="${i.issue_id}">Vol. ${i.volume_number}, Núm. ${i.issue_number} (${i.year})</option>`;
                    });
                }
                const $list = document.getElementById('managerIssuesList');
                if($list) $list.innerHTML = listHtml;
                const $sel = document.getElementById('upload_issue_id');
                if($sel) $sel.innerHTML = selectHtml;
            }).catch(e => console.error(e));
        }
        
        function createIssue() {
            let formData = new URLSearchParams();
            formData.append('action', 'create_issue');
            formData.append('year', document.getElementById('em_year').value);
            formData.append('volume', document.getElementById('em_vol').value);
            formData.append('issue', document.getElementById('em_iss').value);
            fetch('manager_api.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if(data.success) {
                    alert('Número creado con éxito');
                    document.getElementById('em_iss').value = '';
                    loadIssues();
                } else alert('Error: ' + data.message);
            }).catch(e => alert('Error de red al crear número'));
        }

        function loadSections() {
            fetch('manager_api.php?action=list_sections')
            .then(r => r.json())
            .then(data => {
                let listHtml = 'No hay secciones creadas.';
                let optionsHtml = '<option value="">-- Sin Sección --</option>';
                if(data.success && data.sections && data.sections.length > 0) {
                    listHtml = '';
                    data.sections.forEach(s => {
                        listHtml += `<div style="padding:5px; border-bottom:1px solid #eee; display:flex; justify-content:space-between;"><i>${s.title}</i> <div><button onclick="editSection(${s.id}, '${s.title.replace(/'/g, "\\'")}')" style="color:blue; background:none; border:none; cursor:pointer; margin-right:5px;" title="Editar">✏️</button><button onclick="deleteSection(${s.id})" style="color:red; background:none; border:none; cursor:pointer;" title="Eliminar">🗑️</button></div></div>`;
                        optionsHtml += `<option value="${s.title}">${s.title}</option>`;
                    });
                }
                const $list = document.getElementById('managerSectionsList');
                if($list) $list.innerHTML = listHtml;
                const $selList = document.getElementById('assign_article_type');
                if($selList) $selList.innerHTML = optionsHtml;
            }).catch(e => console.error(e));
        }

        function createSection() {
            let title = document.getElementById('em_sec').value;
            if(!title) return alert("Ingrese el nombre de la sección");
            
            let formData = new URLSearchParams();
            formData.append('action', 'create_section');
            formData.append('title', title);
            fetch('manager_api.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if(data.success) {
                    alert('Sección creada con éxito');
                    document.getElementById('em_sec').value = '';
                    loadSections();
                } else alert('Error: ' + data.message);
            }).catch(e => alert('Error de red al crear sección'));
        }
        
        // --- DELETION ---
        function deleteArticle(id) {
            if(!confirm("¿Estás seguro de eliminar este manuscrito y todos sus archivos generados?")) return;
            fetch('manager_api.php', { method: 'POST', body: new URLSearchParams({action: 'delete_article', id: id}) })
            .then(r => r.json()).then(d => { if(d.success) loadArticles(); else alert('Error: '+d.message); });
        }
        function deleteIssue(id) {
            if(!confirm("¿Eliminar este número editorial permanentemente?")) return;
            fetch('manager_api.php', { method: 'POST', body: new URLSearchParams({action: 'delete_issue', id: id}) })
            .then(r => r.json()).then(d => { if(d.success) { loadIssues(); loadArticles(); } else alert('Error: '+d.message); });
        }
        function deleteSection(id) {
            if(!confirm("¿Eliminar esta sección permanentemente?")) return;
            fetch('manager_api.php', { method: 'POST', body: new URLSearchParams({action: 'delete_section', id: id}) })
            .then(r => r.json()).then(d => { if(d.success) { loadSections(); loadArticles(); } else alert('Error: '+d.message); });
        }
        
        function editIssue(id, vol, iss, yr) {
            let nInfo = prompt("Editar Volumen, Año, Número (separados por coma):", `${vol},${yr},${iss}`);
            if(!nInfo) return;
            let p = nInfo.split(',');
            if(p.length < 3) return alert("Debes ingresar Volumen,Año,Número separados por coma.");
            let formData = new URLSearchParams({action: 'update_issue', id: id, volume: p[0].trim(), year: p[1].trim(), issue: p[2].trim()});
            fetch('manager_api.php', { method: 'POST', body: formData }).then(r=>r.json()).then(d=>{
                if(d.success) { loadIssues(); loadArticles(); } else alert('Error: '+d.message);
            });
        }
        
        function editSection(id, title) {
            let newTitle = prompt("Editar nombre de la sección:", title);
            if(!newTitle || newTitle === title) return;
            fetch('manager_api.php', { method: 'POST', body: new URLSearchParams({action: 'update_section', id: id, title: newTitle}) })
            .then(r=>r.json()).then(d=>{ if(d.success) { loadSections(); loadArticles(); } else alert('Error: '+d.message); });
        }
        
        loadArticles();
        loadIssues();
        loadSections();
        function duplicateArticle(id, lang) {
            if (confirm("¿Crear una copia idéntica de este artículo para la versión en inglés? (Esto duplicará metadatos, marcación y archivos adjuntos)")) {
                fetch(`api.php?action=duplicate_article&article_id=${id}&lang=${lang}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert("✅ Artículo clonado exitosamente. Ahora aparece en su misma lista.");
                        loadArticles();
                    } else {
                        alert("❌ Error: " + (data.message || "No se pudo clonar"));
                    }
                })
                .catch(err => {
                    alert("Error de red: " + err);
                });
            }
        }
    </script>
</body>
</html>
