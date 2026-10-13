<?php
/** Acciones de Partido migradas del proyecto entregado. */
final class PartidoController {
    public static function partidos(): void
    {
        $conexion = Database::connection();
        require_once GOOLPASS_ROOT . '/app/Controllers/AuthController.php';
        requerir_login();

        $esAdmin = es_admin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verificar_csrf();
            if (in_array(($_POST['accion'] ?? ''), ['crear_partido', 'actualizar_resultado'], true)) {
                requerir_admin();
            } elseif (($_POST['accion'] ?? '') === 'pagar_entrada' && $esAdmin) {
                http_response_code(403);
                exit('Los pagos de entradas están disponibles únicamente para usuarios.');
            }
        }
        $mensaje = '';
        $error = '';

        // Las fechas de GoolPass se registran en la hora local de Perú.
        // Únicamente pueden comprarse entradas de encuentros programados que aún no empiezan.
        function partido_permite_compra(array $partido): bool
        {
            if (($partido['estado'] ?? 'Programado') !== 'Programado') {
                return false;
            }
            $fecha = trim((string)($partido['fecha_hora'] ?? ''));
            if ($fecha === '') {
                return false;
            }
            try {
                $zona = new DateTimeZone('America/Lima');
                $inicio = new DateTimeImmutable($fecha, $zona);
                return $inicio > new DateTimeImmutable('now', $zona);
            } catch (Exception $e) {
                return false;
            }
        }

        function texto_compra_no_disponible(array $partido): string
        {
            $estado = (string)($partido['estado'] ?? 'Programado');
            if ($estado === 'Finalizado') return 'Partido finalizado · Venta cerrada';
            if ($estado === 'En juego') return 'Partido en juego · Venta cerrada';
            return 'Partido iniciado o fecha pasada · Venta cerrada';
        }

