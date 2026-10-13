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

// Catálogos de ligas y validación de escudos de la versión original.
/** Origen y liga; solo los administradores registran o modifican equipos. */
function ligas_internacionales(): array {
    return ['LaLiga', 'Bundesliga', 'Serie A', 'Premier League', 'Ligue 1', 'J-League', 'Liga MX', 'Champions League'];
}
function ligas_nacionales(): array {
    return ['Liga 1 (Perú)', 'Liga 2 (Perú)', 'Liga 3 (Perú)', 'Copa Perú', 'Liga Femenina (Perú)'];
}
function origen_equipo(array $equipo): string {
    if (in_array(($equipo['origen'] ?? ''), ['nacional', 'internacional'], true)) {
        return $equipo['origen'];
    }
    return in_array(($equipo['liga'] ?? ''), ligas_nacionales(), true) ? 'nacional' : 'internacional';
}
function validar_liga_equipo(array $datos): array {
    $origen = (string)($datos['origen'] ?? 'internacional');
    if (!in_array($origen, ['nacional', 'internacional'], true)) {
        throw new InvalidArgumentException('Selecciona nacional o internacional.');
    }
    if ($origen === 'internacional') {
        $liga = trim((string)($datos['liga'] ?? ''));
        if (!in_array($liga, ligas_internacionales(), true)) {
            throw new InvalidArgumentException('Selecciona una liga internacional válida.');
        }
    } else {
        $liga = trim((string)($datos['liga_nacional'] ?? ''));
        if ($liga === '__otra__') {
            $liga = trim((string)($datos['liga_nacional_otra'] ?? ''));
        } elseif (!in_array($liga, ligas_nacionales(), true)) {
            throw new InvalidArgumentException('Selecciona una liga nacional válida.');
        }
        if ($liga === '' || (function_exists('mb_strlen') ? mb_strlen($liga, 'UTF-8') : strlen($liga)) > 90 || preg_match('/[<>\x00-\x1F]/u', $liga)) {
            throw new InvalidArgumentException('Escribe un nombre de liga nacional válido (máximo 90 caracteres).');
        }
    }
    return [$origen, $liga];
}
/** Almacena el escudo de un equipo, tras validar su archivo y tipo real. */
function guardar_escudo_subido(array $archivo): string
{
    $error = (int)($archivo['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        if ($error === UPLOAD_ERR_NO_FILE) {
            throw new InvalidArgumentException('Selecciona una imagen para el escudo.');
        }
        throw new InvalidArgumentException('No se pudo subir el escudo (código ' . $error . '). Comprueba el tamaño del archivo.');
    }

    $temporal = $archivo['tmp_name'] ?? '';
    $tamano = (int)($archivo['size'] ?? 0);
    if (!is_string($temporal) || !is_uploaded_file($temporal)) {
        throw new InvalidArgumentException('El archivo del escudo no se recibió correctamente.');
    }
    if ($tamano <= 0 || $tamano > 5 * 1024 * 1024) {
        throw new InvalidArgumentException('El escudo debe pesar como máximo 5 MB.');
    }

    $info = @getimagesize($temporal);
    $tipos = [
        IMAGETYPE_JPEG => ['image/jpeg', 'jpg'],
        IMAGETYPE_PNG => ['image/png', 'png'],
        IMAGETYPE_WEBP => ['image/webp', 'webp']
    ];
    if ($info === false || !isset($tipos[$info[2] ?? -1])) {
        throw new InvalidArgumentException('El escudo debe ser una imagen JPG, PNG o WEBP válida.');
    }
    [$mimeEsperado, $extension] = $tipos[$info[2]];
    $mimeReal = (new finfo(FILEINFO_MIME_TYPE))->file($temporal);
    if ($mimeReal !== $mimeEsperado) {
        throw new InvalidArgumentException('El tipo de imagen no coincide con el contenido del archivo.');
    }
    if (($info[0] ?? 0) < 1 || ($info[1] ?? 0) < 1 || ($info[0] ?? 0) > 6000 || ($info[1] ?? 0) > 6000) {
        throw new InvalidArgumentException('El escudo debe tener dimensiones válidas (máximo 6000 × 6000 px).');
    }

    $carpeta = GOOLPASS_UPLOADS_DIR;
    if (!is_dir($carpeta) && !mkdir($carpeta, 0755, true) && !is_dir($carpeta)) {
        throw new RuntimeException('No se pudo crear la carpeta uploads.');
    }
    if (!is_writable($carpeta)) {
        throw new RuntimeException('La carpeta uploads no tiene permisos de escritura.');
    }

    $nombre = 'escudo_' . bin2hex(random_bytes(16)) . '.' . $extension;
    if (!move_uploaded_file($temporal, $carpeta . '/' . $nombre)) {
        throw new RuntimeException('No se pudo guardar la imagen en uploads.');
    }
    return $nombre;
}
