<?php
session_start();

if (!isset($_SESSION['user_id']) || !$_SESSION['logged_in']) {
    header('Location: login-directo.php');
    exit;
}

$userName = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Usuario');

// Cargar configuración
$config = require __DIR__ . '/../config/config.php';
$dbConfig = $config['database'];

// Mensajes
$success = '';
$error = '';

// Conexión a base de datos
try {
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // AUTO-MIGRATE COLUMNS
    try { $pdo->exec("ALTER TABLE journals ADD COLUMN oai_url VARCHAR(255) DEFAULT NULL;"); } catch(Exception $e) {}
    try { $pdo->exec("ALTER TABLE users ADD COLUMN journal_id INT DEFAULT NULL;"); } catch(Exception $e) {}
    try { 
        $pdo->exec("CREATE TABLE IF NOT EXISTS templates (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL, description TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)"); 
        $pdo->exec("ALTER TABLE articles ADD COLUMN template_id INT DEFAULT NULL;");
    } catch(Exception $e) {}
} catch (PDOException $e) {
    $error = 'Error de conexión a base de datos: ' . $e->getMessage();
    $pdo = null;
}

// Crear directorios si no existen
$tempDir = __DIR__ . '/uploads/temp';
$uploadsDir = __DIR__ . '/uploads';
$articlesDir = __DIR__ . '/articles';