        // Crear partido (solo administradores)
        if ($esAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['accion']) && $_POST['accion'] === 'crear_partido') {
            $local = (int)($_POST['equipo_local_id'] ?? 0);
            $visitante = (int)($_POST['equipo_visitante_id'] ?? 0);
            $origenPartido = (string)($_POST['origen_partido'] ?? '');
            $fecha = trim($_POST['fecha_hora'] ?? '');
            $estadio = trim($_POST['estadio'] ?? '');
            $precio = (float)($_POST['precio_entrada'] ?? 0);

            if (!in_array($origenPartido, ['nacional', 'internacional'], true)) {
                $error = 'Selecciona si el partido es nacional o internacional.';
            } elseif ($local <= 0 || $visitante <= 0) {
                $error = 'Elige los dos equipos del partido.';
            } elseif ($local === $visitante) {
                $error = 'El equipo local y el visitante no pueden ser el mismo.';
            } elseif ($fecha === '' || $estadio === '') {
                $error = 'Completa la fecha y el estadio del partido.';
            } elseif ($precio < 0) {
                $error = 'El precio no puede ser negativo.';
            } else {
                try {
                    // La categoría se comprueba en SQLite, no solo en el selector de la página.
                    $origenes = Equipo::origenesDeEquipos($local, $visitante);

                    if (count($origenes) !== 2) {
                        $error = 'Uno o ambos equipos seleccionados no existen.';
                    } elseif ($origenes[$local] !== $origenPartido || $origenes[$visitante] !== $origenPartido) {
                        $error = 'Los dos equipos deben pertenecer al origen seleccionado: nacional con nacional o internacional con internacional.';
                    } else {
                        Partido::crear($local, $visitante, $fecha, $estadio, $precio);
                        $mensaje = 'Partido creado correctamente.';
                    }
                } catch (PDOException $e) {
                    error_log('GoolPass: error al crear un partido: ' . $e->getMessage());
                    $error = 'No se pudo crear el partido. Inténtalo nuevamente.';
                }
            }
        }

        // Registrar marcador: solo administradores, validación del servidor y token CSRF.
        if ($esAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'actualizar_resultado') {
            $partidoResultado = filter_var($_POST['partido_id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
            $localGoles = filter_var($_POST['goles_local'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>0, 'max_range'=>99]]);
            $visitanteGoles = filter_var($_POST['goles_visitante'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>0, 'max_range'=>99]]);
            $estadoPartido = (string)($_POST['estado'] ?? '');
            if (!$partidoResultado || $localGoles === false || $visitanteGoles === false || !in_array($estadoPartido, ['Programado', 'En juego', 'Finalizado'], true)) {
                $error = 'Comprueba el partido, el estado y los goles (entre 0 y 99).';
            } elseif ($estadoPartido === 'Programado' && ($localGoles !== 0 || $visitanteGoles !== 0)) {
                $error = 'Un partido programado debe mostrar 0-0. Usa En juego o Finalizado para registrar goles.';
            } else {
                try {
                    $filas = Partido::actualizarResultado($partidoResultado, $localGoles, $visitanteGoles, $estadoPartido);
                    if ($filas === 0 && !Partido::existe($partidoResultado)) {
                        $error = 'Ese partido no existe.';
                    }
                    if ($error === '') $mensaje = 'Resultado actualizado correctamente.';
                } catch (PDOException $e) {
                    error_log('GoolPass: error al guardar marcador: '.$e->getMessage());
                    $error = 'No se pudo actualizar el resultado del partido.';
                }
            }
        }

        // Equipos disponibles y cantidad por origen para el formulario del administrador.
        $equipos = [];
        $equiposPorOrigen = ['nacional' => 0, 'internacional' => 0];
        if ($esAdmin) {
            try {
                $equipos = Equipo::opciones();
                foreach ($equipos as $equipo) {
                    $origen = (string)($equipo['origen'] ?? '');
                    if (array_key_exists($origen, $equiposPorOrigen)) {
                        $equiposPorOrigen[$origen]++;
                    }
                }
            } catch (PDOException $e) {
                error_log('GoolPass: no se pudo cargar la lista de equipos: ' . $e->getMessage());
                $equipos = [];
            }
        }

        // Compra SIMULADA de entrada: no se guardan ni procesan datos reales de tarjetas.
        if (!$esAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'pagar_entrada') {
            $partidoId = (int)($_POST['partido_id'] ?? 0);
            $usuarioId = (int)($_SESSION['usuario']['id'] ?? 0);
            $nombre = trim((string)($_POST['nombre_comprador'] ?? ''));
            $tarjetaEscrita = trim((string)($_POST['tarjeta'] ?? ''));
            $tarjeta = preg_replace('/\s+/', '', $tarjetaEscrita);

            if ($usuarioId <= 0) {
                $error = 'Inicia sesión para comprar una entrada.';
            } elseif ($partidoId <= 0) {
                $error = 'Selecciona un partido válido.';
            } elseif ($nombre === '' || strlen($nombre) > 120) {
                $error = 'Ingresa el nombre del comprador (máximo 120 caracteres).';
            } elseif (!preg_match('/^[0-9]{16}$/D', $tarjeta)) {
                $error = 'El número de tarjeta de prueba debe contener exactamente 16 dígitos.';
            } else {
                try {
                    $partidoComprado = Partido::buscarParaCompra($partidoId);
                    if (!$partidoComprado) {
                        $error = 'El partido seleccionado ya no está disponible.';
                    } elseif (!partido_permite_compra($partidoComprado)) {
                        $error = 'La venta de entradas está cerrada: el partido ya comenzó o finalizó.';
                    } else {
                        // Se consulta antes de procesar para dar un mensaje comprensible.
                        // El trigger SQLite también bloquea una segunda compra concurrente.
                        $yaComprada = Entrada::existeCompra($usuarioId, $partidoId);
                        if ($yaComprada) {
                            $error = 'Ya compraste una entrada para este partido. Consulta su código en Mis entradas.';
                        } else {
                        $precioPagado = round((float)$partidoComprado['precio_entrada'], 2);
                        $entradaIdGenerada = 0;
                        for ($intento = 0; $intento < 3; $intento++) {
                            // Código aleatorio de 64 bits. La base de datos verifica su unicidad.
                            $codigo = 'QR-' . strtoupper(bin2hex(random_bytes(8)));
                            try {
                                $entradaIdGenerada = Entrada::guardarCompra($usuarioId, $partidoId, $codigo, $nombre, $precioPagado);
                                if (!$entradaIdGenerada) {
                                    $error = 'El partido ya no acepta compras de entradas.';
                                }
                                break;
                            } catch (PDOException $e) {
                                if ($intento < 2 && stripos($e->getMessage(), 'entradas.codigo_qr') !== false) {
                                    continue;
                                }
                                throw $e;
                            }
                        }
                        if ($error === '') {
                            // Redirigir tras POST evita compras duplicadas al actualizar la página.
                            $_SESSION['entrada_recien_comprada'] = $entradaIdGenerada;
                            header('Location: index.php?r=mis_entradas');
                            exit;
                        }
                        }
                    }
                } catch (PDOException $e) {
                    if (str_contains($e->getMessage(), 'ENTRADA_YA_COMPRADA')) {
                        $error = 'Ya compraste una entrada para este partido. Consulta su código en Mis entradas.';
                    } else {
                        error_log('GoolPass: fallo al registrar una entrada simulada: ' . $e->getMessage());
                        $error = 'No se pudo registrar la entrada. Inténtalo nuevamente.';
                    }
                } catch (Throwable $e) {
                    error_log('GoolPass: error en compra simulada: ' . $e->getMessage());
                    $error = 'Ocurrió un problema al generar el código de entrada.';
                }
            }
        }

        try {
            $partidos = Partido::listarConEquipos();
        } catch (PDOException $e) {
            $partidos = [];
        }

        $entradasCompradas = [];
        if (!$esAdmin) {
            $usuarioId = (int)($_SESSION['usuario']['id'] ?? 0);
            $entradasCompradas = Entrada::partidosComprados($usuarioId);
        }
        View::render('partidos', get_defined_vars());
    }

}
