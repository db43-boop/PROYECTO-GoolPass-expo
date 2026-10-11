<?php
require_once GOOLPASS_ROOT . '/app/Core/auth.php';
cerrar_sesion();
header('Location: login.php?salida=1');
exit;
