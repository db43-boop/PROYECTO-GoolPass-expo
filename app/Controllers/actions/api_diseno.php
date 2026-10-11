<?php
/** Compatibilidad: API anterior de fichas de participantes, retirada del catálogo actual. */
require_once GOOLPASS_ROOT . '/app/Core/auth.php';
requerir_login();
http_response_code(410);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok'=>false,'error'=>'La función antigua de diseño de participantes ya no está disponible.'], JSON_UNESCAPED_UNICODE);
