<?php
final class Equipo
{
    public static function buscar(int $id): array|false
    {
        $stmt = Database::connection()->prepare('SELECT * FROM equipos WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    public static function existe(int $id): bool
    {
        $stmt = Database::connection()->prepare('SELECT 1 FROM equipos WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return (bool)$stmt->fetchColumn();
    }
    public static function totales(): array
    {
        $db = Database::connection();
        return [
            'equipos' => (int)$db->query('SELECT COUNT(*) FROM equipos')->fetchColumn(),
            'ligas' => (int)$db->query("SELECT COUNT(DISTINCT liga) FROM equipos WHERE liga IS NOT NULL AND TRIM(liga) <> ''")->fetchColumn(),
        ];
    }
    public static function recientes(int $limite = 6): array
    {
        $stmt = Database::connection()->prepare('SELECT id, nombre, liga, escudo, anio FROM equipos ORDER BY id DESC LIMIT :limite');
        $stmt->bindValue(':limite', max(1, min($limite, 50)), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    public static function listarPorOrigen(string $origen): array
    {
        $stmt = Database::connection()->prepare('SELECT e.*,
          (SELECT COUNT(*) FROM jugadores j WHERE j.equipo_id=e.id) AS total_jugadores,
          (SELECT COALESCE(SUM(j.goles),0) FROM jugadores j WHERE j.equipo_id=e.id) AS total_goles,
          (SELECT COALESCE(SUM(j.asistencias),0) FROM jugadores j WHERE j.equipo_id=e.id) AS total_asistencias,
          (SELECT COALESCE(SUM(j.tarjetas_amarillas),0) FROM jugadores j WHERE j.equipo_id=e.id) AS total_amarillas,
          (SELECT COALESCE(SUM(j.tarjetas_rojas),0) FROM jugadores j WHERE j.equipo_id=e.id) AS total_rojas,
          (SELECT COALESCE(SUM(j.mvp_count),0) FROM jugadores j WHERE j.equipo_id=e.id) AS total_mvp
          FROM equipos e WHERE e.origen = :origen ORDER BY e.nombre');
        $stmt->execute([':origen'=>$origen]);
        return $stmt->fetchAll();
    }
    public static function conteosPorOrigen(): array
    {
        $conteos = ['nacional'=>0,'internacional'=>0];
        foreach (Database::connection()->query("SELECT origen,COUNT(*) AS total FROM equipos WHERE origen IN ('nacional','internacional') GROUP BY origen") as $fila) {
            $conteos[$fila['origen']] = (int)$fila['total'];
        }
        return $conteos;
    }
    public static function opciones(): array
    {
        return Database::connection()->query('SELECT id,nombre,liga,origen FROM equipos ORDER BY nombre')->fetchAll();
    }
    public static function opcionesTorneo(): array
    {
        return Database::connection()->query('SELECT id,nombre,liga,escudo,origen FROM equipos ORDER BY nombre')->fetchAll();
    }
    public static function idsPorOrigen(string $origen): array
    {
        $stmt=Database::connection()->prepare('SELECT id FROM equipos WHERE origen=:origen');
        $stmt->execute([':origen'=>$origen]);
        return array_map('intval',$stmt->fetchAll(PDO::FETCH_COLUMN));
    }
    public static function origenesDeEquipos(int $local, int $visitante): array
    {
        $stmt=Database::connection()->prepare('SELECT id,origen FROM equipos WHERE id IN (:local,:visitante)');
        $stmt->execute([':local'=>$local,':visitante'=>$visitante]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    public static function crear(string $nombre,string $liga,string $origen,string $anio,string $escudo): int
    {
        $db=Database::connection();
        $stmt=$db->prepare('INSERT INTO equipos (nombre,liga,origen,anio,escudo) VALUES (:nombre,:liga,:origen,:anio,:escudo)');
        $stmt->execute(compact('nombre','liga','origen','anio','escudo'));
        return (int)$db->lastInsertId();
    }
    public static function actualizar(int $id,string $nombre,string $liga,string $anio,?string $origen=null): void
    {
        $datos=[':id'=>$id,':nombre'=>$nombre,':liga'=>$liga,':anio'=>$anio];
        $sql='UPDATE equipos SET nombre=:nombre,liga=:liga,anio=:anio';
        if($origen!==null){$sql.=',origen=:origen';$datos[':origen']=$origen;}
        Database::connection()->prepare($sql.' WHERE id=:id')->execute($datos);
    }
    public static function actualizarEscudo(int $id,string $escudo): void
    {
        Database::connection()->prepare('UPDATE equipos SET escudo=:escudo WHERE id=:id')->execute([':escudo'=>$escudo,':id'=>$id]);
    }
    public static function eliminar(int $id): void
    {
        Database::connection()->prepare('DELETE FROM equipos WHERE id=:id')->execute([':id'=>$id]);
    }
}
