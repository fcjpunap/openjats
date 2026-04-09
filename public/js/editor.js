// Editor de marcación XML-JATS
let articleData = null;
let selectedText = '';
let currentMarkupData = {
    metadata: {},
    authors: [],
    affiliations: [],
    sections: [],
    tables: [],
    figures: [],
    references: []
};

document.addEventListener('DOMContentLoaded', async function() {
    // Verificar autenticación
    await checkAuth();
    
    // Obtener ID del artículo
    const urlParams = new URLSearchParams(window.location.search);
    const articleId = urlParams.get('id');
    
    if (!articleId) {
        showNotification('ID de artículo no especificado', 'error');
        return;
    }
    
    // Cargar artículo
    await loadArticle(articleId);
    
    // Configurar editor
    setupEditor();
});

async function loadArticle(articleId) {
    showLoading('Cargando artículo...');
    
    try {
        const response = await fetch(`api.php?action=get_article&id=${articleId}`);
        const data = await response.json();
        
        if (data.success) {
            articleData = data.article;
            
            // Cargar marcación guardada si existe
            if (articleData.markup && articleData.markup.markup_data) {
                currentMarkupData = articleData.markup.markup_data;
            }
            
            renderArticleContent();
            renderMarkupPanel();
        } else {
            showNotification('Error al cargar artículo', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    } finally {
        hideLoading();
    }
}

function setupEditor() {
    // Toolbar buttons
    document.getElementById('btnMarkTitle')?.addEventListener('click', () => markSelection('title'));
    document.getElementById('btnMarkAuthor')?.addEventListener('click', () => markSelection('author'));
    document.getElementById('btnMarkAbstract')?.addEventListener('click', () => markSelection('abstract'));
    document.getElementById('btnMarkKeywords')?.addEventListener('click', () => markSelection('keywords'));
    document.getElementById('btnMarkSection')?.addEventListener('click', () => markSelection('section'));
    document.getElementById('btnMarkReference')?.addEventListener('click', () => markSelection('reference'));
    
    // Save button
    document.getElementById('btnSave')?.addEventListener('click', saveMarkup);
    
    // Generate XML button
    document.getElementById('btnGenerateXML')?.addEventListener('click', generateXML);
    
    // Preview button
    document.getElementById('btnPreview')?.addEventListener('click', showXMLPreview);
    
    // Selection handling
    document.addEventListener('mouseup', handleTextSelection);
}

function renderArticleContent() {
    const container = document.getElementById('articleContent');
    if (!container) return;
    
    // Construir HTML del artículo
    let html = '';
    
    // Título
    if (articleData.title) {
        html += `<h1 id="article-title" class="selectable-text">${articleData.title}</h1>`;
    }
    
    // Autores
    if (articleData.authors && articleData.authors.length > 0) {
        html += '<div class="authors-section">';
        articleData.authors.forEach(author => {
            html += `
                <div class="author selectable-text" data-author-id="${author.id}">
                    <p class="author-name">${author.given_names} ${author.surname}</p>
                    ${author.affiliation ? `<p class="affiliation">${author.affiliation}</p>` : ''}
                    ${author.email ? `<p class="email">${author.email}</p>` : ''}
                    ${author.orcid ? `<p class="orcid">ORCID: ${author.orcid}</p>` : ''}
                </div>
            `;
        });
        html += '</div>';
    }
    
    // Resumen
    if (articleData.abstract) {
        html += `
            <div class="abstract-section">
                <h2>Resumen</h2>
                <p class="selectable-text">${articleData.abstract}</p>
            </div>
        `;
    }
    
    // Palabras clave
    if (articleData.keywords) {
        html += `
            <div class="keywords-section">
                <h3>Palabras clave</h3>
                <p class="selectable-text">${articleData.keywords}</p>
            </div>
        `;
    }
    
    // Secciones
    if (articleData.sections && articleData.sections.length > 0) {
        articleData.sections.forEach(section => {
            const levelClass = `level-${section.level || 1}`;
            html += `
                <div class="section ${levelClass}" data-section-id="${section.section_id}">
                    <h${section.level || 2} class="selectable-text section-title">${section.title || ''}</h${section.level || 2}>
                    <div class="section-content selectable-text">${formatContent(section.content || '')}</div>
                </div>
            `;
        });
    }
    
    // Tablas
    if (articleData.tables && articleData.tables.length > 0) {
        articleData.tables.forEach(table => {
            html += `
                <div class="table-container" data-table-id="${table.table_id}">
                    <p class="table-label">${table.label || ''}</p>
                    <p class="table-caption selectable-text">${table.caption || ''}</p>
                    <div class="table-wrapper" onclick="selectTable('${table.table_id}')">
                        ${table.html_content || ''}
                    </div>
                </div>
            `;
        });
    }
    
    // Figuras
    if (articleData.figures && articleData.figures.length > 0) {
        articleData.figures.forEach(figure => {
            html += `
                <div class="figure-container" data-figure-id="${figure.figure_id}">
                    <p class="figure-label">${figure.label || ''}</p>
                    <div class="figure-wrapper" onclick="selectFigure('${figure.figure_id}')">
                        <img src="${figure.file_path || ''}" alt="${figure.caption || ''}" />
                    </div>
                    <p class="figure-caption selectable-text">${figure.caption || ''}</p>
                </div>
            `;
        });
    }
    
    // Referencias
    if (articleData.references && articleData.references.length > 0) {
        html += '<div class="references-section"><h2>Referencias</h2>';
        articleData.references.forEach(ref => {
            html += `
                <div class="reference selectable-text" data-ref-id="${ref.ref_id}">
                    ${ref.full_citation || ''}
                </div>
            `;
        });
        html += '</div>';
    }
    
    container.innerHTML = html;
    
    // Aplicar marcaciones existentes
    applyExistingMarkup();
}

function formatContent(content) {
    // Convertir saltos de línea a párrafos
    return content.split('\n\n')
        .filter(p => p.trim())
        .map(p => `<p>${p.trim()}</p>`)
        .join('');
}

function handleTextSelection(e) {
    const selection = window.getSelection();
    const text = selection.toString().trim();
    
    if (text.length > 0) {
        selectedText = text;
        
        // Mostrar toolbar de marcación
        showMarkupToolbar(e.clientX, e.clientY);
    } else {
        hideMarkupToolbar();
    }
}

function showMarkupToolbar(x, y) {
    let toolbar = document.getElementById('selectionToolbar');
    
    if (!toolbar) {
        toolbar = document.createElement('div');
        toolbar.id = 'selectionToolbar';
        toolbar.className = 'selection-toolbar';
        toolbar.innerHTML = `
            <button class="btn btn-sm" onclick="markSelection('title')">Título</button>
            <button class="btn btn-sm" onclick="markSelection('author')">Autor</button>
            <button class="btn btn-sm" onclick="markSelection('affiliation')">Afiliación</button>
            <button class="btn btn-sm" onclick="markSelection('section')">Sección</button>
            <button class="btn btn-sm" onclick="markSelection('citation')">Cita</button>
        `;
        document.body.appendChild(toolbar);
    }
    
    toolbar.style.left = `${x}px`;
    toolbar.style.top = `${y - 50}px`;
    toolbar.style.display = 'flex';
}

function hideMarkupToolbar() {
    const toolbar = document.getElementById('selectionToolbar');
    if (toolbar) {
        toolbar.style.display = 'none';
    }
}

function markSelection(type) {
    if (!selectedText) return;
    
    switch (type) {
        case 'title':
            openTitleDialog(selectedText);
            break;
        case 'author':
            openAuthorDialog(selectedText);
            break;
        case 'affiliation':
            openAffiliationDialog(selectedText);
            break;
        case 'abstract':
            openAbstractDialog(selectedText);
            break;
        case 'keywords':
            openKeywordsDialog(selectedText);
            break;
        case 'section':
            openSectionDialog(selectedText);
            break;
        case 'reference':
            openReferenceDialog(selectedText);
            break;
        case 'citation':
            openCitationDialog(selectedText);
            break;
    }
    
    hideMarkupToolbar();
}

function openTitleDialog(text) {
    const modal = createModal('Marcar Título', `
        <div class="form-group">
            <label>Idioma</label>
            <select id="titleLang">
                <option value="es">Español</option>
                <option value="en">Inglés</option>
            </select>
        </div>
        <div class="form-group">
            <label>Título</label>
            <textarea id="titleText" rows="3">${text}</textarea>
        </div>
    `, [
        { text: 'Cancelar', class: 'btn', callback: () => modal.close() },
        { text: 'Guardar', class: 'btn btn-primary', callback: () => {
            const lang = document.getElementById('titleLang').value;
            const title = document.getElementById('titleText').value;
            
            if (lang === 'es') {
                currentMarkupData.metadata.title = title;
            } else {
                currentMarkupData.metadata.title_en = title;
            }
            
            renderMarkupPanel();
            modal.close();
            showNotification('Título marcado', 'success');
        }}
    ]);
    
    modal.show();
}

function openAuthorDialog(text) {
    const modal = createModal('Marcar Autor', `
        <div class="form-group">
            <label>Nombre completo</label>
            <input type="text" id="authorFullName" value="${text}">
        </div>
        <div class="form-group">
            <label>Nombre(s)</label>
            <input type="text" id="authorGivenNames">
        </div>
        <div class="form-group">
            <label>Apellido(s)</label>
            <input type="text" id="authorSurname">
        </div>
        <div class="form-group">
            <label>ORCID</label>
            <input type="text" id="authorOrcid" placeholder="0000-0000-0000-0000">
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" id="authorEmail">
        </div>
        <div class="form-group">
            <label>Afiliación</label>
            <input type="text" id="authorAffiliation">
        </div>
    `, [
        { text: 'Cancelar', class: 'btn', callback: () => modal.close() },
        { text: 'Guardar', class: 'btn btn-primary', callback: () => {
            const author = {
                full_name: document.getElementById('authorFullName').value,
                given_names: document.getElementById('authorGivenNames').value,
                surname: document.getElementById('authorSurname').value,
                orcid: document.getElementById('authorOrcid').value,
                email: document.getElementById('authorEmail').value,
                affiliation: document.getElementById('authorAffiliation').value,
                author_order: currentMarkupData.authors.length + 1
            };
            
            currentMarkupData.authors.push(author);
            renderMarkupPanel();
            modal.close();
            showNotification('Autor agregado', 'success');
        }}
    ]);
    
    modal.show();
}

function selectTable(tableId) {
    const table = articleData.tables.find(t => t.table_id === tableId);
    if (!table) return;
    
    const modal = createModal('Editar Tabla', `
        <div class="form-group">
            <label>Etiqueta</label>
            <input type="text" id="tableLabel" value="${table.label || ''}">
        </div>
        <div class="form-group">
            <label>Título/Descripción</label>
            <textarea id="tableCaption" rows="2">${table.caption || ''}</textarea>
        </div>
        <div class="form-group">
            <label>Notas al pie</label>
            <textarea id="tableFooter" rows="2">${table.footer || ''}</textarea>
        </div>
        <div class="table-preview">
            ${table.html_content}
        </div>
    `, [
        { text: 'Cancelar', class: 'btn', callback: () => modal.close() },
        { text: 'Guardar', class: 'btn btn-primary', callback: () => {
            // Actualizar tabla en currentMarkupData
            const existingIndex = currentMarkupData.tables.findIndex(t => t.table_id === tableId);
            const updatedTable = {
                ...table,
                label: document.getElementById('tableLabel').value,
                caption: document.getElementById('tableCaption').value,
                footer: document.getElementById('tableFooter').value
            };
            
            if (existingIndex >= 0) {
                currentMarkupData.tables[existingIndex] = updatedTable;
            } else {
                currentMarkupData.tables.push(updatedTable);
            }
            
            renderMarkupPanel();
            modal.close();
            showNotification('Tabla actualizada', 'success');
        }}
    ]);
    
    modal.show();
}

function selectFigure(figureId) {
    const figure = articleData.figures.find(f => f.figure_id === figureId);
    if (!figure) return;
    
    const modal = createModal('Editar Figura', `
        <div class="form-group">
            <label>Etiqueta</label>
            <input type="text" id="figureLabel" value="${figure.label || ''}">
        </div>
        <div class="form-group">
            <label>Descripción</label>
            <textarea id="figureCaption" rows="3">${figure.caption || ''}</textarea>
        </div>
        <div class="figure-preview">
            <img src="${figure.file_path}" style="max-width: 100%;">
        </div>
    `, [
        { text: 'Cancelar', class: 'btn', callback: () => modal.close() },
        { text: 'Guardar', class: 'btn btn-primary', callback: () => {
            const existingIndex = currentMarkupData.figures.findIndex(f => f.figure_id === figureId);
            const updatedFigure = {
                ...figure,
                label: document.getElementById('figureLabel').value,
                caption: document.getElementById('figureCaption').value
            };
            
            if (existingIndex >= 0) {
                currentMarkupData.figures[existingIndex] = updatedFigure;
            } else {
                currentMarkupData.figures.push(updatedFigure);
            }
            
            renderMarkupPanel();
            modal.close();
            showNotification('Figura actualizada', 'success');
        }}
    ]);
    
    modal.show();
}

function renderMarkupPanel() {
    const panel = document.getElementById('markupPanel');
    if (!panel) return;
    
    let html = '<h3>Elementos Marcados</h3>';
    
    // Metadata
    if (currentMarkupData.metadata) {
        html += '<div class="markup-section"><h4>Metadatos</h4>';
        if (currentMarkupData.metadata.title) {
            html += `<p><strong>Título (ES):</strong> ${currentMarkupData.metadata.title}</p>`;
        }
        if (currentMarkupData.metadata.title_en) {
            html += `<p><strong>Título (EN):</strong> ${currentMarkupData.metadata.title_en}</p>`;
        }
        html += '</div>';
    }
    
    // Authors
    if (currentMarkupData.authors.length > 0) {
        html += '<div class="markup-section"><h4>Autores</h4><ul>';
        currentMarkupData.authors.forEach((author, index) => {
            html += `<li>${author.given_names} ${author.surname} 
                     <button class="btn btn-sm" onclick="editAuthor(${index})">✏️</button>
                     <button class="btn btn-sm btn-danger" onclick="removeAuthor(${index})">🗑️</button>
                     </li>`;
        });
        html += '</ul></div>';
    }
    
    // Tables
    if (currentMarkupData.tables.length > 0) {
        html += `<div class="markup-section"><h4>Tablas (${currentMarkupData.tables.length})</h4></div>`;
    }
    
    // Figures
    if (currentMarkupData.figures.length > 0) {
        html += `<div class="markup-section"><h4>Figuras (${currentMarkupData.figures.length})</h4></div>`;
    }
    
    // References
    if (currentMarkupData.references.length > 0) {
        html += `<div class="markup-section"><h4>Referencias (${currentMarkupData.references.length})</h4></div>`;
    }
    
    panel.innerHTML = html;
}

async function saveMarkup() {
    showLoading('Guardando marcación...');
    
    try {
        const response = await fetch('api.php?action=save_markup', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                article_id: articleData.id,
                markup_data: currentMarkupData
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Marcación guardada exitosamente', 'success');
        } else {
            showNotification('Error al guardar marcación', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    } finally {
        hideLoading();
    }
}

async function generateXML() {
    showLoading('Generando XML-JATS...');
    
    try {
        // Primero guardar marcación
        await saveMarkup();
        
        // Luego generar XML
        const response = await fetch(`api.php?action=generate_xml&article_id=${articleData.id}`);
        const data = await response.json();
        
        if (data.success) {
            showNotification('XML-JATS generado exitosamente', 'success');
            
            // Mostrar URL del archivo
            const modal = createModal('XML-JATS Generado', `
                <p>El archivo XML-JATS ha sido generado exitosamente.</p>
                <p><strong>URL:</strong> <a href="${data.url}" target="_blank">${data.url}</a></p>
            `, [
                { text: 'Cerrar', class: 'btn btn-primary', callback: () => modal.close() }
            ]);
            
            modal.show();
        } else {
            showNotification(data.message || 'Error al generar XML', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    } finally {
        hideLoading();
    }
}

function showXMLPreview() {
    // Aquí se generaría una vista previa del XML
    showNotification('Vista previa en desarrollo', 'info');
}

function removeAuthor(index) {
    currentMarkupData.authors.splice(index, 1);
    renderMarkupPanel();
    showNotification('Autor eliminado', 'success');
}

function applyExistingMarkup() {
    // Aplicar estilos visuales a elementos ya marcados
    // Esto resaltará los elementos que ya tienen marcación
}
