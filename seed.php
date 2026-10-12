<?php
declare(strict_types=1);

// Seeder de demostración. Se ejecuta solo por CLI para que no quede expuesto por Apache.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Este script solo se puede ejecutar desde la consola.');
}

require_once __DIR__ . '/app/bootstrap.php';

$db = Database::connection();
$db->beginTransaction();

try {
    $demoUser = seedDemoUser($db);

    $teams = [
        'universitario' => seedDemoTeam($db, 'Universitario Demo', 'Liga 1 (Perú)', 'nacional'),
        'alianza' => seedDemoTeam($db, 'Alianza Lima Demo', 'Liga 1 (Perú)', 'nacional'),
        'cristal' => seedDemoTeam($db, 'Sporting Cristal Demo', 'Liga 1 (Perú)', 'nacional'),
        'melgar' => seedDemoTeam($db, 'FBC Melgar Demo', 'Liga 1 (Perú)', 'nacional'),
        'madrid' => seedDemoTeam($db, 'Madrid FC Demo', 'LaLiga (España)', 'internacional'),
        'barcelona' => seedDemoTeam($db, 'Barcelona FC Demo', 'LaLiga (España)', 'internacional'),
    ];

    $tournaments = [
        'nacional' => seedDemoTournament($db, 'Copa GoolPass Nacional Demo 2026', 'nacional', '2026-11-01', '2026-12-15'),
        'internacional' => seedDemoTournament($db, 'Copa GoolPass Internacional Demo 2026', 'internacional', '2026-11-01', '2026-12-20'),
    ];

    foreach (['universitario', 'alianza', 'cristal', 'melgar'] as $teamKey) {
        seedDemoEnrollment($db, $tournaments['nacional'], $teams[$teamKey]);
    }
    foreach (['madrid', 'barcelona'] as $teamKey) {
        seedDemoEnrollment($db, $tournaments['internacional'], $teams[$teamKey]);
    }

    $players = [
        'universitario' => seedDemoPlayers($db, $teams['universitario'], [
            ['Jugador Demo U1', 'Delantero', 9], ['Jugador Demo U2', 'Portero', 1],
        ]),
        'alianza' => seedDemoPlayers($db, $teams['alianza'], [
            ['Jugador Demo A1', 'Delantero', 10], ['Jugador Demo A2', 'Defensa', 4],
        ]),
        'cristal' => seedDemoPlayers($db, $teams['cristal'], [
            ['Jugador Demo SC1', 'Mediocampista', 8], ['Jugador Demo SC2', 'Delantero', 11],
        ]),
        'melgar' => seedDemoPlayers($db, $teams['melgar'], [
            ['Jugador Demo M1', 'Defensa', 5], ['Jugador Demo M2', 'Portero', 1],
        ]),
        'madrid' => seedDemoPlayers($db, $teams['madrid'], [
            ['Jugador Demo R1', 'Delantero', 9], ['Jugador Demo R2', 'Mediocampista', 8],
        ]),
        'barcelona' => seedDemoPlayers($db, $teams['barcelona'], [
            ['Jugador Demo B1', 'Delantero', 10], ['Jugador Demo B2', 'Defensa', 3],
        ]),
    ];

    $matches = [
        'clasico' => seedDemoMatch($db, $tournaments['nacional'], $teams['universitario'], $teams['alianza'], '2026-11-14 19:00:00', 'Estadio Monumental (Lima)', 45.00, 80000),
        'sur' => seedDemoMatch($db, $tournaments['nacional'], $teams['cristal'], $teams['melgar'], '2026-11-15 17:00:00', 'Estadio Monumental de la UNSA (Arequipa)', 30.00, 60000),
        'internacional' => seedDemoMatch($db, $tournaments['internacional'], $teams['madrid'], $teams['barcelona'], '2026-11-21 20:00:00', 'Santiago Bernabéu (Madrid)', 80.00, 81000),
    ];

    seedDemoStatistic($db, $matches['clasico'], $players['universitario'][0], 1, 1, 0, 0, true);
    seedDemoStatistic($db, $matches['clasico'], $players['alianza'][0], 1, 0, 0, 0, false);
    seedDemoStatistic($db, $matches['sur'], $players['cristal'][0], 0, 1, 1, 0, true);
    seedDemoStatistic($db, $matches['internacional'], $players['madrid'][0], 2, 0, 0, 0, true);
    seedDemoStatistic($db, $matches['internacional'], $players['barcelona'][0], 1, 1, 0, 0, false);

    seedDemoTicket($db, $matches['clasico'], $demoUser, 'GP-DEMO-NACIONAL-001', 'pagado', 45.00, null, null);
    seedDemoTicket($db, $matches['clasico'], null, 'GP-DEMO-NACIONAL-002', 'pendiente', 45.00, 'Invitado Demo', 'invitado.demo@example.test');
    seedDemoTicket($db, $matches['sur'], $demoUser, 'GP-DEMO-SUR-001', 'pagado', 30.00, null, null);
    seedDemoTicket($db, $matches['internacional'], null, 'GP-DEMO-INTERNACIONAL-001', 'pendiente', 80.00, 'Invitado Internacional Demo', 'internacional.demo@example.test');

    $db->commit();
    echo "Datos de demostración listos. No se borraron datos existentes.\n";
    echo "Cuenta demo: demo@goolpass.local / GoolpassDemo2026!\n";
} catch (Throwable $error) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, 'No se pudieron cargar los datos de demostración: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}

