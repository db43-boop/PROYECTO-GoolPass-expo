<?php
final class Entrada
{
    public static function listarPorUsuario(int $usuarioId): array
    {
        $stmt=Database::connection()->prepare("SELECT e.id,e.codigo_qr,e.nombre_comprador,e.estado_pago,e.fecha_compra,
          e.precio_pagado,p.fecha_hora,p.estadio,p.precio_entrada,
          l.nombre AS local_nombre,v.nombre AS visitante_nombre
          FROM entradas e JOIN partidos p ON p.id=e.partido_id
          JOIN equipos l ON l.id=p.equipo_local_id JOIN equipos v ON v.id=p.equipo_visitante_id
          WHERE e.usuario_id=:usuario AND e.estado_pago='Pagado' ORDER BY e.id DESC");
        $stmt->execute([':usuario'=>$usuarioId]);return $stmt->fetchAll();
    }
    public static function existeCompra(int $usuarioId,int $partidoId): bool
    {
        $stmt=Database::connection()->prepare("SELECT 1 FROM entradas WHERE usuario_id=:usuario AND partido_id=:partido AND estado_pago='Pagado' LIMIT 1");
        $stmt->execute([':usuario'=>$usuarioId,':partido'=>$partidoId]);return (bool)$stmt->fetchColumn();
    }
    public static function partidosComprados(int $usuarioId): array
    {
        $stmt=Database::connection()->prepare("SELECT DISTINCT partido_id FROM entradas WHERE usuario_id=:usuario AND estado_pago='Pagado'");
        $stmt->execute([':usuario'=>$usuarioId]);return array_fill_keys(array_map('intval',$stmt->fetchAll(PDO::FETCH_COLUMN)),true);
    }
    /** El trigger UNIQUE lógico de SQLite impide una segunda entrada pagada. */
    public static function guardarCompra(int $usuario,int $partido,string $codigo,string $nombre,float $precio): int
    {
        $db=Database::connection();
        $stmt=$db->prepare("INSERT INTO entradas (usuario_id,partido_id,estado_pago,codigo_qr,nombre_comprador,precio_pagado)
          SELECT :usuario,id,'Pagado',:codigo,:nombre,:precio FROM partidos WHERE id=:partido AND estado='Programado'");
        $stmt->execute([':usuario'=>$usuario,':partido'=>$partido,':codigo'=>$codigo,':nombre'=>$nombre,':precio'=>$precio]);
        return $stmt->rowCount()===1?(int)$db->lastInsertId():0;
    }
}