if (!is_dir($uploadsDir)) @mkdir($uploadsDir, 0775, true);
if (!is_dir($tempDir)) @mkdir($tempDir, 0775, true);
if (!is_dir($articlesDir)) @mkdir($articlesDir, 0775, true);

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'save_journal':
            try {
                $j_id = $_POST['journal_id'] ?? '';
                if ($j_id) {
                    $stmt = $pdo->prepare("
                        UPDATE journals SET 
                            title = :title,
                            issn_print = :issn_print,
                            issn_electronic = :issn_electronic,
                            doi_prefix = :doi_prefix,
                            publisher = :publisher,
                            publisher_location = :publisher_location,
                            base_url = :base_url,
                            oai_url = :oai_url,
                            updated_at = NOW()
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        'id' => $j_id,
                        'title' => $_POST['journal_name'] ?? '',
                        'issn_print' => $_POST['issn_print'] ?? '',
                        'issn_electronic' => $_POST['issn_electronic'] ?? '',
                        'doi_prefix' => $_POST['doi_prefix'] ?? '',
                        'publisher' => $_POST['publisher'] ?? '',
                        'publisher_location' => $_POST['location'] ?? '',
                        'base_url' => $_POST['website'] ?? '',
                        'oai_url' => $_POST['oai_url'] ?? ''
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO journals (
                            title, issn_print, issn_electronic, doi_prefix,
                            publisher, publisher_location, base_url, oai_url,
                            created_at, updated_at
                        ) VALUES (
                            :title, :issn_print, :issn_electronic, :doi_prefix,
                            :publisher, :publisher_location, :base_url, :oai_url,
                            NOW(), NOW()
                        )
                    ");
                    $stmt->execute([
                        'title' => $_POST['journal_name'] ?? '',
                        'issn_print' => $_POST['issn_print'] ?? '',
                        'issn_electronic' => $_POST['issn_electronic'] ?? '',
                        'doi_prefix' => $_POST['doi_prefix'] ?? '',
                        'publisher' => $_POST['publisher'] ?? '',
                        'publisher_location' => $_POST['location'] ?? '',
                        'base_url' => $_POST['website'] ?? '',
                        'oai_url' => $_POST['oai_url'] ?? ''
                    ]);
                }
                $success = 'Revista guardada exitosamente';
            } catch (PDOException $e) {
                $error = 'Error al guardar revista: ' . $e->getMessage();
            }
            break;
            
        case 'delete_journal':
            if (!empty($_POST['journal_id'])) {
                try {
                    $pdo->prepare("DELETE FROM journals WHERE id = ?")->execute([$_POST['journal_id']]);
                    $success = "Revista eliminada.";
                } catch(PDOException $e) {
                    $error = "No se puede eliminar la revista. Puede tener datos asociados.";
                }
            }
            break;
            
        case 'save_template':
            try {
                $t_id = $_POST['template_id'] ?? '';
                if ($t_id) {
                    $stmt = $pdo->prepare("UPDATE templates SET name = :name, description = :description WHERE id = :id");
                    $stmt->execute(['id' => $t_id, 'name' => $_POST['template_name'] ?? '', 'description' => $_POST['template_description'] ?? '']);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO templates (name, description) VALUES (:name, :description)");
                    $stmt->execute(['name' => $_POST['template_name'] ?? '', 'description' => $_POST['template_description'] ?? '']);
                }
                $success = 'Plantilla guardada exitosamente';
            } catch (PDOException $e) {
                $error = 'Error al guardar plantilla: ' . $e->getMessage();
            }
            break;
            
        case 'delete_template':
            if (!empty($_POST['template_id'])) {
                try {
                    $pdo->prepare("DELETE FROM templates WHERE id = ?")->execute([$_POST['template_id']]);
                    $pdo->prepare("UPDATE articles SET template_id = NULL WHERE template_id = ?")->execute([$_POST['template_id']]);
                    $success = "Plantilla eliminada.";
                } catch(PDOException $e) {
                    $error = "Error al eliminar la plantilla.";
                }
            }
            break;
            
        case 'change_password':
            $currentPass = $_POST['current_password'] ?? '';
            $newPass = $_POST['new_password'] ?? '';
            $confirmPass = $_POST['confirm_password'] ?? '';
            
            if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
                $error = 'Todos los campos son obligatorios';
            } elseif ($newPass !== $confirmPass) {
                $error = 'Las contraseñas nuevas no coinciden';
            } elseif (strlen($newPass) < 8) {
                $error = 'La nueva contraseña debe tener al menos 8 caracteres';
            } else {
                try {
                    // Verificar contraseña actual
                    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = :id");
                    $stmt->execute(['id' => $_SESSION['user_id']]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!password_verify($currentPass, $user['password_hash'])) {
                        $error = 'Contraseña actual incorrecta';
                    } else {
                        // Actualizar contraseña
                        $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
                        $stmt->execute(['hash' => $newHash, 'id' => $_SESSION['user_id']]);
                        
                        $success = 'Contraseña cambiada exitosamente';
                    }
                } catch (PDOException $e) {
                    $error = 'Error al cambiar contraseña: ' . $e->getMessage();
                }
            }
            break;
            
        case 'clean_temp':
            $count = 0;
            if (is_dir($tempDir)) {
                $files = array_diff(scandir($tempDir), ['.', '..']);
                foreach ($files as $file) {
                    $filePath = $tempDir . '/' . $file;
                    if (is_file($filePath)) {
                        unlink($filePath);
                        $count++;
                    }
                }
            }
            $success = "Se eliminaron $count archivo(s) temporal(es)";
            break;
            
        case 'add_user':
            $newUsername = $_POST['new_username'] ?? '';
            $newEmail = $_POST['new_email'] ?? '';
            $newRole = $_POST['new_role'] ?? 'editor';
            $newPass = $_POST['new_password'] ?? '';
            $journalId = !empty($_POST['new_journal_id']) ? $_POST['new_journal_id'] : null;
            
            if ($newUsername && $newPass) {
                try {
                    $hash = password_hash($newPass, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, role, password_hash, active, created_at, journal_id) VALUES (:u, :e, :r, :p, 1, NOW(), :j)");
                    $stmt->execute(['u' => $newUsername, 'e' => $newEmail, 'r' => $newRole, 'p' => $hash, 'j' => $journalId]);
                    $success = "Usuario creado exitosamente.";
                } catch (PDOException $e) {
                    $error = "Error al crear usuario: " . $e->getMessage();
                }
            } else {
                $error = "Usuario y contraseña requeridos.";
            }
            break;
            
        case 'edit_user':
            $userId = $_POST['user_id'] ?? 0;
            $editEmail = $_POST['edit_email'] ?? '';
            $editRole = $_POST['edit_role'] ?? 'editor';
            $editJournalId = !empty($_POST['edit_journal_id']) ? $_POST['edit_journal_id'] : null;
            
            if ($userId) {
                try {
                    $stmt = $pdo->prepare("UPDATE users SET email = :e, role = :r, journal_id = :j WHERE id = :id");
                    $stmt->execute(['e' => $editEmail, 'r' => $editRole, 'j' => $editJournalId, 'id' => $userId]);
                    
                    if ($userId == $_SESSION['user_id']) {
                        $_SESSION['email'] = $editEmail;
                        $_SESSION['role'] = $editRole;
                    }
                    
                    $success = "Información de usuario actualizada.";
                } catch (PDOException $e) {
                    $error = "Error al actualizar: " . $e->getMessage();
                }
            }
            break;
            
        case 'delete_user':
            $userId = $_POST['user_id'] ?? 0;
            if ($userId == $_SESSION['user_id']) {
                $error = "No puede eliminar su propio usuario de sesión activa.";
            } elseif ($userId) {
                try {
                    // Eliminación lógica o física (haremos física para limpiar)
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
                    $stmt->execute(['id' => $userId]);
                    $success = "Usuario eliminado.";
                } catch (PDOException $e) {
                    $error = "No se puede eliminar el usuario. Es posible que tenga artículos asociados.";
                }
            }
            break;
    }
}

