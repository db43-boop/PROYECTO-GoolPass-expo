<?php
final class Jugador
{
    public static function porEquipo(int $id): array
    {
        $stmt=Database::connection()->prepare('SELECT * FROM jugadores WHERE equipo_id=:id ORDER BY nombre');
        $stmt->execute([':id'=>$id]);return $stmt->fetchAll();
    }
    public static function porEquipos(array $ids): array
    {
        if (!$ids) return [];
        $ids=array_values(array_unique(array_map('intval',$ids)));
        $marcas=implode(',',array_fill(0,count($ids),'?'));
        $stmt=Database::connection()->prepare("SELECT equipo_id,nombre,posicion FROM jugadores WHERE equipo_id IN ($marcas) ORDER BY id");
        $stmt->execute($ids);return $stmt->fetchAll();
    }
    public static function insertar(int $equipoId,array $j): void
    {
        $stmt=Database::connection()->prepare('INSERT INTO jugadores
         (equipo_id,nombre,posicion,goles,asistencias,tarjetas_amarillas,tarjetas_rojas,mvp_count,trofeos_ganados)
         VALUES (:equipo_id,:nombre,:posicion,:goles,:asistencias,:tarjetas_amarillas,:tarjetas_rojas,:mvp_count,:trofeos_ganados)');
        $stmt->execute([
            ':equipo_id'=>$equipoId,':nombre'=>trim((string)($j['nombre']??'')),':posicion'=>trim((string)($j['posicion']??'')),
            ':goles'=>max(0,(int)($j['goles']??0)),':asistencias'=>max(0,(int)($j['asistencias']??0)),
            ':tarjetas_amarillas'=>max(0,(int)($j['tarjetas_amarillas']??0)),':tarjetas_rojas'=>max(0,(int)($j['tarjetas_rojas']??0)),
            ':mvp_count'=>max(0,(int)($j['mvp_count']??0)),':trofeos_ganados'=>max(0,(int)($j['trofeos_ganados']??0)),
        ]);
    }
    public static function reemplazarPlantilla(int $equipoId,array $jugadores): void
    {
        Database::connection()->prepare('DELETE FROM jugadores WHERE equipo_id=:id')->execute([':id'=>$equipoId]);
        foreach ($jugadores as $j) {
            if (trim((string)($j['nombre']??''))!=='' && trim((string)($j['posicion']??''))!=='') self::insertar($equipoId,$j);
        }
    }
    public static function eliminar(int $id): void
    {
        Database::connection()->prepare('DELETE FROM jugadores WHERE id=:id')->execute([':id'=>$id]);
    }
    public static function actualizarParaEquipo(int $id, int $equipoId, array $j): void
    {
        $stmt = Database::connection()->prepare('UPDATE jugadores SET
            nombre=:nombre, posicion=:posicion, goles=:goles, asistencias=:asistencias,
            tarjetas_amarillas=:tarjetas_amarillas, tarjetas_rojas=:tarjetas_rojas,
            mvp_count=:mvp_count, trofeos_ganados=:trofeos_ganados
            WHERE id=:id AND equipo_id=:equipo_id');
        $stmt->execute([
            ':id'=>$id, ':equipo_id'=>$equipoId,
            ':nombre'=>trim((string)($j['nombre']??'')), ':posicion'=>trim((string)($j['posicion']??'')),
            ':goles'=>max(0,(int)($j['goles']??0)), ':asistencias'=>max(0,(int)($j['asistencias']??0)),
            ':tarjetas_amarillas'=>max(0,(int)($j['tarjetas_amarillas']??0)),
            ':tarjetas_rojas'=>max(0,(int)($j['tarjetas_rojas']??0)),
            ':mvp_count'=>max(0,(int)($j['mvp_count']??0)),
            ':trofeos_ganados'=>max(0,(int)($j['trofeos_ganados']??0)),
        ]);
    }
    public static function actualizar(int $id,array $j): void
    {
        $stmt=Database::connection()->prepare('UPDATE jugadores SET nombre=:nombre,posicion=:posicion,goles=:goles,
          asistencias=:asistencias,tarjetas_amarillas=:tarjetas_amarillas,tarjetas_rojas=:tarjetas_rojas,
          mvp_count=:mvp_count,trofeos_ganados=:trofeos_ganados WHERE id=:id');
        $stmt->execute([
         ':id'=>$id,':nombre'=>trim((string)($j['nombre']??'')),':posicion'=>trim((string)($j['posicion']??'')),
         ':goles'=>max(0,(int)($j['goles']??0)),':asistencias'=>max(0,(int)($j['asistencias']??0)),
         ':tarjetas_amarillas'=>max(0,(int)($j['tarjetas_amarillas']??0)),':tarjetas_rojas'=>max(0,(int)($j['tarjetas_rojas']??0)),
         ':mvp_count'=>max(0,(int)($j['mvp_count']??0)),':trofeos_ganados'=>max(0,(int)($j['trofeos_ganados']??0))
        ]);
    }
}
