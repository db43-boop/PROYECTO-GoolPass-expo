<?php
/** Front Controller: único punto de entrada por navegador en la estructura solicitada. */
require_once dirname(__DIR__) . '/app/bootstrap.php';
$r = (string)($_GET['r'] ?? 'index');
$mapa = [
    'index' => [EquipoController::class,'inicio'],
    'login' => [AuthController::class,'login'],
    'logout' => [AuthController::class,'logout'],
    'perfil' => [AuthController::class,'perfil'],
    'usuarios' => [AuthController::class,'usuarios'],
    'ver' => [EquipoController::class,'listar'],
    'ficha' => [EquipoController::class,'ficha'],
    'crear' => [EquipoController::class,'crear'],
    'editar' => [EquipoController::class,'editar'],
    'guardar_ficha' => [EquipoController::class,'guardar'],
    'api_diseno' => [EquipoController::class,'apiDiseno'],
    'partidos' => [PartidoController::class,'partidos'],
    'mis_entradas' => [EntradaController::class,'entradas'],
    'torneo' => [TorneoController::class,'torneos'],
    'api_torneo' => [TorneoController::class,'api'],
];
if (!isset($mapa[$r])) {
    http_response_code(404);
    exit('Página no encontrada.');
}
call_user_func($mapa[$r]);
