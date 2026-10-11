<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Solo terminal.'); }
require_once __DIR__ . '/app/bootstrap.php';
require GOOLPASS_ROOT . '/scripts/configurar_admin.php';
