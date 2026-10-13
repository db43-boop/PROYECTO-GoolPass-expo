<?php
/** Acciones de Torneo migradas del proyecto entregado. */
final class TorneoController {
    public static function torneos(): void
    {
        $conexion = Database::connection();
        require_once GOOLPASS_ROOT . '/app/Controllers/AuthController.php';
        requerir_login();

        try {
            $equipos = Equipo::opcionesTorneo();

        } catch (PDOException $e) {
            die("Error al consultar equipos: " . $e->getMessage());
        }

        $json_equipos = json_encode($equipos, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE);
        View::render('torneos', get_defined_vars());
    }

    public static function api(): void
    {
        $conexion = Database::connection();
        require_once GOOLPASS_ROOT . '/app/Controllers/AuthController.php';
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, private');

        function responder_torneo(int $codigo, array $respuesta): void
        {
            http_response_code($codigo);
            echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        if (!esta_logueado()) {
            responder_torneo(401, ['ok' => false, 'error' => 'Inicia sesión para consultar el torneo.']);
        }

        function origen_valido($valor): bool
        {
            return is_string($valor) && in_array($valor, ['nacional', 'internacional'], true);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $origen = $_GET['origen'] ?? '';
            if (!origen_valido($origen)) responder_torneo(400, ['ok' => false, 'error' => 'Origen no válido.']);
            $fila = Torneo::buscar($origen);
            responder_torneo(200, ['ok' => true, 'torneo' => $fila ? json_decode($fila['estado_json'], true) : null, 'actualizado_en' => $fila['actualizado_en'] ?? null]);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Allow: GET, POST');
            responder_torneo(405, ['ok' => false, 'error' => 'Método no permitido.']);
        }
        if (!es_admin()) responder_torneo(403, ['ok' => false, 'error' => 'Solo el administrador puede modificar torneos.']);

        $datos = json_decode(file_get_contents('php://input'), true);
        if (!is_array($datos)) responder_torneo(400, ['ok' => false, 'error' => 'Solicitud JSON inválida.']);
        $token = $datos['csrf_token'] ?? '';
        if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
            responder_torneo(403, ['ok' => false, 'error' => 'Token de seguridad no válido. Recarga la página.']);
        }
        $origen = $datos['origen'] ?? '';
        if (!origen_valido($origen)) responder_torneo(400, ['ok' => false, 'error' => 'Origen no válido.']);

        if (($datos['accion'] ?? '') === 'reiniciar') {
            Torneo::eliminar($origen);
            responder_torneo(200, ['ok' => true]);
        }
        if (($datos['accion'] ?? '') !== 'guardar' || !is_array($datos['torneo'] ?? null)) {
            responder_torneo(400, ['ok' => false, 'error' => 'Acción no válida.']);
        }

        try {
            $estado = $datos['torneo'];
            $fase = $estado['fase'] ?? null;
            if (!in_array($fase, ['grupos', 'eliminatorias', 'finalizado'], true)) throw new RuntimeException('Fase del torneo inválida.');
            $cantidad = $estado['cantidad_grupos'] ?? null;
            if (!is_int($cantidad) || $cantidad < 2 || $cantidad > 26) throw new RuntimeException('Cantidad de grupos inválida.');
            $orden = $estado['orden'] ?? null;
            $clasificados = $estado['clasificados'] ?? null;
            $rondas = $estado['rondas'] ?? null;
            $campeon = $estado['campeon'] ?? null;
            if (!is_array($orden) || !array_is_list($orden) || !is_array($clasificados) || !array_is_list($clasificados) || !is_array($rondas)) {
                throw new RuntimeException('Datos del torneo incorrectos.');
            }
            $disponibles = Equipo::idsPorOrigen($origen);
            sort($disponibles);
            $ids = array_map('intval', $orden);
            $comparar = $ids;
            sort($comparar);
            if (count($ids) < 4 || count($ids) < $cantidad || $comparar !== $disponibles || count($ids) !== count(array_unique($ids))) {
                throw new RuntimeException('Los equipos sorteados no coinciden con los equipos del origen seleccionado.');
            }
            foreach ($orden as $id) if (!is_int($id)) throw new RuntimeException('ID de equipo no válido.');

            $c = count($ids);
            $porGrupo = intdiv($c, $cantidad);
            $extras = $c % $cantidad;
            $grupos = [];
            $indice = 0;
            for ($i = 0; $i < $cantidad; $i++) {
                $longitud = $porGrupo + ($i < $extras ? 1 : 0);
                $grupos[] = array_slice($ids, $indice, $longitud);
                $indice += $longitud;
            }
            if (count($clasificados) > $cantidad || count($clasificados) !== count(array_unique($clasificados))) {
                throw new RuntimeException('Solo puede haber un equipo clasificado por grupo.');
            }
            foreach ($clasificados as $id) {
                if (!is_int($id)) throw new RuntimeException('Clasificado inválido.');
            }
            foreach ($grupos as $grupo) {
                if (count(array_intersect($grupo, $clasificados)) > 1) {
                    throw new RuntimeException('No se pueden clasificar dos equipos del mismo grupo.');
                }
            }
            if (count(array_diff($clasificados, $ids))) throw new RuntimeException('El clasificado no pertenece al torneo.');
            if ($fase !== 'grupos' && count($clasificados) < 2) throw new RuntimeException('Selecciona al menos dos clasificados.');
            if ($fase === 'grupos' && ($rondas !== [] || $campeon !== null)) throw new RuntimeException('La fase de grupos no debe tener resultados de eliminatorias.');
            if ($campeon !== null && !is_int($campeon)) throw new RuntimeException('Campeón inválido.');

            // Verificar cada ganador contra sus dos contendientes reales, ronda por ronda.
            // null = pase libre; false = enfrentamiento aún pendiente.
            $tam = 2;
            while ($tam < count($clasificados)) $tam *= 2;
            $entradas = array_merge($clasificados, array_fill(0, $tam - count($clasificados), null));
            $clavesProcesadas = [];
            $r = 0;
            while ($tam >= 2) {
                $salidas = [];
                for ($m = 0; $m < $tam / 2; $m++) {
                    $a = $entradas[$m * 2];
                    $b = $entradas[$m * 2 + 1];
                    $clave = "r{$r}_m{$m}";
                    if (array_key_exists($clave, $rondas)) {
                        if (!is_int($rondas[$clave]) || !is_int($a) || !is_int($b) || !in_array($rondas[$clave], [$a, $b], true)) {
                            throw new RuntimeException('El resultado de la eliminatoria no corresponde a sus participantes.');
                        }
                        $clavesProcesadas[] = $clave;
                        $salidas[] = $rondas[$clave];
                    } elseif ($a === false || $b === false) {
                        $salidas[] = false;
                    } elseif ($a === null && $b === null) {
                        $salidas[] = null;
                    } elseif ($a === null) {
                        $salidas[] = $b;
                    } elseif ($b === null) {
                        $salidas[] = $a;
                    } else {
                        $salidas[] = false;
                    }
                }
                $entradas = $salidas;
                $tam = intdiv($tam, 2);
                $r++;
            }
            if (count($rondas) !== count($clavesProcesadas)) throw new RuntimeException('Hay resultados de rondas no válidas.');
            $ganadorFinal = $entradas[0] ?? false;
            if ($fase === 'finalizado' && (!is_int($ganadorFinal) || $campeon !== $ganadorFinal)) {
                throw new RuntimeException('El campeón debe ganar la final antes de publicarlo.');
            }
            if ($fase !== 'finalizado' && $campeon !== null) throw new RuntimeException('Confirma el campeón antes de publicarlo.');

            $limpio = [
                'fase' => $fase,
                'cantidad_grupos' => $cantidad,
                'orden' => $ids,
                'clasificados' => $clasificados,
                'rondas' => $rondas,
                'campeon' => $campeon,
            ];
            Torneo::guardar($origen, $limpio);
            responder_torneo(200, ['ok' => true, 'actualizado_en' => date('Y-m-d H:i:s')]);
        } catch (RuntimeException $e) {
            responder_torneo(422, ['ok' => false, 'error' => $e->getMessage()]);
        } catch (PDOException $e) {
            error_log('GoolPass torneo: ' . $e->getMessage());
            responder_torneo(500, ['ok' => false, 'error' => 'No se pudo guardar el torneo en la base de datos.']);
        }
    }

}
