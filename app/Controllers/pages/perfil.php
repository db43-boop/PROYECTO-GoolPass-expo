<?php
require_once GOOLPASS_ROOT . '/app/Core/auth.php';
requerir_login();

$u = usuario_actual();
$denegado = isset($_GET['denegado']);
?>
