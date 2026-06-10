<?php
session_start();

$config = require __DIR__ . '/../config/config.php';
$dbConfig = $config['database'];

$error = '';
$success = '';

$token = $_GET['token'] ?? '';

if (!$token) {
    die("Token no proporcionado.");
}

try {
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar si el token es válido y no ha expirado
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = :token AND reset_token_expires > NOW()");
    $stmt->execute(['token' => $token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $error = "El enlace de recuperación es inválido o ha expirado.";
    }
} catch (PDOException $e) {
    // Si la columna no existe, mostrar error
    $error = "Error al verificar el token. Asegúrese de haber actualizado la base de datos.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    if (strlen($password) < 8) {
        $error = "La contraseña debe tener al menos 8 caracteres.";
    } elseif ($password !== $password_confirm) {
        $error = "Las contraseñas no coinciden.";
    } else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = :hash, reset_token = NULL, reset_token_expires = NULL WHERE id = :id");
            $stmt->execute([
                'hash' => $hash,
                'id' => $user['id']
            ]);
            
            $success = "Contraseña actualizada correctamente. Ya puede iniciar sesión con su nueva contraseña.";
        } catch (PDOException $e) {
            $error = 'Error al actualizar la contraseña: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - OpenJATS</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .login-page { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); }
        .login-container { width: 100%; max-width: 420px; padding: 20px; }
        .login-box { background: white; border-radius: 12px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); overflow: hidden; }
        .login-header { text-align: center; padding: 40px 30px 30px; background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); color: white; }
        .login-header h1 { font-size: 24px; margin-bottom: 8px; }
        .login-form { padding: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 500; font-size: 14px; color: #111827; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 14px; }
        .btn { padding: 12px 24px; border: none; border-radius: 6px; font-size: 14px; font-weight: 500; cursor: pointer; text-align: center; width: 100%; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1e40af; }
        .error-message { background: #fef2f2; color: #ef4444; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; border-left: 4px solid #ef4444; }
        .success-message { background: #d1fae5; color: #065f46; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; border-left: 4px solid #10b981; }
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>OpenJATS</h1>
                <p>Restablecer Contraseña</p>
            </div>
            
            <form method="POST" class="login-form">
                <?php if ($error): ?>
                    <div class="error-message">❌ <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success-message">
                        ✅ <?= htmlspecialchars($success) ?>
                        <div style="margin-top: 15px; text-align: center;">
                            <a href="login-directo.php" style="color: #065f46; font-weight: bold; text-decoration: underline;">Ir a iniciar sesión</a>
                        </div>
                    </div>
                <?php elseif (!$error || isset($user)): ?>
                    <div class="form-group">
                        <label for="password">Nueva Contraseña (mínimo 8 caracteres)</label>
                        <input type="password" id="password" name="password" required autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label for="password_confirm">Confirmar Nueva Contraseña</label>
                        <input type="password" id="password_confirm" name="password_confirm" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Guardar Contraseña</button>
                <?php endif; ?>
            </form>
        </div>
    </div>
</body>
</html>
