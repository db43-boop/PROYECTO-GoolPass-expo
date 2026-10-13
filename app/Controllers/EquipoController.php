<?php
/** Acciones de Equipo migradas del proyecto entregado. */
final class EquipoController {
    public static function inicio(): void
    {
        $conexion = Database::connection();
        require_once GOOLPASS_ROOT . '/app/Controllers/AuthController.php';
        requerir_login();

        // --- Datos para la portada (con degradación segura si la BD falla) ---
        $totalEquipos = 0;
        $totalLigas = 0;
        $ultimos = [];
        $jugadoresPorEquipo = [];

        try {
            $totales = Equipo::totales();
            $totalEquipos = $totales['equipos'];
            $totalLigas = $totales['ligas'];
            $ultimos = Equipo::recientes();
            foreach (Jugador::porEquipos(array_column($ultimos, 'id')) as $j) {
                $jugadoresPorEquipo[(int)$j['equipo_id']][] = $j;
            }
        } catch (PDOException $e) {
            error_log('GoolPass inicio: '.$e->getMessage());
            $ultimos = [];
            $jugadoresPorEquipo = [];
        }

        /** Devuelve la inicial para el avatar de respaldo */
        function inicial_avatar($nombre)
        {
            $nombre = trim((string) $nombre);
            if ($nombre === '') return '?';
            return mb_strtoupper(mb_substr($nombre, 0, 1, 'UTF-8'));
        }

        /** Abreviatura y clase de color según la posición del jugador */
        function pos_meta($pos)
        {
            switch ($pos) {
                case 'Portero':         return ['POR', 'por'];
                case 'Defensas':        return ['DEF', 'def'];
                case 'Centrocampistas': return ['MED', 'med'];
                case 'Delanteros':      return ['DEL', 'del'];
                default:                return ['—',   'otro'];
            }
        }
        View::render('layout', array_merge(get_defined_vars(), ['__page' => 'inicio']));
    }

    public static function listar(): void
    {
        $conexion = Database::connection();
        require_once GOOLPASS_ROOT . '/app/Controllers/AuthController.php';
        requerir_login();

        // Se muestran exclusivamente las tarjetas de la categoría seleccionada.
        $origenParam = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['origen'] ?? '') : ($_GET['origen'] ?? '');
        $origenSeleccionado = $origenParam === 'internacional' ? 'internacional' : 'nacional';

        try {
            $equipos = Equipo::listarPorOrigen($origenSeleccionado);
            $conteos = Equipo::conteosPorOrigen();
        } catch (PDOException $e) {
            error_log('GoolPass equipos: ' . $e->getMessage());
            die('Error al consultar equipos.');
        }