// Obtener listas
$allJournals = [];
$allTemplates = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM journals ORDER BY id DESC");
        $allJournals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->query("SELECT * FROM templates ORDER BY name ASC");
        $allTemplates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}

// Contar archivos temporales
$tempCount = 0;
if (is_dir($tempDir)) {
    $files = array_diff(scandir($tempDir), ['.', '..']);
    $tempCount = count($files);
}

// Obtener lista de usuarios para el Gestor
$allUsers = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT u.id, u.username, u.email, u.full_name, u.role, u.journal_id, j.title as journal_name FROM users u LEFT JOIN journals j ON u.journal_id = j.id ORDER BY u.created_at DESC");
        $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $allUsers = [];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - OpenJATS</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .config-page { display: flex; height: 100vh; }
        .config-content { flex: 1; padding: 40px; overflow-y: auto; }
        .config-section { background: white; padding: 30px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .config-section h2 { margin-top: 0; color: #2563eb; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; font-family: inherit;
        }
        .btn-group { display: flex; gap: 10px; margin-top: 20px; }
        .alert { padding: 12px 20px; border-radius: 6px; margin-bottom: 20px; }
        .alert.success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .alert.error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        .modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;
        }
        .modal.show { display: flex; }
        .modal-content { background: white; padding: 30px; border-radius: 8px; max-width: 500px; width: 90%; }
        .modal-content h3 { margin-top: 0; }
    </style>
