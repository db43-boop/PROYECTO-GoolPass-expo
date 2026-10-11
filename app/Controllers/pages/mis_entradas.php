<?php
require_once GOOLPASS_ROOT . '/app/Core/auth.php';
requerir_login();
if (es_admin()) {
    http_response_code(403);
    exit('Mis entradas está disponible únicamente para usuarios.');
}

$usuarioId = (int)($_SESSION['usuario']['id'] ?? 0);
$recienComprada = (int)($_SESSION['entrada_recien_comprada'] ?? 0);
unset($_SESSION['entrada_recien_comprada']);

$entradas = Entrada::listarPorUsuario($usuarioId);
$idsPropios = array_map(static fn($fila) => (int)$fila['id'], $entradas);
$mostrarCompraCorrecta = $recienComprada > 0 && in_array($recienComprada, $idsPropios, true);
?>