        // Solo el administrador puede eliminar equipos. No se realizan eliminaciones por URL (GET).
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            requerir_admin();
            verificar_csrf();
            if (($_POST['accion'] ?? '') !== 'eliminar_equipo') {
                http_response_code(400);
                exit('Acción no válida.');
            }
            $idEliminar = (int)($_POST['equipo_id'] ?? 0);
            if ($idEliminar <= 0) {
                http_response_code(400);
                exit('Equipo no válido.');
            }
            try {
                $conexion->beginTransaction();
                // SQLite aplica ON DELETE CASCADE para la plantilla.
                Equipo::eliminar($idEliminar);
                $conexion->commit();
                header('Location: index.php?r=ver&origen=' . urlencode($origenSeleccionado) . '&mensaje=Equipo+eliminado+correctamente');
                exit;
            } catch (PDOException $e) {
                if ($conexion->inTransaction()) { $conexion->rollBack(); }
                http_response_code(500);
                exit('No se pudo eliminar el equipo. Comprueba si tiene partidos registrados.');
            }
        }

        // (La creación de partidos se gestiona ahora en index.php?r=partidos, solo para admin)
        $error = '';
        View::render('equipos', get_defined_vars());
    }

    public static function ficha(): void
    {
        $conexion = Database::connection();
        require_once GOOLPASS_ROOT . '/app/Controllers/AuthController.php';
        requerir_login();
        require_once GOOLPASS_ROOT . '/app/Models/Jugador.php';
        require_once GOOLPASS_ROOT . '/app/Models/Equipo.php';

        if (!isset($_GET['id']) || empty($_GET['id'])) {
            header("Location: index.php?r=ver");
            exit;
        }

        $id = (int)$_GET['id'];

        try {
            $equipo = Equipo::buscar($id);
            if (!$equipo) die('Equipo no encontrado.');
            $jugadores = Jugador::porEquipo($id);

            // Totales del equipo
            $total_goles = array_sum(array_column($jugadores, 'goles'));
            $total_asistencias = array_sum(array_column($jugadores, 'asistencias'));
            $total_amarillas = array_sum(array_column($jugadores, 'tarjetas_amarillas'));
            $total_rojas = array_sum(array_column($jugadores, 'tarjetas_rojas'));
            $total_mvp = array_sum(array_column($jugadores, 'mvp_count'));
            $total_trofeos = array_sum(array_column($jugadores, 'trofeos_ganados'));
        } catch (PDOException $e) {
            die("Error al consultar equipo: " . $e->getMessage());
        }

        // Mostrar solo archivos locales válidos que realmente existan en uploads.
        $archivoEscudo = basename((string)($equipo['escudo'] ?? ''));
        $tieneEscudo = $archivoEscudo !== '' && $archivoEscudo !== 'default.png'
            && is_file(GOOLPASS_UPLOADS_DIR . '/' . $archivoEscudo);
        $srcEscudo = $tieneEscudo ? 'uploads/' . rawurlencode($archivoEscudo) : '';

        $color = '#2196F3';
        View::render('equipo', get_defined_vars());
    }

    public static function crear(): void
    {
        $conexion = Database::connection();
        require_once GOOLPASS_ROOT . '/app/Controllers/AuthController.php';
        requerir_admin();
        require_once GOOLPASS_ROOT . '/app/Models/Equipo.php';
        $origenActual = 'internacional';
        $ligaActual = '';
        View::render('equipo_form', array_merge(get_defined_vars(), ['__mode' => 'crear']));
    }

    public static function editar(): void
    {
        $conexion = Database::connection();
        require_once GOOLPASS_ROOT . '/app/Controllers/AuthController.php';
        requerir_admin();
        require_once GOOLPASS_ROOT . '/app/Models/Equipo.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') { verificar_csrf(); }

        $mensaje = "";
        $tipo_mensaje = "info";

        if (!isset($_GET['id']) || empty($_GET['id'])) {
            header("Location: index.php?r=ver");
            exit;
        }

        $id = (int)$_GET['id'];

        // --- Lista de ligas (coincide con index.php?r=crear) ---
        $ligas = ligas_internacionales();

        /** Opciones del <select> de posición marcando la actual. */
        function opciones_posicion($actual)
        {
            $vals = ['Portero', 'Defensas', 'Centrocampistas', 'Delanteros'];
            $out = '<option value="">Selecciona…</option>';
            foreach ($vals as $v) {
                $sel = ($v === $actual) ? ' selected' : '';
                $out .= '<option value="' . $v . '"' . $sel . '>' . $v . '</option>';
            }
            return $out;
        }

        // --- Procesar actualización ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['accion_editar'])) {
            $nombre = trim($_POST['nombre'] ?? '');
            try {
                [$origen, $liga] = validar_liga_equipo($_POST);
            } catch (InvalidArgumentException $e) {
                http_response_code(400);
                exit('Error al actualizar equipo: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
            }
            $anio   = trim($_POST['anio'] ?? '');

            if ($nombre === '') {
                $mensaje = "Por favor, completa el nombre.";
                $tipo_mensaje = "error";
            } elseif ($anio !== '' && (!preg_match('/^\d{4}$/', $anio) || (int)$anio < 1800 || (int)$anio > 2100)) {
                $mensaje = "El año del club debe estar entre 1800 y 2100.";
                $tipo_mensaje = "error";
            } else {
                try {
                    $conexion->beginTransaction();
                    Equipo::actualizar($id, $nombre, $liga, $anio, $origen);
                    Jugador::reemplazarPlantilla($id, is_array($_POST['jugadores'] ?? null) ? $_POST['jugadores'] : []);

                    $conexion->commit();
                    header("Location: index.php?r=ver&mensaje=" . urlencode("Equipo actualizado correctamente."));
                    exit;
                } catch (PDOException $e) {
                    if ($conexion->inTransaction()) $conexion->rollBack();
                    $mensaje = "Error al actualizar: " . $e->getMessage();
                    $tipo_mensaje = "error";
                }
            }
        }

        // --- Cargar datos del equipo y sus jugadores ---
        try {
            $equipo = Equipo::buscar($id);
            if (!$equipo) die('Equipo no encontrado.');
            $jugadores = Jugador::porEquipo($id);
        } catch (PDOException $e) {
            die("Error al consultar equipo: " . $e->getMessage());
        }
        ?>
        <?php
        $origenActual = origen_equipo($equipo);
        $ligaActual = (string)($equipo['liga'] ?? '');
        View::render('equipo_form', array_merge(get_defined_vars(), ['__mode' => 'editar']));
    }

    public static function guardar(): void
    {
        $conexion = Database::connection();
        require_once GOOLPASS_ROOT . '/app/Controllers/AuthController.php';
        require_once GOOLPASS_ROOT . '/app/Models/Equipo.php';
        require_once GOOLPASS_ROOT . '/app/Models/Equipo.php';
        requerir_admin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Método no permitido.');
        }
        verificar_csrf();

        $mensaje = "";
        $tipo_mensaje = "";

        // ============================================================
        // 1. CREAR EQUIPO  (+ jugadores)
        // ============================================================
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear_equipo') {
            $nombre = trim($_POST['nombre'] ?? '');
            $anio   = trim($_POST['anio'] ?? '');
            try {
                [$origen, $liga] = validar_liga_equipo($_POST);
            } catch (InvalidArgumentException $e) {
                http_response_code(400);
                exit('Error al registrar equipo: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
            }

            if ($nombre === '' || $liga === '') {
                $mensaje = "Por favor, completa nombre y liga.";
                $tipo_mensaje = "error";
            } elseif ($anio !== '' && (!preg_match('/^\d{4}$/', $anio) || (int)$anio < 1800 || (int)$anio > 2100)) {
                $mensaje = "El año del club debe estar entre 1800 y 2100.";
                $tipo_mensaje = "error";
            } else {
                try {
                    $jugadores = $_POST['jugadores'] ?? [];
                    if (!is_array($jugadores)) {
                        throw new InvalidArgumentException('La plantilla de jugadores no es válida.');
                    }
                    foreach ($jugadores as $jugador) {
                        if (!is_array($jugador) || trim((string)($jugador['nombre'] ?? '')) === '' || trim((string)($jugador['posicion'] ?? '')) === '') {
                            throw new InvalidArgumentException('Completa el nombre y la posición de cada jugador agregado.');
                        }
                    }

                    // Primero almacenar la imagen real. Si falla la BD, se retira el archivo subido.
                    $nombreEscudo = guardar_escudo_subido($_FILES['escudo'] ?? []);
                    $conexion->beginTransaction();
                    $equipo_id = Equipo::crear($nombre, $liga, $origen, $anio, $nombreEscudo);
                    foreach ($jugadores as $jugador) {
                        Jugador::insertar($equipo_id, $jugador);
                    }

                    $conexion->commit();
                    header('Location: index.php?r=ficha&id=' . $equipo_id);
                    exit;
                } catch (Throwable $e) {
                    if ($conexion->inTransaction()) {
                        $conexion->rollBack();
                    }
                    if (!empty($nombreEscudo) && is_file(GOOLPASS_UPLOADS_DIR . '/' . $nombreEscudo)) {
                        unlink(GOOLPASS_UPLOADS_DIR . '/' . $nombreEscudo);
                    }
                    $mensaje = $e instanceof InvalidArgumentException
                        ? $e->getMessage()
                        : 'Error al guardar el equipo y sus jugadores: ' . $e->getMessage();
                    $tipo_mensaje = 'error';
                }
                if ($mensaje !== '') {
                    http_response_code(400);
                    header('Content-Type: text/html; charset=utf-8');
                    echo '<!doctype html><html lang="es"><meta charset="utf-8"><title>No se pudo guardar el equipo</title>';
                    echo '<body style="font-family:Arial,sans-serif;background:#121212;color:#fff;max-width:650px;margin:70px auto;padding:24px">';
                    echo '<h1>Error al guardar el equipo</h1><p>' . htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') . '</p>';
                    echo '<a style="color:#ffad45" href="index.php?r=crear">Volver al formulario</a></body></html>';
                    exit;
                }
            }
        }

        // ============================================================
        // 2. ACTUALIZAR EL ESCUDO DE UN EQUIPO EXISTENTE
        // ============================================================
        if (($_POST['accion'] ?? '') === 'actualizar_escudo') {
            $idEscudo = (int)($_POST['equipo_id'] ?? 0);
            $nombreNuevo = null;
            try {
                if ($idEscudo <= 0) {
                    throw new InvalidArgumentException('Equipo no válido.');
                }
                if (!Equipo::existe($idEscudo)) throw new InvalidArgumentException('No se encontró el equipo.');
                $nombreNuevo = guardar_escudo_subido($_FILES['escudo'] ?? []);
                Equipo::actualizarEscudo($idEscudo, $nombreNuevo);
                header('Location: index.php?r=ficha&id=' . $idEscudo . '&mensaje=escudo_actualizado');
                exit;
            } catch (Throwable $e) {
                if ($nombreNuevo !== null && is_file(GOOLPASS_UPLOADS_DIR . '/' . $nombreNuevo)) {
                    unlink(GOOLPASS_UPLOADS_DIR . '/' . $nombreNuevo);
                }
                http_response_code(400);
                header('Content-Type: text/html; charset=utf-8');
                echo '<!doctype html><html lang="es"><meta charset="utf-8"><title>Error al guardar escudo</title>';
                echo '<body style="font-family:Arial;background:#121212;color:white;margin:65px auto;max-width:680px;padding:24px">';
                echo '<h1>No se pudo guardar el escudo</h1><p>' . e($e->getMessage()) . '</p>';
                echo '<a style="color:#ffae55" href="index.php?r=ficha&id=' . $idEscudo . '">Volver a la ficha</a></body></html>';
                exit;
            }
        }

        // ============================================================
        // 3. ACTUALIZAR EQUIPO EXISTENTE (+ jugadores)
        // ============================================================
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['accion_editar'])) {
            $id     = (int)$_POST['accion_editar'];
            $nombre = trim($_POST['nombre'] ?? '');
            $liga   = trim($_POST['liga'] ?? '');
            $anio   = trim($_POST['anio'] ?? '');

            if ($nombre === '') {
                $mensaje = "Por favor, completa el nombre.";
                $tipo_mensaje = "error";
            } elseif ($anio !== '' && (!preg_match('/^\d{4}$/', $anio) || (int)$anio < 1800 || (int)$anio > 2100)) {
                $mensaje = "El año del club debe estar entre 1800 y 2100.";
                $tipo_mensaje = "error";
            } else {
                try {
                    $conexion->beginTransaction();
                    Equipo::actualizar($id, $nombre, $liga, $anio);
                    if (!empty($_POST['jugadores']) && is_array($_POST['jugadores'])) {
                        Jugador::reemplazarPlantilla($id, $_POST['jugadores']);
                    }
                    $conexion->commit();

                    header("Location: index.php?r=ver&mensaje=Equipo+actualizado+correctamente");
                    exit;
                } catch (PDOException $e) {
                    if ($conexion->inTransaction()) $conexion->rollBack();
                    $mensaje = "Error al actualizar el equipo: " . $e->getMessage();
                    $tipo_mensaje = "error";
                }
            }
        }


        // ============================================================
        // 3. ELIMINAR JUGADOR
        // ============================================================
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar_jugador') {
            $id_jugador = (int)($_POST['id_jugador'] ?? 0);
            if ($id_jugador > 0) {
                try {
                    Jugador::eliminar($id_jugador);
                } catch (PDOException $e) {
                    die("Error al eliminar jugador: " . $e->getMessage());
                }
            }
            header("Location: index.php?r=ver");
            exit;
        }

        // ============================================================
        // 4. AGREGAR / EDITAR JUGADOR (desde modal en index.php?r=ficha)
        // ============================================================
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array(($_POST['accion'] ?? ''), ['agregar_jugador', 'editar_jugador'], true)) {
            $accion_player    = $_POST['accion'];
            $id_jugador       = (int)($_POST['id_jugador'] ?? 0);
            $equipo_id        = (int)($_POST['equipo_id'] ?? 0);
            $nombre           = trim($_POST['nombre'] ?? '');
            $posicion         = trim($_POST['posicion'] ?? '');
            $goles            = (int)($_POST['goles'] ?? 0);
            $asistencias      = (int)($_POST['asistencias'] ?? 0);
            $tarjetas_amarillas = (int)($_POST['tarjetas_amarillas'] ?? 0);
            $tarjetas_rojas   = (int)($_POST['tarjetas_rojas'] ?? 0);
            $mvp_count        = (int)($_POST['mvp_count'] ?? 0);
            $trofeos_ganados = max(0, (int)($_POST['trofeos_ganados'] ?? 0));

            // Volver a la ficha del equipo tras procesar
            $volver = $equipo_id > 0 ? "index.php?r=ficha&id={$equipo_id}" : "index.php?r=ver";

            try {
                if ($accion_player === 'agregar_jugador') {
                    if ($equipo_id > 0 && $nombre !== '' && $posicion !== '') {
                        Jugador::insertar($equipo_id, compact('nombre','posicion','goles','asistencias','tarjetas_amarillas','tarjetas_rojas','mvp_count','trofeos_ganados'));
                    }
                } else { // editar_jugador
                    if ($id_jugador > 0 && $equipo_id > 0 && $nombre !== '' && $posicion !== '') {
                        Jugador::actualizarParaEquipo($id_jugador, $equipo_id, compact('nombre','posicion','goles','asistencias','tarjetas_amarillas','tarjetas_rojas','mvp_count','trofeos_ganados'));
                    }
                }
                header("Location: {$volver}");
                exit;
            } catch (PDOException $e) {
                die("Error al guardar jugador: " . $e->getMessage());
            }
        }

        // Si llegamos aquí sin procesar nada, redirigir
        header("Location: index.php?r=ver");
        exit;
    }

    public static function apiDiseno(): void
    {
        $conexion = Database::connection();
        /** Compatibilidad: API anterior de fichas de participantes, retirada del catálogo actual. */
        require_once GOOLPASS_ROOT . '/app/Controllers/AuthController.php';
        requerir_login();
        http_response_code(410);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok'=>false,'error'=>'La función antigua de diseño de participantes ya no está disponible.'], JSON_UNESCAPED_UNICODE);
    }

}
