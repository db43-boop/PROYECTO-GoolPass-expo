<?php
/** Resumen de marcadores a partir de la tabla de partidos original. */
final class EstadisticaPartido {
    public static function porPartido(int $partidoId): array|false {
        $stmt = Database::connection()->prepare('SELECT p.id, p.fecha_hora, p.estado, p.goles_local, p.goles_visitante,
            l.nombre AS equipo_local, v.nombre AS equipo_visitante
            FROM partidos p JOIN equipos l ON l.id = p.equipo_local_id
            JOIN equipos v ON v.id = p.equipo_visitante_id WHERE p.id = :id');
        $stmt->execute([':id' => $partidoId]);
        return $stmt->fetch();
    }
}
