# OpenJATS (JATS Assistant)

Sistema completo de marcación XML-JATS para artículos académicos, desarrollado para la Facultad de Ciencias Jurídicas y Políticas de la Universidad Nacional del Altiplano de Puno.

## ⚖️ Disclaimer (Aviso Legal)
Este software es proporcionado "tal cual" (as is), sin garantías de ningún tipo, expresas o implícitas. Su uso es bajo responsabilidad del administrador de la plataforma. Ha sido desarrollado como una herramienta auxiliar de gestión editorial y generación automática. Por lo tanto, no se asume ninguna responsabilidad por cualquier pérdida de datos, falla técnica o alteración inadvertida de los registros.

## 🎖️ Créditos
Este sistema fue diseñado y programado para su implementación, contando además con la indispensable **asistencia y acompañamiento de las Inteligencias Artificiales de Google (Gemini Pro) y Anthropic (Sonnet Claude)**, logrando el desarrollo, modernización y soporte de sus componentes.

## 🚀 Características Principales

1. **Autenticación y Gestión de Usuarios**
   - Sistema de login seguro con PHP 8 y sesiones
   - Roles: Admin, Editor, Reviewer
   - Gestión de permisos

2. **Carga y Procesamiento Automático**
   - Subida de archivos .zip con HTML generado por Word
   - Reconocimiento automático mediante patrones y NLP de:
     - Títulos (español/inglés)
     - Autores con ORCID y emails
     - Afiliaciones institucionales
     - Resumen/Abstract
     - Palabras clave/Keywords
     - Secciones del documento
     - Tablas
     - Figuras (imágenes)
     - Referencias en formato APA
     - Citas in-text

3. **Editor Visual de Marcación**
   - Marcación manual mediante selección de texto
   - Click en tablas y figuras para importarlas
   - Campos editables para cada elemento
   - Vista previa del XML-JATS en tiempo real
   - Corrección y ajuste de elementos detectados

4. **Configuración de Revista**
   - Gestión de metadatos de revista
   - Configuración de volúmenes y números
   - Metadatos del manuscrito (fechas, DOI, etc.)

5. **Generación de Formatos**
   - XML-JATS (estándar 1.2)
   - PDF (mediante bibliotecas PHP)
   - HTML (versión web del artículo)
   - EPUB (para lectores electrónicos)

6. **Sistema de Publicación**
   - Almacenamiento organizado de artículos
   - URLs públicas amigables
   - Compatibilidad con estructura tipo Redalyc

## 📋 Requisitos del Sistema

- PHP 8.0 o superior
- MariaDB 10.x o MySQL 8.x
- Apache/Nginx con mod_rewrite
- Extensiones PHP requeridas:
  - pdo_mysql
  - zip
  - dom
  - mbstring
  - json
  - gd (para procesamiento de imágenes)

## 🛠️ Instalación

### 1. Configurar Base de Datos

```bash
# Crear base de datos
mysql -u root -p < database.sql

# Verificar creación
mysql -u root -p -e "USE jats_assistant; SHOW TABLES;"
```

### 2. Configurar PHP

Editar `config/config.php` con tus credenciales de base de datos:

```php
'database' => [
    'host' => 'localhost',
    'port' => '3306',
    'database' => 'jats_assistant',
    'username' => 'tu_usuario',
    'password' => 'tu_contraseña',
],
```

### 3. Configurar Apache

Crear virtual host en `/etc/apache2/sites-available/jats-assistant.conf`:

```apache
<VirtualHost *:80>
    ServerName jats.localhost
    DocumentRoot /path/to/jats-assistant/public
    
    <Directory /path/to/jats-assistant/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/jats_error.log
    CustomLog ${APACHE_LOG_DIR}/jats_access.log combined
</VirtualHost>
```

Habilitar y reiniciar:

```bash
sudo a2ensite jats-assistant
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### 4. Configurar Permisos

```bash
# Dar permisos de escritura a directorios de uploads
chmod -R 775 public/uploads public/articles
chown -R www-data:www-data public/uploads public/articles
```

## 📁 Estructura del Proyecto

```
jats-assistant/
├── config/
│   └── config.php              # Configuración general
├── public/                      # Raíz web pública
│   ├── css/
│   │   └── styles.css          # Estilos principales
│   ├── js/
│   │   ├── auth.js             # Autenticación
│   │   ├── dashboard.js        # Panel principal
│   │   └── editor.js           # Editor de marcación
│   ├── uploads/                # Archivos temporales
│   ├── articles/               # Artículos publicados
│   ├── index.php               # Dashboard
│   ├── login.php               # Página de login
│   ├── editor.php              # Editor de marcación
│   └── api.php                 # API REST
├── src/
│   ├── controllers/
│   │   ├── AuthController.php  # Control de autenticación
│   │   └── ArticleController.php # Control de artículos
│   ├── models/
│   │   ├── Database.php        # Conexión BD
│   │   ├── User.php            # Modelo de usuarios
│   │   └── Article.php         # Modelo de artículos
│   └── utils/
│       ├── HTMLProcessor.php   # Procesamiento HTML
│       ├── JATSGenerator.php   # Generador XML-JATS
│       ├── PDFGenerator.php    # Generador PDF
│       ├── HTMLGenerator.php   # Generador HTML
│       └── EPUBGenerator.php   # Generador EPUB
├── templates/                   # Plantillas HTML
├── database.sql                 # Script de base de datos
└── README.md                    # Este archivo
```

## 🔐 Credenciales por Defecto

- **Usuario:** admin
- **Contraseña:** admin123

⚠️ **IMPORTANTE:** Cambiar estas credenciales inmediatamente después de la instalación.

## 💻 Uso del Sistema

### 1. Subir Artículo

1. Iniciar sesión en el sistema
2. Ir a "Nuevo Artículo"
3. Subir archivo .zip con HTML de Word
4. El sistema procesará automáticamente:
   - Extracción del HTML
   - Reconocimiento de elementos
   - Guardado en base de datos

### 2. Editar Marcación

1. Seleccionar artículo de la lista
2. Abrir en Editor de Marcación
3. Usar las herramientas de marcación:
   - **Seleccionar texto** y marcar como: Título, Autor, Resumen, etc.
   - **Click en tablas** para importarlas
   - **Click en figuras** para agregarlas
   - **Editar metadatos** en panel derecho

### 3. Configurar Metadatos

1. Ir a "Configuración" > "Revista"
2. Ingresar datos de la revista:
   - Título
   - ISSN (impreso/electrónico)
   - Editorial
   - DOI prefix

3. Crear Volúmenes y Números
4. Asignar artículo a número específico

### 4. Generar Formatos

1. Abrir artículo marcado
2. Click en "Generar Archivos"
3. Seleccionar formatos deseados:
   - XML-JATS
   - PDF
   - HTML
   - EPUB
4. Descargar archivos generados

### 5. Publicar Artículo

1. Cambiar estado a "Publicado"
2. El artículo estará disponible en:
   - `/articles/{article_id}/`
   - `/articles/{article_id}/index.html`
   - `/articles/{article_id}/article.xml`
   - `/articles/{article_id}/article.pdf`

## 🔧 API REST

### Endpoints Disponibles

```
POST   /api.php?action=login           - Iniciar sesión
POST   /api.php?action=logout          - Cerrar sesión
GET    /api.php?action=check_auth      - Verificar autenticación