</head>
<body class="config-page">
    <aside class="sidebar">
        <div class="sidebar-header"><h2>OpenJATS</h2></div>
        <nav>
            <ul class="sidebar-menu">
                <li><a href="index.php">📊 Dashboard</a></li>
                <li><a href="configuracion.php" class="active">⚙️ Configuración</a></li>
            </ul>
        </nav>
        <div class="sidebar-footer">
            <div class="user-info">
                <p><?php echo $userName; ?></p>
                <a href="logout-directo.php">Cerrar Sesión</a>
            </div>
        </div>
    </aside>
    
    <main class="config-content">
        <h1>Configuración del Sistema</h1>
        
        <?php if ($success): ?>
        <div class="alert success">✅ <?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert error">❌ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="config-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0; color: #2563eb;">📖 Revistas Registradas</h2>
                <button class="btn btn-primary" onclick="openJournalModal()">+ Nueva Revista</button>
            </div>
            
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                    <thead>
                        <tr style="background: #f8f9fa; border-bottom: 2px solid #e5e7eb;">
                            <th style="padding: 12px; font-weight: 600;">Nombre</th>
                            <th style="padding: 12px; font-weight: 600;">OAI Endpoint</th>
                            <th style="padding: 12px; font-weight: 600;">Editorial</th>
                            <th style="padding: 12px; font-weight: 600; text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($allJournals as $j): ?>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 12px;"><?= htmlspecialchars($j['title']) ?></td>
                            <td style="padding: 12px;"><?= htmlspecialchars($j['oai_url'] ?? '---') ?></td>
                            <td style="padding: 12px;"><?= htmlspecialchars($j['publisher'] ?? '---') ?></td>
                            <td style="padding: 12px; text-align: center;">
                                <button class="btn" style="padding: 5px 10px; font-size: 12px; margin-right: 5px; background: #f3f4f6;" 
                                    onclick='openJournalModal(<?= json_encode($j) ?>)'>✏️ Editar</button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Está seguro de eliminar esta revista permanentemente?');">
                                    <input type="hidden" name="action" value="delete_journal">
                                    <input type="hidden" name="journal_id" value="<?= $j['id'] ?>">
                                    <button type="submit" class="btn" style="padding: 5px 10px; font-size: 12px; background: #fee2e2; color: #991b1b;">🗑️ Borrar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($allJournals)): ?>
                        <tr><td colspan="4" style="text-align:center; padding: 20px;">No hay revistas registradas</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="config-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0; color: #2563eb;">📄 Gestor de Plantillas</h2>
                <button class="btn btn-primary" onclick="openTemplateModal()">+ Nueva Plantilla</button>
            </div>
            
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                    <thead>
                        <tr style="background: #f8f9fa; border-bottom: 2px solid #e5e7eb;">
                            <th style="padding: 12px; font-weight: 600;">Nombre</th>
                            <th style="padding: 12px; font-weight: 600;">Descripción</th>
                            <th style="padding: 12px; font-weight: 600; text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($allTemplates as $t): ?>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 12px; font-weight: 500;"><?= htmlspecialchars($t['name']) ?></td>
                            <td style="padding: 12px;"><?= htmlspecialchars($t['description'] ?? '---') ?></td>
                            <td style="padding: 12px; text-align: center;">
                                <button class="btn" style="padding: 5px 10px; font-size: 12px; margin-right: 5px; background: #f3f4f6;" 
                                    onclick='openTemplateModal(<?= json_encode($t) ?>)'>✏️ Editar</button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Está seguro de eliminar esta plantilla? Los manuscritos asociados perderán la asignación.');">
                                    <input type="hidden" name="action" value="delete_template">
                                    <input type="hidden" name="template_id" value="<?= $t['id'] ?>">
                                    <button type="submit" class="btn" style="padding: 5px 10px; font-size: 12px; background: #fee2e2; color: #991b1b;">🗑️ Borrar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($allTemplates)): ?>
                        <tr><td colspan="3" style="text-align:center; padding: 20px;">No hay plantillas registradas</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="config-section">
            <h2>👤 Información del Usuario</h2>
            <p><strong>Usuario:</strong> <?php echo $_SESSION['username']; ?></p>
            <p><strong>Email:</strong> <?php echo $_SESSION['email'] ?? 'No especificado'; ?></p>
            <p><strong>Rol:</strong> <?php echo ucfirst($_SESSION['role']); ?></p>
            
            <div class="btn-group" style="margin-bottom: 30px;">
                <button class="btn" onclick="document.getElementById('passwordModal').classList.add('show')">
                    🔑 Cambiar Contraseña
                </button>
            </div>
            
            <hr style="margin: 30px 0; border: 0; border-top: 1px solid #eee;">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0; color: #2c3e50;">👥 Gestor de Usuarios</h3>
                <button class="btn btn-primary" onclick="document.getElementById('addUserModal').classList.add('show')">
                    + Nuevo Usuario
                </button>
            </div>
            
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                    <thead>
                        <tr style="background: #f8f9fa; border-bottom: 2px solid #e5e7eb;">
                            <th style="padding: 12px; font-weight: 600;">Usuario</th>
                            <th style="padding: 12px; font-weight: 600;">Email</th>
                            <th style="padding: 12px; font-weight: 600;">Rol</th>
                            <th style="padding: 12px; font-weight: 600;">Revista Asignada</th>
                            <th style="padding: 12px; font-weight: 600; text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($allUsers as $u): ?>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 12px;"><?= htmlspecialchars($u['username']) ?></td>
                            <td style="padding: 12px;"><?= htmlspecialchars($u['email'] ?? '---') ?></td>
                            <td style="padding: 12px;">
                                <span style="background: #e0e7ff; color: #3730a3; padding: 3px 8px; border-radius: 12px; font-size: 12px;">
                                    <?= htmlspecialchars(ucfirst($u['role'])) ?>
                                </span>
                            </td>
                            <td style="padding: 12px;"><?= htmlspecialchars($u['journal_name'] ?? 'Todas (Global)') ?></td>
                            <td style="padding: 12px; text-align: center;">
                                <button class="btn" style="padding: 5px 10px; font-size: 12px; margin-right: 5px; background: #f3f4f6;" 
                                    onclick="openEditUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['email'] ?? '') ?>', '<?= htmlspecialchars($u['role'] ?? '') ?>', <?= $u['journal_id'] ?? 'null' ?>)">✏️ Editar</button>
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Está seguro de eliminar este usuario permanentemente?');">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn" style="padding: 5px 10px; font-size: 12px; background: #fee2e2; color: #991b1b;">🗑️ Borrar</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($allUsers)): ?>
                        <tr><td colspan="4" style="text-align:center; padding: 20px;">No hay usuarios registrados</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="config-section">
            <h2>🔧 Sistema</h2>
            <p><strong>Versión:</strong> 1.0.0</p>
            <p><strong>Base de datos:</strong> <?php echo $pdo ? 'Conectada ✅' : 'Error ❌'; ?></p>
            <p><strong>Archivos temporales:</strong> <?php echo $tempCount; ?> archivo(s)</p>
            
            <div class="btn-group">
                <?php if ($tempCount > 0): ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="clean_temp">
                    <button type="submit" class="btn" onclick="return confirm('¿Eliminar archivos temporales?')">
                        🧹 Limpiar Temporales (<?php echo $tempCount; ?>)
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="config-section">
            <h2>ℹ️ Acerca de</h2>
            <p><strong>OpenJATS</strong> - Sistema de marcación XML-JATS 1.2</p>
            <p style="margin-top: 20px;">
                <strong>Universidad Nacional del Altiplano de Puno</strong><br>
                Facultad de Ciencias Jurídicas y Políticas
            </p>
        </div>
    </main>
    
    <!-- Modal de Cambio de Contraseña -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <h3>Cambiar Contraseña</h3>
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group">
                    <label>Contraseña Actual *</label>
                    <input type="password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label>Nueva Contraseña * (mínimo 8 caracteres)</label>
                    <input type="password" name="new_password" required minlength="8">
                </div>
                
                <div class="form-group">
                    <label>Confirmar Nueva Contraseña *</label>
                    <input type="password" name="confirm_password" required minlength="8">
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Cambiar Contraseña</button>
                    <button type="button" class="btn" onclick="document.getElementById('passwordModal').classList.remove('show')">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal de Revista -->
    <div id="journalModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <h3 id="journalModalTitle">Nueva Revista</h3>
            <form method="POST">
                <input type="hidden" name="action" value="save_journal">
                <input type="hidden" name="journal_id" id="edit_journal_id">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Nombre de la Revista *</label>
                        <input type="text" name="journal_name" id="j_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label>ISSN (Impreso)</label>
                        <input type="text" name="issn_print" id="j_issn_p">
                    </div>
                    
                    <div class="form-group">
                        <label>ISSN (Electrónico)</label>
                        <input type="text" name="issn_electronic" id="j_issn_e">
                    </div>
                    
                    <div class="form-group">
                        <label>DOI Prefix</label>
                        <input type="text" name="doi_prefix" id="j_doi">
                    </div>
                    
                    <div class="form-group">
                        <label>Editorial / Publisher *</label>
                        <input type="text" name="publisher" id="j_publisher" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Extremo OAI-PMH (Endpoint)</label>
                        <input type="url" name="oai_url" id="j_oai" placeholder="https://revista.edu.pe/oai">
                    </div>
                    
                    <div class="form-group">
                        <label>Ubicación</label>
                        <input type="text" name="location" id="j_location">
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Sitio Web</label>
                        <input type="url" name="website" id="j_website">
                    </div>
                </div>
                
                <div class="btn-group" style="margin-top: 10px;">
                    <button type="submit" class="btn btn-primary">💾 Guardar Revista</button>
                    <button type="button" class="btn" onclick="document.getElementById('journalModal').classList.remove('show')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal de Plantilla -->
    <div id="templateModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <h3 id="templateModalTitle">Nueva Plantilla</h3>
            <form method="POST">
                <input type="hidden" name="action" value="save_template">
                <input type="hidden" name="template_id" id="edit_template_id">
                
                <div class="form-group">
                    <label>Nombre de la Plantilla *</label>
                    <input type="text" name="template_name" id="t_name" required placeholder="Ej: Plantilla Artículo de Redalyc">
                </div>
                
                <div class="form-group">
                    <label>Descripción / Notas</label>
                    <textarea name="template_description" id="t_description" rows="3" placeholder="Información adicional (opcional)"></textarea>
                </div>
                
                <div class="btn-group" style="margin-top: 10px;">
                    <button type="submit" class="btn btn-primary">💾 Guardar Plantilla</button>
                    <button type="button" class="btn" onclick="document.getElementById('templateModal').classList.remove('show')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Add User -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <h3>Agregar Nuevo Usuario</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_user">
                
                <div class="form-group">
                    <label>Nombre de Usuario *</label>
                    <input type="text" name="new_username" required placeholder="ej. jsmith">
                </div>
                
                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <input type="email" name="new_email" placeholder="ej. jsmith@ejemplo.com">
                </div>
                
                <div class="form-group">
                    <label>Rol *</label>
                    <select name="new_role" required>
                        <option value="editor">Editor</option>
                        <option value="admin">Administrador</option>
                        <option value="author">Autor</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Asignar Revista (Opcional)</label>
                    <select name="new_journal_id">
                        <option value="">Todas (Sin restricción)</option>
                        <?php foreach($allJournals as $j): ?>
                        <option value="<?= $j['id'] ?>"><?= htmlspecialchars($j['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Contraseña Provisional *</label>
                    <input type="password" name="new_password" required minlength="6">
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Registrar Usuario</button>
                    <button type="button" class="btn" onclick="document.getElementById('addUserModal').classList.remove('show')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit User -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <h3>Editar Usuario</h3>
            <form method="POST">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <input type="email" name="edit_email" id="edit_email">
                </div>
                
                <div class="form-group">
                    <label>Rol *</label>
                    <select name="edit_role" id="edit_role" required>
                        <option value="editor">Editor</option>
                        <option value="admin">Administrador</option>
                        <option value="author">Autor</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Asignar Revista (Opcional)</label>
                    <select name="edit_journal_id" id="edit_journal_id">
                        <option value="">Todas (Sin restricción)</option>
                        <?php foreach($allJournals as $j): ?>
                        <option value="<?= $j['id'] ?>"><?= htmlspecialchars($j['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    <button type="button" class="btn" onclick="document.getElementById('editUserModal').classList.remove('show')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Cerrar modal al hacer click fuera
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('show');
                }
            });
        });
        
        function openEditUser(id, email, role, journal_id) {
            document.getElementById('edit_user_id').value = id;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_role').value = role;
            document.getElementById('edit_journal_id').value = journal_id || "";
            document.getElementById('editUserModal').classList.add('show');
        }
        
        function openJournalModal(journal = null) {
            if (journal) {
                document.getElementById('journalModalTitle').innerText = 'Editar Revista';
                document.getElementById('edit_journal_id').value = journal.id;
                document.getElementById('j_name').value = journal.title || '';
                document.getElementById('j_issn_p').value = journal.issn_print || '';
                document.getElementById('j_issn_e').value = journal.issn_electronic || '';
                document.getElementById('j_doi').value = journal.doi_prefix || '';
                document.getElementById('j_publisher').value = journal.publisher || '';
                document.getElementById('j_oai').value = journal.oai_url || '';
                document.getElementById('j_location').value = journal.publisher_location || '';
                document.getElementById('j_website').value = journal.base_url || '';
            } else {
                document.getElementById('journalModalTitle').innerText = 'Nueva Revista';
                document.getElementById('edit_journal_id').value = '';
                document.getElementById('j_name').value = '';
                document.getElementById('j_issn_p').value = '';
                document.getElementById('j_issn_e').value = '';
                document.getElementById('j_doi').value = '';
                document.getElementById('j_publisher').value = '';
                document.getElementById('j_oai').value = '';
                document.getElementById('j_location').value = '';
                document.getElementById('j_website').value = '';
            }
            document.getElementById('journalModal').classList.add('show');
        }
        
        function openTemplateModal(template = null) {
            if (template) {
                document.getElementById('templateModalTitle').innerText = 'Editar Plantilla';
                document.getElementById('edit_template_id').value = template.id;
                document.getElementById('t_name').value = template.name || '';
                document.getElementById('t_description').value = template.description || '';
            } else {
                document.getElementById('templateModalTitle').innerText = 'Nueva Plantilla';
                document.getElementById('edit_template_id').value = '';
                document.getElementById('t_name').value = '';
                document.getElementById('t_description').value = '';
            }
            document.getElementById('templateModal').classList.add('show');
        }
    </script>
</body>
</html>
