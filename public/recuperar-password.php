<?php
session_start();

$config = require __DIR__ . '/../config/config.php';
$dbConfig = $config['database'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        try {
            $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
            $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND active = TRUE");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Generar token seguro
                $token = bin2hex(random_bytes(32));
                
                // NOTA: Aquí iría la lógica para guardar el token en la base de datos e invocar 
                // la función de envío de correos (ej. PHPMailer o mail())
                
                $success = "Si el correo está registrado, se han enviado las instrucciones de recuperación.";
            } else {
                // Por seguridad, no especificamos si el correo existe o no
                $success = "Si el correo está registrado, se han enviado las instrucciones de recuperación.";
            }
        } catch (PDOException $e) {
            $error = 'Error interno temporal del sistema: ' . $e->getMessage();
        }
    } else {
        $error = "Por favor, introduzca una dirección de correo electrónico válida.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - OpenJATS</title>
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
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>OpenJATS</h1>
                <p>Recuperar Contraseña</p>
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
                
                <p style="font-size: 14px; color: #4b5563; margin-bottom: 20px; text-align: justify;">
                    Ingrese el correo electrónico asociado a su cuenta. Le enviaremos un enlace con las instrucciones para restablecer su contraseña.
                </p>

                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email" required autofocus placeholder="correo@ejemplo.com">
                </div>
                
                <button type="submit" class="btn btn-primary" style="margin-bottom: 15px;">Enviar Enlace</button>
                
                <div style="text-align: center;">
                    <a href="login-directo.php" style="font-size: 14px; color: #2563eb; text-decoration: none;">Volver al inicio de sesión</a>
                </div>
            </form>
            
            <div class="login-footer">
                <p>Desarrollado por Michael Espinoza Coila</p>
                <p>con asistencia de Gemini Pro.</p>
                <hr style="margin: 15px 0; opacity: 0.3;">
                <p>v1.0.0 | Universidad Nacional del Altiplano de Puno</p>
            </div>
        </div>
    </div>
</body>
</html>
