<?php
/**
 * Login - Redirige a login-directo.php temporalmente
 * Esto evita el problema de caché de PHP con User.php
 */

// Si ya está logueado, ir al dashboard
session_start();
if (isset($_SESSION['user_id']) && $_SESSION['logged_in']) {
    header('Location: index.php');
    exit;
}

// Redirigir a login-directo.php que evita el caché
header('Location: login-directo.php');
exit;
?>
