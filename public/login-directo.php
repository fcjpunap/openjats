<?php
/**
 * Login Directo - SIN User.php, SIN JavaScript
 * Hace la consulta SQL directamente para evitar caché de PHP
 * 
 * Subir a: /public/login-directo.php
 * Acceder: https://fcjp.derecho.unap.edu.pe/catg/jats-assistant/public/login-directo.php
 */

session_start();

// Cargar configuración
$config = require __DIR__ . '/../config/config.php';
$dbConfig = $config['database'];

$error = '';
$success = '';

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['user_id']) && $_SESSION['logged_in']) {
    header('Location: index.php');
    exit;
}

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username && $password) {
        try {
            // Conexión directa a base de datos
            $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
            $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Consulta SQL DIRECTA (con :identifier correcto)
            $stmt = $pdo->prepare(
                "SELECT * FROM users WHERE (username = :identifier OR email = :identifier) AND active = TRUE"
            );
            $stmt->execute(['identifier' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Login exitoso
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['logged_in'] = true;
                
                // Actualizar último login
                $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
                $updateStmt->execute(['id' => $user['id']]);
                
                // Redirigir al dashboard
                header('Location: index.php');
                exit;
            } else {
                $error = 'Usuario o contraseña incorrectos';
            }
        } catch (PDOException $e) {
            $error = 'Error de conexión: ' . $e->getMessage();
        }
    } else {
        $error = 'Por favor ingrese usuario y contraseña';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Directo - JATS Assistant</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .login-page {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
        }
        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }
        .login-box {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        .login-header {
            text-align: center;
            padding: 40px 30px 30px;
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
        }
        .login-header h1 {
            font-size: 24px;
            margin-bottom: 8px;
        }
        .login-header p {
            font-size: 14px;
            opacity: 0.9;
        }
        .login-form {
            padding: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            font-size: 14px;
            color: #111827;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
            font-family: inherit;
        }
        .form-group input:focus {
            outline: none;
            border-color: #2563eb;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-block;
            text-decoration: none;
            text-align: center;
        }
        .btn-primary {
            background: #2563eb;
            color: white;
            width: 100%;
        }
        .btn-primary:hover {
            background: #1e40af;
        }
        .error-message {
            background: #fef2f2;
            color: #ef4444;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
            border-left: 4px solid #ef4444;
        }
        .success-message {
            background: #d1fae5;
            color: #065f46;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
            border-left: 4px solid #10b981;
        }
        .login-footer {
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }
        .badge {
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 10px;
        }
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>OpenJATS</h1>
                <p>Sistema de Marcación XML-JATS</p>
            </div>
            
            <form method="POST" class="login-form">
                <?php if ($error): ?>
                    <div class="error-message">
                        ❌ <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success-message">
                        ✅ <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="username">Usuario o Email</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div style="text-align: right; margin-bottom: 20px;">
                    <a href="recuperar-password.php" style="font-size: 13px; color: #2563eb; text-decoration: none;">¿Olvidaste tu contraseña?</a>
                </div>
                
                <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
            </form>
            
            <div class="login-footer">
                <p>Desarrollado por Michael Espinoza Coila</p>
                <p>con asistencia de Gemini Pro y Sonnet (Claude).</p>
                <hr style="margin: 15px 0; opacity: 0.3;">
                <p>v1.0.0 | Universidad Nacional del Altiplano de Puno</p>
            </div>
        </div>
    </div>
</body>
</html>
