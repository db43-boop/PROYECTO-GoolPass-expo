<?php
final class Partido
{
    public static function crear(int $local,int $visitante,string $fecha,string $estadio,float $precio): void
    {
        Database::connection()->prepare('INSERT INTO partidos (equipo_local_id,equipo_visitante_id,fecha_hora,estadio,precio_entrada)
          VALUES (:local,:visitante,:fecha,:estadio,:precio)')
          ->execute([':local'=>$local,':visitante'=>$visitante,':fecha'=>$fecha,':estadio'=>$estadio,':precio'=>$precio]);
    }
    public static function actualizarResultado(int $id,int $gl,int $gv,string $estado): int
    {
        $stmt=Database::connection()->prepare('UPDATE partidos SET goles_local=:gl,goles_visitante=:gv,estado=:estado WHERE id=:id');
        $stmt->execute([':gl'=>$gl,':gv'=>$gv,':estado'=>$estado,':id'=>$id]);return $stmt->rowCount();
    }
    public static function existe(int $id): bool
    {
        $stmt=Database::connection()->prepare('SELECT 1 FROM partidos WHERE id=:id');
        $stmt->execute([':id'=>$id]);return (bool)$stmt->fetchColumn();
    }
    public static function buscarParaCompra(int $id): array|false
    {
        $stmt=Database::connection()->prepare('SELECT id,fecha_hora,estado,precio_entrada FROM partidos WHERE id=:id');
        $stmt->execute([':id'=>$id]);return $stmt->fetch();
    }
    public static function listarConEquipos(): array
    {
        return Database::connection()->query("SELECT p.*,l.nombre AS local_nombre,v.nombre AS visitante_nombre,
          (SELECT COUNT(*) FROM entradas e WHERE e.partido_id=p.id AND e.estado_pago='Pagado') AS entradas_vendidas
          FROM partidos p JOIN equipos l ON l.id=p.equipo_local_id JOIN equipos v ON v.id=p.equipo_visitante_id
          ORDER BY p.fecha_hora ASC")->fetchAll();
    }
}