function seedDemoUser(PDO $db): int
{
    $email = 'demo@goolpass.local';
    $query = $db->prepare('SELECT id FROM usuarios WHERE email = :email');
    $query->execute([':email' => $email]);
    $id = $query->fetchColumn();
    if ($id !== false) {
        return (int)$id;
    }

    $insert = $db->prepare('INSERT INTO usuarios (nombre,email,password) VALUES (:nombre,:email,:password)');
    $insert->execute([
        ':nombre' => 'Usuario Demo GoolPass',
        ':email' => $email,
        ':password' => password_hash('GoolpassDemo2026!', PASSWORD_DEFAULT),
    ]);
    return (int)$db->lastInsertId();
}

function seedDemoTeam(PDO $db, string $name, string $league, string $origin): int
{
    $query = $db->prepare('SELECT id FROM equipos WHERE nombre = :nombre AND liga = :liga AND origen = :origen');
    $query->execute([':nombre' => $name, ':liga' => $league, ':origen' => $origin]);
    $id = $query->fetchColumn();
    if ($id !== false) {
        return (int)$id;
    }

    $insert = $db->prepare('INSERT INTO equipos (nombre,liga,origen) VALUES (:nombre,:liga,:origen)');
    $insert->execute([':nombre' => $name, ':liga' => $league, ':origen' => $origin]);
    return (int)$db->lastInsertId();
}

function seedDemoTournament(PDO $db, string $name, string $category, string $start, string $end): int
{
    $query = $db->prepare('SELECT id FROM torneos WHERE nombre = :nombre AND categoria = :categoria');
    $query->execute([':nombre' => $name, ':categoria' => $category]);
    $id = $query->fetchColumn();
    if ($id !== false) {
        return (int)$id;
    }

    $insert = $db->prepare("INSERT INTO torneos (nombre,categoria,formato,estado,inscripciones_abiertas,fecha_inicio,fecha_fin) VALUES (:nombre,:categoria,'liga','abierto',1,:inicio,:fin)");
    $insert->execute([':nombre' => $name, ':categoria' => $category, ':inicio' => $start, ':fin' => $end]);
    return (int)$db->lastInsertId();
}

