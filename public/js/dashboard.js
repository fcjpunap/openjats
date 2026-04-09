// Dashboard principal
let currentUser = null;

document.addEventListener('DOMContentLoaded', async function() {
    // Verificar autenticación
    currentUser = await checkAuth();
    
    if (currentUser) {
        updateUserInterface();
        loadArticles();
        setupEventListeners();
    }
});

function updateUserInterface() {
    const userNameElement = document.getElementById('userName');
    if (userNameElement) {
        userNameElement.textContent = currentUser.full_name || currentUser.username;
    }
}

function setupEventListeners() {
    // Botón de subir artículo
    const uploadBtn = document.getElementById('uploadBtn');
    if (uploadBtn) {
        uploadBtn.addEventListener('click', () => {
            document.getElementById('articleZipInput').click();
        });
    }
    
    // Input de archivo
    const fileInput = document.getElementById('articleZipInput');
    if (fileInput) {
        fileInput.addEventListener('change', handleFileUpload);
    }
    
    // Zona de drag & drop
    const uploadZone = document.getElementById('uploadZone');
    if (uploadZone) {
        uploadZone.addEventListener('dragover', handleDragOver);
        uploadZone.addEventListener('dragleave', handleDragLeave);
        uploadZone.addEventListener('drop', handleDrop);
        uploadZone.addEventListener('click', () => {
            document.getElementById('articleZipInput').click();
        });
    }
}

async function loadArticles(filters = {}) {
    try {
        const queryParams = new URLSearchParams(filters).toString();
        const response = await fetch(`api.php?action=list_articles&${queryParams}`);
        const data = await response.json();
        
        if (data.success) {
            displayArticles(data.articles);
        } else {
            showNotification('Error al cargar artículos', 'error');
        }
    } catch (error) {
        console.error('Error cargando artículos:', error);
        showNotification('Error de conexión', 'error');
    }
}

function displayArticles(articles) {
    const container = document.getElementById('articlesGrid');
    if (!container) return;
    
    if (articles.length === 0) {
        container.innerHTML = '<p>No hay artículos disponibles</p>';
        return;
    }
    
    container.innerHTML = articles.map(article => `
        <div class="article-card" data-article-id="${article.id}">
            <div class="article-header">
                <h3>${escapeHtml(article.title)}</h3>
                <span class="article-status status-${article.status}">${getStatusText(article.status)}</span>
            </div>
            <div class="article-meta">
                <p><strong>ID:</strong> ${article.article_id}</p>
                <p><strong>Subido por:</strong> ${article.uploaded_by_name || 'N/A'}</p>
                <p><strong>Fecha:</strong> ${formatDate(article.created_at)}</p>
            </div>
            <div class="article-actions">
                <button class="btn btn-primary btn-sm" onclick="openEditor(${article.id})">
                    Editar Marcación
                </button>
                ${article.status === 'marked' ? `
                    <button class="btn btn-success btn-sm" onclick="generateFiles(${article.id})">
                        Generar Archivos
                    </button>
                ` : ''}
            </div>
        </div>
    `).join('');
}

function handleDragOver(e) {
    e.preventDefault();
    e.currentTarget.classList.add('dragover');
}

function handleDragLeave(e) {
    e.currentTarget.classList.remove('dragover');
}

function handleDrop(e) {
    e.preventDefault();
    e.currentTarget.classList.remove('dragover');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        const fileInput = document.getElementById('articleZipInput');
        fileInput.files = files;
        handleFileUpload({ target: fileInput });
    }
}