POST   /api.php?action=upload_article  - Subir artículo
GET    /api.php?action=get_article&id={id} - Obtener artículo
GET    /api.php?action=list_articles   - Listar artículos
POST   /api.php?action=save_markup     - Guardar marcación
POST   /api.php?action=generate_xml    - Generar XML-JATS
POST   /api.php?action=generate_pdf    - Generar PDF
POST   /api.php?action=generate_html   - Generar HTML
POST   /api.php?action=generate_epub   - Generar EPUB
```

### Ejemplo de Uso (JavaScript)

```javascript
// Subir artículo
const formData = new FormData();
formData.append('article_zip', fileInput.files[0]);

fetch('/api.php?action=upload_article', {
    method: 'POST',
    body: formData
})
.then(res => res.json())
.then(data => {
    if (data.success) {
        console.log('Artículo subido:', data.article_id);
        console.log('Datos extraídos:', data.extracted_data);
    }
});

// Guardar marcación
fetch('/api.php?action=save_markup', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        article_id: 123,
        markup_data: {
            title: 'Título del artículo',
            authors: [...],
            sections: [...]
        }
    })
})
.then(res => res.json())
.then(data => console.log('Marcación guardada'));
```

## 🎨 Personalización

### Modificar Estilos

Editar `public/css/styles.css` para cambiar colores, tipografía, etc.

Variables CSS disponibles:

```css
:root {
    --primary-color: #2563eb;
    --secondary-color: #1e40af;
    --success-color: #10b981;
    --danger-color: #ef4444;
}
```

### Agregar Nuevos Campos

1. Modificar tabla en `database.sql`
2. Actualizar modelo correspondiente
3. Actualizar formularios en editor
4. Actualizar generador XML-JATS

## 📚 Documentación Adicional

### Formato XML-JATS

El sistema genera XML-JATS compatible con el estándar NISO JATS 1.2:
- [JATS Documentation](https://jats.nlm.nih.gov/)
- [JATS DTD](https://jats.nlm.nih.gov/archiving/tag-library/1.2/)

### Estructura de URLs Públicas

Similar a Redalyc:

```
/articles/671874656001/             # Página principal del artículo
/articles/671874656001/index.html   # Vista HTML
/articles/671874656001/article.xml  # XML-JATS
/articles/671874656001/article.pdf  # PDF
/articles/671874656001/figures/     # Imágenes
```

## 🐛 Solución de Problemas

### Error: "No se puede conectar a la base de datos"
- Verificar credenciales en `config/config.php`
- Verificar que MariaDB esté corriendo
- Verificar permisos del usuario de BD

### Error: "No se puede subir archivo"
- Verificar permisos de `/public/uploads`
- Verificar tamaño máximo en `php.ini`: `upload_max_filesize` y `post_max_size`

### Error: "No se genera XML-JATS"
- Verificar extensión PHP DOM instalada
- Revisar logs de error de PHP

## 📝 Licencia

Este software es de código abierto. Desarrollado para la Universidad Nacional del Altiplano de Puno.

## 👥 Contribuciones

Las contribuciones son bienvenidas. Por favor:

1. Fork del repositorio
2. Crear rama feature
3. Commit de cambios
4. Push a la rama
5. Crear Pull Request

## 📧 Soporte

Para reportar bugs o solicitar características:
- Email: soporte@revista.edu
- Issues en GitHub

## 🔄 Actualizaciones

### Versión 1.0.0 (2026)
- Lanzamiento inicial
- Sistema completo de marcación
- Generación de XML-JATS, PDF, HTML, EPUB
- Editor visual
- Sistema de autenticación

### Próximas Características
- Integración con ORCID API
- Validación automática de referencias con CrossRef
- Exportación a otros formatos (MARCXML, Dublin Core)
- Panel de estadísticas
- Notificaciones por email
- Flujo de revisión por pares
- Integración con OJS/PKP