function seedDemoEnrollment(PDO $db, int $tournamentId, int $teamId): void
{
    $query = $db->prepare('SELECT torneo_id FROM inscripciones WHERE equipo_id = :equipo LIMIT 1');
    $query->execute([':equipo' => $teamId]);
    $registeredTournament = $query->fetchColumn();
    if ($registeredTournament !== false) {
        if ((int)$registeredTournament !== $tournamentId) {
            throw new RuntimeException('Un equipo demo ya está inscrito en otro torneo; no se modificó esa inscripción.');
        }
        return;
    }

    $insert = $db->prepare('INSERT INTO inscripciones (torneo_id,equipo_id) VALUES (:torneo,:equipo)');
    $insert->execute([':torneo' => $tournamentId, ':equipo' => $teamId]);
}

function seedDemoPlayers(PDO $db, int $teamId, array $roster): array
{
    $ids = [];
    foreach ($roster as [$name, $position, $number]) {
        $query = $db->prepare('SELECT id FROM jugadores WHERE equipo_id = :equipo AND nombre = :nombre');
        $query->execute([':equipo' => $teamId, ':nombre' => $name]);
        $id = $query->fetchColumn();
        if ($id === false) {
            $insert = $db->prepare('INSERT INTO jugadores (equipo_id,nombre,posicion,numero_camiseta) VALUES (:equipo,:nombre,:posicion,:numero)');
            $insert->execute([':equipo' => $teamId, ':nombre' => $name, ':posicion' => $position, ':numero' => $number]);
            $id = $db->lastInsertId();
        }
        $ids[] = (int)$id;
    }
    return $ids;
}

function seedDemoMatch(PDO $db, int $tournamentId, int $homeId, int $awayId, string $date, string $stadium, float $price, int $capacity): int
{
    $query = $db->prepare('SELECT id FROM partidos WHERE torneo_id = :torneo AND equipo_local_id = :local AND equipo_visitante_id = :visitante AND fecha_hora = :fecha');
    $query->execute([':torneo' => $tournamentId, ':local' => $homeId, ':visitante' => $awayId, ':fecha' => $date]);
    $id = $query->fetchColumn();
    if ($id !== false) {
        return (int)$id;
    }

    $insert = $db->prepare("INSERT INTO partidos (torneo_id,equipo_local_id,equipo_visitante_id,fecha_hora,estadio,precio_entrada,aforo,estado) VALUES (:torneo,:local,:visitante,:fecha,:estadio,:precio,:aforo,'programado')");
    $insert->execute([':torneo' => $tournamentId, ':local' => $homeId, ':visitante' => $awayId, ':fecha' => $date, ':estadio' => $stadium, ':precio' => $price, ':aforo' => $capacity]);
    return (int)$db->lastInsertId();
}

function seedDemoStatistic(PDO $db, int $matchId, int $playerId, int $goals, int $assists, int $yellow, int $red, bool $mvp): void
{
    $insert = $db->prepare('INSERT OR IGNORE INTO estadisticas_partido (partido_id,jugador_id,goles,asistencias,tarjetas_amarillas,tarjetas_rojas,fue_mvp) VALUES (:partido,:jugador,:goles,:asistencias,:amarillas,:rojas,:mvp)');
    $insert->execute([':partido' => $matchId, ':jugador' => $playerId, ':goles' => $goals, ':asistencias' => $assists, ':amarillas' => $yellow, ':rojas' => $red, ':mvp' => $mvp ? 1 : 0]);
}

function seedDemoTicket(PDO $db, int $matchId, ?int $userId, string $code, string $payment, float $price, ?string $name, ?string $email): void
{
    $insert = $db->prepare("INSERT OR IGNORE INTO entradas (usuario_id,partido_id,estado_pago,estado_entrada,codigo_qr,nombre_comprador,email_comprador,precio_pagado) VALUES (:usuario,:partido,:pago,'activa',:codigo,:nombre,:email,:precio)");
    $insert->execute([':usuario' => $userId, ':partido' => $matchId, ':pago' => $payment, ':codigo' => $code, ':nombre' => $name, ':email' => $email, ':precio' => $price]);
}