async function handleFileUpload(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    // Validar tipo de archivo
    if (!file.name.endsWith('.zip')) {
        showNotification('Solo se permiten archivos .zip', 'error');
        return;
    }
    
    // Validar tamaño (50MB máximo)
    if (file.size > 50 * 1024 * 1024) {
        showNotification('El archivo es demasiado grande (máximo 50MB)', 'error');
        return;
    }
    
    // Mostrar loading
    showLoading('Subiendo y procesando artículo...');
    
    try {
        const formData = new FormData();
        formData.append('article_zip', file);
        
        const response = await fetch('api.php?action=upload_article', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        hideLoading();
        
        if (data.success) {
            showNotification('Artículo procesado exitosamente', 'success');
            
            // Mostrar resumen de extracción
            showExtractionSummary(data.extracted_data);
            
            // Recargar lista de artículos
            setTimeout(() => loadArticles(), 1000);
        } else {
            showNotification(data.message || 'Error al procesar artículo', 'error');
        }
    } catch (error) {
        hideLoading();
        console.error('Error subiendo artículo:', error);
        showNotification('Error de conexión', 'error');
    }
    
    // Limpiar input
    e.target.value = '';
}

function showExtractionSummary(data) {
    const modal = createModal('Extracción Automática Completada', `
        <div class="extraction-summary">
            <h4>Elementos Detectados:</h4>
            <ul>
                <li><strong>Título:</strong> ${data.metadata?.title || 'No detectado'}</li>
                <li><strong>Autores:</strong> ${data.authors?.length || 0}</li>
                <li><strong>Afiliaciones:</strong> ${data.affiliations?.length || 0}</li>
                <li><strong>Resumen:</strong> ${data.abstract?.es ? 'Detectado' : 'No detectado'}</li>
                <li><strong>Palabras clave:</strong> ${data.keywords?.es?.length || 0}</li>
                <li><strong>Secciones:</strong> ${data.sections?.length || 0}</li>
                <li><strong>Tablas:</strong> ${data.tables?.length || 0}</li>
                <li><strong>Figuras:</strong> ${data.figures?.length || 0}</li>
                <li><strong>Referencias:</strong> ${data.references?.length || 0}</li>
            </ul>
            <p class="info-text">Puede revisar y corregir estos elementos en el editor de marcación.</p>
        </div>
    `, [
        { text: 'Cerrar', class: 'btn btn-primary', callback: () => modal.close() }
    ]);
    
    modal.show();
}

function openEditor(articleId) {
    window.location.href = `/editor.php?id=${articleId}`;
}

async function generateFiles(articleId) {
    const modal = createModal('Generar Archivos', `
        <p>Seleccione los formatos a generar:</p>
        <div class="format-options">
            <label><input type="checkbox" id="gen_xml" checked> XML-JATS</label>
            <label><input type="checkbox" id="gen_pdf"> PDF</label>
            <label><input type="checkbox" id="gen_html"> HTML</label>
            <label><input type="checkbox" id="gen_epub"> EPUB</label>
        </div>
    `, [
        { text: 'Cancelar', class: 'btn', callback: () => modal.close() },
        { text: 'Generar', class: 'btn btn-primary', callback: async () => {
            modal.close();
            await processFileGeneration(articleId);
        }}
    ]);
    
    modal.show();
}

async function processFileGeneration(articleId) {
    const formats = [];
    if (document.getElementById('gen_xml')?.checked) formats.push('xml');
    if (document.getElementById('gen_pdf')?.checked) formats.push('pdf');
    if (document.getElementById('gen_html')?.checked) formats.push('html');
    if (document.getElementById('gen_epub')?.checked) formats.push('epub');
    
    showLoading('Generando archivos...');
    
    for (const format of formats) {
        try {
            const response = await fetch(`api.php?action=generate_${format}&article_id=${articleId}`);
            const data = await response.json();
            
            if (data.success) {
                console.log(`${format.toUpperCase()} generado:`, data.url);
            } else {
                console.error(`Error generando ${format}:`, data.message);
            }
        } catch (error) {
            console.error(`Error generando ${format}:`, error);
        }
    }
    
    hideLoading();
    showNotification('Archivos generados exitosamente', 'success');
}

// Utilidades
function getStatusText(status) {
    const statusMap = {
        'draft': 'Borrador',
        'processing': 'Procesando',
        'marked': 'Marcado',
        'published': 'Publicado'
    };
    return statusMap[status] || status;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

function showLoading(message = 'Cargando...') {
    const overlay = document.createElement('div');
    overlay.id = 'loadingOverlay';
    overlay.className = 'loading-overlay';
    overlay.innerHTML = `
        <div class="loading-content">
            <div class="spinner"></div>
            <p>${message}</p>
        </div>
    `;
    document.body.appendChild(overlay);
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.remove();
    }
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => notification.classList.add('show'), 100);
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function createModal(title, content, buttons = []) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>${title}</h3>
            </div>
            <div class="modal-body">
                ${content}
            </div>
            <div class="modal-footer">
                ${buttons.map(btn => `
                    <button class="${btn.class || 'btn'}">${btn.text}</button>
                `).join('')}
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Bind button events
    const buttonElements = modal.querySelectorAll('.modal-footer button');
    buttonElements.forEach((btn, index) => {
        if (buttons[index]?.callback) {
            btn.addEventListener('click', buttons[index].callback);
        }
    });
    
    return {
        show: () => modal.classList.add('active'),
        close: () => {
            modal.classList.remove('active');
            setTimeout(() => modal.remove(), 300);
        }
    };
}
