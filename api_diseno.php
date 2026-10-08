<?php
/**
 * API · DISEÑO DE FICHA
 * ---------------------------------------------------------------------------
 * Recibe la semilla generada por js/ficha_diseno.js y la guarda en la tabla
 * participantes para que el diseño único se mantenga entre visitas.
 *
 * POST: id=<int>&semilla=<texto>
 * Salida: JSON { ok: true, semilla: "..." }  |  { ok: false, error: "..." }
 */

header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

$id       = (int)($_POST['id'] ?? 0);
$semilla  = trim($_POST['semilla'] ?? '');

// Validación: solo letras y números, longitud razonable
if ($id <= 0 || !preg_match('/^[A-Za-z0-9]{1,32}$/', $semilla)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Datos inválidos']);
    exit;
}

try {
    // Asegurar que la columna existe (migración segura)
    try {
        $conexion->exec("ALTER TABLE participantes ADD COLUMN semilla TEXT DEFAULT ''");
    } catch (PDOException $e) {
        // Ya existe
    }

    $stmt = $conexion->prepare("UPDATE participantes SET semilla = :semilla WHERE id = :id");
    $stmt->execute([
        ':semilla' => $semilla,
        ':id'      => $id
    ]);

    echo json_encode(['ok' => true, 'semilla' => $semilla]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}