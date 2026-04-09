<?php
/**
 * Logout Directo - Sin api.php
 */
session_start();
session_destroy();
header('Location: login-directo.php');
exit;
?>
