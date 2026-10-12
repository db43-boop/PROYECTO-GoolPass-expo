<?php
/**
 * Migraciones compatibles con bases antiguas. No elimina registros.
 * Los cambios de estructura están aquí y NO en modelos ni controladores.
 */
final class Schema
{
    public static function migrate(PDO $conexion): void
    {
        // La base actual ya no requiere ejecutar todos los CREATE TABLE en cada página.
        $version = (int)$conexion->query('PRAGMA user_version')->fetchColumn();
        if ($version >= 4) return;
        if ($version === 3) {
            self::migrateInscriptionStatus($conexion);
            return;
        }
        $tablasExistentes = (int)$conexion->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchColumn();
        if ($version === 0 && $tablasExistentes === 0) {
            self::crearEsquemaCanonico($conexion);
            return;
        }
        // --- Esquema relacional para torneo de fútbol -------------------------------
        // Se ejecuta una sola vez para crear las 4 tablas principales.
        $crear_tablas = "
            CREATE TABLE IF NOT EXISTS equipos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL,
                liga TEXT NOT NULL,
                origen TEXT NOT NULL DEFAULT 'internacional',
                escudo TEXT DEFAULT 'default.png',
                anio TEXT DEFAULT '',
                creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS jugadores (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                equipo_id INTEGER NOT NULL,
                nombre TEXT NOT NULL,
                posicion TEXT NOT NULL,
                goles INTEGER DEFAULT 0,
                asistencias INTEGER DEFAULT 0,
                tarjetas_amarillas INTEGER DEFAULT 0,
                tarjetas_rojas INTEGER DEFAULT 0,
                mvp_count INTEGER DEFAULT 0,
                trofeos_ganados INTEGER NOT NULL DEFAULT 0,
                FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS partidos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                equipo_local_id INTEGER NOT NULL,
                equipo_visitante_id INTEGER NOT NULL,
                fecha_hora DATETIME NOT NULL,
                estadio TEXT NOT NULL,
                precio_entrada DECIMAL(10,2) DEFAULT 0.00,
                goles_local INTEGER NOT NULL DEFAULT 0 CHECK(goles_local >= 0),
                goles_visitante INTEGER NOT NULL DEFAULT 0 CHECK(goles_visitante >= 0),
                estado TEXT NOT NULL DEFAULT 'Programado',
                FOREIGN KEY (equipo_local_id) REFERENCES equipos(id) ON DELETE CASCADE,
                FOREIGN KEY (equipo_visitante_id) REFERENCES equipos(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS entradas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                usuario_id INTEGER,
                partido_id INTEGER NOT NULL,
                estado_pago TEXT NOT NULL DEFAULT 'Pendiente',
                codigo_qr TEXT DEFAULT '',
                nombre_comprador TEXT DEFAULT '',
                precio_pagado NUMERIC DEFAULT 0,
                fecha_compra DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (partido_id) REFERENCES partidos(id) ON DELETE CASCADE
            );
        ";

        $conexion->exec($crear_tablas);

        // Migraciones compatibles: se aplican a bases antiguas sin borrar registros.
        $colsEquipos = array_column($conexion->query('PRAGMA table_info(equipos)')->fetchAll(PDO::FETCH_ASSOC), 'name');
        if (!in_array('origen', $colsEquipos, true)) {
            $conexion->exec("ALTER TABLE equipos ADD COLUMN origen TEXT NOT NULL DEFAULT 'internacional'");
            $conexion->exec("UPDATE equipos SET origen = 'nacional' WHERE liga IN ('Liga 1 (Perú)', 'Liga 2 (Perú)', 'Liga 3 (Perú)', 'Copa Perú', 'Liga Femenina (Perú)')");
        }
        $colsJugadores = array_column($conexion->query('PRAGMA table_info(jugadores)')->fetchAll(PDO::FETCH_ASSOC), 'name');
        if (!in_array('trofeos_ganados', $colsJugadores, true)) {
            $conexion->exec('ALTER TABLE jugadores ADD COLUMN trofeos_ganados INTEGER NOT NULL DEFAULT 0');
        }
        $colsPartidos = array_column($conexion->query('PRAGMA table_info(partidos)')->fetchAll(PDO::FETCH_ASSOC), 'name');
        if (!in_array('goles_local', $colsPartidos, true)) {
            $conexion->exec('ALTER TABLE partidos ADD COLUMN goles_local INTEGER NOT NULL DEFAULT 0');
        }
        if (!in_array('goles_visitante', $colsPartidos, true)) {
            $conexion->exec('ALTER TABLE partidos ADD COLUMN goles_visitante INTEGER NOT NULL DEFAULT 0');
        }
        if (!in_array('estado', $colsPartidos, true)) {
            $conexion->exec("ALTER TABLE partidos ADD COLUMN estado TEXT NOT NULL DEFAULT 'Programado'");
        }

        // Un torneo vigente por origen. Se conserva en SQLite y puede verse por ambos roles.
        $conexion->exec("CREATE TABLE IF NOT EXISTS torneos_guardados (
            origen TEXT PRIMARY KEY CHECK (origen IN ('nacional', 'internacional')),
            estado_json TEXT NOT NULL,
            actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // --- Tabla de usuarios (login/registro con roles admin/usuario) ---
        $conexion->exec("
            CREATE TABLE IF NOT EXISTS usuarios (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // Mantener el historial de entradas sin eliminar datos existentes.
        // Estas columnas se agregan también a bases de datos creadas en versiones anteriores.
        $colsEntradas = array_column($conexion->query('PRAGMA table_info(entradas)')->fetchAll(PDO::FETCH_ASSOC), 'name');
        if (!in_array('nombre_comprador', $colsEntradas, true)) {
            $conexion->exec("ALTER TABLE entradas ADD COLUMN nombre_comprador TEXT DEFAULT ''");
        }
        if (!in_array('precio_pagado', $colsEntradas, true)) {
            $conexion->exec('ALTER TABLE entradas ADD COLUMN precio_pagado NUMERIC DEFAULT 0');
        }

        // Garantiza que cada código de entrada tenga un único propietario.
        // Si una base antigua contiene códigos repetidos, no interrumpe el sitio:
        // revisa esos duplicados antes de crear el índice de forma manual.
        try {
            $conexion->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_entradas_codigo_unico
                             ON entradas(codigo_qr) WHERE codigo_qr IS NOT NULL AND codigo_qr <> ''");
        } catch (PDOException $e) {
            error_log('GoolPass: existen códigos de entradas duplicados en una base antigua; revisar índice único.');
        }

        // Solo una entrada pagada por usuario y partido. Se aplican triggers en lugar
        // de un índice UNIQUE para conservar sin cambios compras duplicadas antiguas.
        // SQLite rechaza intentos posteriores incluso entre solicitudes concurrentes.
        $conexion->exec("CREATE INDEX IF NOT EXISTS idx_entradas_usuario_partido_estado
                         ON entradas(usuario_id, partido_id, estado_pago)");
        $conexion->exec("CREATE TRIGGER IF NOT EXISTS evitar_segunda_entrada_pagada
            BEFORE INSERT ON entradas
            WHEN NEW.estado_pago = 'Pagado' AND NEW.usuario_id IS NOT NULL
             AND EXISTS (
                SELECT 1 FROM entradas
                WHERE usuario_id = NEW.usuario_id AND partido_id = NEW.partido_id
                  AND estado_pago = 'Pagado'
             )
            BEGIN
                SELECT RAISE(ABORT, 'ENTRADA_YA_COMPRADA');
            END");
        $conexion->exec("CREATE TRIGGER IF NOT EXISTS evitar_duplicado_entrada_actualizada
            BEFORE UPDATE OF usuario_id, partido_id, estado_pago ON entradas
            WHEN NEW.estado_pago = 'Pagado' AND NEW.usuario_id IS NOT NULL
             AND EXISTS (
                SELECT 1 FROM entradas
                WHERE usuario_id = NEW.usuario_id AND partido_id = NEW.partido_id
                  AND estado_pago = 'Pagado' AND id <> OLD.id
             )
            BEGIN
                SELECT RAISE(ABORT, 'ENTRADA_YA_COMPRADA');
            END");

        // No se usa ni se crea ningún rol en el MVP.

        // Agregar las columnas que faltaron si las tablas existían antes
        $columnas = [
            'equipos' => ['escudo', 'anio'],
            'jugadores' => ['tarjetas_amarillas', 'tarjetas_rojas', 'mvp_count'],
            'partidos' => ['precio_entrada'],
            'entradas' => ['codigo_qr']
        ];

        foreach ($columnas as $tabla => $cols) {
            $sql_check = "PRAGMA table_info({$tabla})";
            $table_cols = $conexion->query($sql_check)->fetchAll(PDO::FETCH_ASSOC);
            $existing = array_column($table_cols, 'name');
            foreach ($cols as $col) {
                if (!in_array($col, $existing)) {
                    try {
                        $conexion->exec("ALTER TABLE {$tabla} ADD COLUMN {$col} TEXT DEFAULT '';");
                    } catch (PDOException $e) {
                        // Ignorar si ya existe
                    }
                }
            }
        }

        // Tablas nuevas del MVP. Las tablas antiguas y sus filas se conservan.
        $conexion->exec("CREATE TABLE IF NOT EXISTS torneos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL,
            categoria TEXT NOT NULL CHECK (categoria IN ('nacional', 'internacional')),
            formato TEXT NOT NULL DEFAULT 'Liga',
            estado TEXT NOT NULL DEFAULT 'Abierto',
            fecha_inicio DATE,
            fecha_fin DATE,
            creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        $conexion->exec("CREATE TABLE IF NOT EXISTS inscripciones (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            torneo_id INTEGER NOT NULL,
            equipo_id INTEGER NOT NULL UNIQUE,
            fecha_inscripcion DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (torneo_id, equipo_id),
            FOREIGN KEY (torneo_id) REFERENCES torneos(id) ON DELETE CASCADE,
            FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE
        )");
        $conexion->exec("CREATE TABLE IF NOT EXISTS estadisticas_partido (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            partido_id INTEGER NOT NULL,
            jugador_id INTEGER NOT NULL,
            goles INTEGER NOT NULL DEFAULT 0 CHECK (goles >= 0),
            asistencias INTEGER NOT NULL DEFAULT 0 CHECK (asistencias >= 0),
            tarjetas_amarillas INTEGER NOT NULL DEFAULT 0 CHECK (tarjetas_amarillas >= 0),
            tarjetas_rojas INTEGER NOT NULL DEFAULT 0 CHECK (tarjetas_rojas >= 0),
            fue_mvp INTEGER NOT NULL DEFAULT 0 CHECK (fue_mvp IN (0, 1)),
            UNIQUE (partido_id, jugador_id),
            FOREIGN KEY (partido_id) REFERENCES partidos(id) ON DELETE CASCADE,
            FOREIGN KEY (jugador_id) REFERENCES jugadores(id) ON DELETE CASCADE
        )");

        $asegurarColumna = static function (string $tabla, string $columna, string $definicion) use ($conexion): void {
            $nombres = array_column($conexion->query("PRAGMA table_info({$tabla})")->fetchAll(PDO::FETCH_ASSOC), 'name');
            if (!in_array($columna, $nombres, true)) {
                $conexion->exec("ALTER TABLE {$tabla} ADD COLUMN {$columna} {$definicion}");
            }
        };
        $asegurarColumna('partidos', 'torneo_id', 'INTEGER');
        $asegurarColumna('partidos', 'aforo', 'INTEGER NOT NULL DEFAULT 1000 CHECK (aforo > 0)');
        $asegurarColumna('entradas', 'estado_entrada', "TEXT NOT NULL DEFAULT 'Activa'");
        $asegurarColumna('entradas', 'email_comprador', "TEXT DEFAULT ''");
        $conexion->exec("CREATE INDEX IF NOT EXISTS idx_inscripciones_torneo ON inscripciones(torneo_id)");
        $conexion->exec("CREATE INDEX IF NOT EXISTS idx_estadisticas_jugador ON estadisticas_partido(jugador_id)");
        $conexion->exec("CREATE TRIGGER IF NOT EXISTS controlar_aforo_entrada
            BEFORE INSERT ON entradas
            WHEN COALESCE(NEW.estado_entrada, 'Activa') = 'Activa'
             AND (SELECT COUNT(*) FROM entradas
                  WHERE partido_id = NEW.partido_id AND estado_entrada = 'Activa') >=
                 (SELECT aforo FROM partidos WHERE id = NEW.partido_id)
            BEGIN
                SELECT RAISE(ABORT, 'AFORO_COMPLETO');
            END");

        // Versión 3: validar las relaciones de negocio sin reasignar partidos
        // antiguos ni reconstruir tablas que ya contienen datos.
        $conexion->exec("DROP TRIGGER IF EXISTS validar_inscripcion_equipo_torneo");
        $conexion->exec("CREATE TRIGGER validar_inscripcion_equipo_torneo
            BEFORE INSERT ON inscripciones
            BEGIN
                SELECT CASE
                  WHEN (SELECT categoria FROM torneos WHERE id = NEW.torneo_id) IS NULL
                    THEN RAISE(ABORT, 'El torneo indicado no existe')
                  WHEN (SELECT origen FROM equipos WHERE id = NEW.equipo_id) IS NULL
                    THEN RAISE(ABORT, 'El equipo indicado no existe')
                  WHEN LOWER((SELECT origen FROM equipos WHERE id = NEW.equipo_id)) = 'internacional'
                    AND LOWER((SELECT categoria FROM torneos WHERE id = NEW.torneo_id)) <> 'internacional'
                    THEN RAISE(ABORT, 'Un equipo internacional solo puede inscribirse en torneo internacional')
                END;
            END");
        $conexion->exec("CREATE TRIGGER IF NOT EXISTS validar_inscripcion_equipo_torneo_update
            BEFORE UPDATE OF torneo_id, equipo_id ON inscripciones
            BEGIN
                SELECT CASE
                  WHEN (SELECT categoria FROM torneos WHERE id = NEW.torneo_id) IS NULL
                    THEN RAISE(ABORT, 'El torneo indicado no existe')
                  WHEN (SELECT origen FROM equipos WHERE id = NEW.equipo_id) IS NULL
                    THEN RAISE(ABORT, 'El equipo indicado no existe')
                  WHEN LOWER((SELECT origen FROM equipos WHERE id = NEW.equipo_id)) = 'internacional'
                    AND LOWER((SELECT categoria FROM torneos WHERE id = NEW.torneo_id)) <> 'internacional'
                    THEN RAISE(ABORT, 'Un equipo internacional solo puede inscribirse en torneo internacional')
                END;
            END");
        $conexion->exec("CREATE TRIGGER IF NOT EXISTS validar_participantes_partido
            BEFORE INSERT ON partidos
            WHEN NEW.torneo_id IS NOT NULL
            BEGIN
                SELECT CASE
                  WHEN NOT EXISTS (SELECT 1 FROM inscripciones WHERE torneo_id=NEW.torneo_id AND equipo_id=NEW.equipo_local_id)
                    THEN RAISE(ABORT, 'El equipo local no está inscrito en el torneo')
                  WHEN NOT EXISTS (SELECT 1 FROM inscripciones WHERE torneo_id=NEW.torneo_id AND equipo_id=NEW.equipo_visitante_id)
                    THEN RAISE(ABORT, 'El equipo visitante no está inscrito en el torneo')
                END;
            END");
        $conexion->exec("CREATE TRIGGER IF NOT EXISTS validar_participantes_partido_update
            BEFORE UPDATE OF torneo_id, equipo_local_id, equipo_visitante_id ON partidos
            WHEN NEW.torneo_id IS NOT NULL
            BEGIN
                SELECT CASE
                  WHEN NOT EXISTS (SELECT 1 FROM inscripciones WHERE torneo_id=NEW.torneo_id AND equipo_id=NEW.equipo_local_id)
                    THEN RAISE(ABORT, 'El equipo local no está inscrito en el torneo')
                  WHEN NOT EXISTS (SELECT 1 FROM inscripciones WHERE torneo_id=NEW.torneo_id AND equipo_id=NEW.equipo_visitante_id)
                    THEN RAISE(ABORT, 'El equipo visitante no está inscrito en el torneo')
                END;
            END");
        $conexion->exec("CREATE TRIGGER IF NOT EXISTS validar_jugador_estadistica
            BEFORE INSERT ON estadisticas_partido
            BEGIN
                SELECT CASE WHEN NOT EXISTS (
                    SELECT 1 FROM partidos p JOIN jugadores j ON j.id=NEW.jugador_id
                    WHERE p.id=NEW.partido_id AND j.equipo_id IN (p.equipo_local_id,p.equipo_visitante_id)
                ) THEN RAISE(ABORT, 'El jugador no pertenece a un equipo del partido') END;
            END");
        $conexion->exec("CREATE TRIGGER IF NOT EXISTS validar_jugador_estadistica_update
            BEFORE UPDATE OF partido_id, jugador_id ON estadisticas_partido
            BEGIN
                SELECT CASE WHEN NOT EXISTS (
                    SELECT 1 FROM partidos p JOIN jugadores j ON j.id=NEW.jugador_id
                    WHERE p.id=NEW.partido_id AND j.equipo_id IN (p.equipo_local_id,p.equipo_visitante_id)
                ) THEN RAISE(ABORT, 'El jugador no pertenece a un equipo del partido') END;
            END");
        $asegurarColumna('jugadores', 'numero_camiseta', 'INTEGER');
        $asegurarColumna('jugadores', 'fecha_nacimiento', 'TEXT');
        $conexion->exec("CREATE TRIGGER IF NOT EXISTS validar_origen_equipo_insert
            BEFORE INSERT ON equipos
            WHEN LOWER(NEW.origen) NOT IN ('nacional','internacional')
            BEGIN SELECT RAISE(ABORT, 'Origen de equipo inválido'); END");
        $conexion->exec("CREATE TRIGGER IF NOT EXISTS validar_origen_equipo_update
            BEFORE UPDATE OF origen ON equipos
            WHEN LOWER(NEW.origen) NOT IN ('nacional','internacional')
            BEGIN SELECT RAISE(ABORT, 'Origen de equipo inválido'); END");
        $conexion->exec("CREATE TRIGGER IF NOT EXISTS validar_campos_partido_insert
            BEFORE INSERT ON partidos
            WHEN NEW.precio_entrada < 0 OR NEW.aforo <= 0 OR NEW.goles_local < 0 OR NEW.goles_visitante < 0
            BEGIN SELECT RAISE(ABORT, 'Precio, aforo o marcador inválido'); END");
        $conexion->exec("CREATE TRIGGER IF NOT EXISTS validar_campos_partido_update
            BEFORE UPDATE OF precio_entrada, aforo, goles_local, goles_visitante ON partidos
            WHEN NEW.precio_entrada < 0 OR NEW.aforo <= 0 OR NEW.goles_local < 0 OR NEW.goles_visitante < 0
            BEGIN SELECT RAISE(ABORT, 'Precio, aforo o marcador inválido'); END");
        $conexion->exec("CREATE TRIGGER IF NOT EXISTS validar_datos_entrada_insert
            BEFORE INSERT ON entradas
            WHEN NEW.precio_pagado < 0 OR LOWER(NEW.estado_pago) NOT IN ('pendiente','pagado','fallido','reembolsado')
              OR LOWER(NEW.estado_entrada) NOT IN ('activa','cancelada','usada')
            BEGIN SELECT RAISE(ABORT, 'Estado o precio de entrada inválido'); END");
        $conexion->exec("CREATE TRIGGER IF NOT EXISTS validar_datos_entrada_update
            BEFORE UPDATE OF estado_pago, estado_entrada, precio_pagado ON entradas
            WHEN NEW.precio_pagado < 0 OR LOWER(NEW.estado_pago) NOT IN ('pendiente','pagado','fallido','reembolsado')
              OR LOWER(NEW.estado_entrada) NOT IN ('activa','cancelada','usada')
            BEGIN SELECT RAISE(ABORT, 'Estado o precio de entrada inválido'); END");
        $conexion->exec("CREATE TRIGGER IF NOT EXISTS validar_usuario_entrada
            BEFORE INSERT ON entradas
            WHEN NEW.usuario_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM usuarios WHERE id=NEW.usuario_id)
            BEGIN SELECT RAISE(ABORT, 'El usuario de la entrada no existe'); END");
        $conexion->exec("CREATE TRIGGER IF NOT EXISTS validar_usuario_entrada_update
            BEFORE UPDATE OF usuario_id ON entradas
            WHEN NEW.usuario_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM usuarios WHERE id=NEW.usuario_id)
            BEGIN SELECT RAISE(ABORT, 'El usuario de la entrada no existe'); END");
        $conexion->exec("CREATE TRIGGER IF NOT EXISTS desvincular_usuario_entradas
            AFTER DELETE ON usuarios
            BEGIN UPDATE entradas SET usuario_id=NULL WHERE usuario_id=OLD.id; END");
        $conexion->exec("CREATE TRIGGER IF NOT EXISTS proteger_historial_partido
            BEFORE DELETE ON partidos
            WHEN EXISTS (SELECT 1 FROM entradas WHERE partido_id=OLD.id)
              OR EXISTS (SELECT 1 FROM estadisticas_partido WHERE partido_id=OLD.id)
            BEGIN SELECT RAISE(ABORT, 'No se puede borrar un partido con historial'); END");
        $conexion->exec("CREATE TRIGGER IF NOT EXISTS proteger_historial_jugador
            BEFORE DELETE ON jugadores
            WHEN EXISTS (SELECT 1 FROM estadisticas_partido WHERE jugador_id=OLD.id)
            BEGIN SELECT RAISE(ABORT, 'No se puede borrar un jugador con estadísticas históricas'); END");
        $conexion->exec("CREATE TRIGGER IF NOT EXISTS proteger_historial_equipo
            BEFORE DELETE ON equipos
            WHEN EXISTS (SELECT 1 FROM jugadores WHERE equipo_id=OLD.id)
              OR EXISTS (SELECT 1 FROM inscripciones WHERE equipo_id=OLD.id)
              OR EXISTS (SELECT 1 FROM partidos WHERE equipo_local_id=OLD.id OR equipo_visitante_id=OLD.id)
            BEGIN SELECT RAISE(ABORT, 'No se puede borrar un equipo con historial'); END");
        $conexion->exec("CREATE TRIGGER IF NOT EXISTS proteger_historial_torneo
            BEFORE DELETE ON torneos
            WHEN EXISTS (SELECT 1 FROM inscripciones WHERE torneo_id=OLD.id)
              OR EXISTS (SELECT 1 FROM partidos WHERE torneo_id=OLD.id)
            BEGIN SELECT RAISE(ABORT, 'No se puede borrar un torneo con historial'); END");
        $conexion->exec("CREATE TRIGGER IF NOT EXISTS proteger_cambio_equipo_jugador
            BEFORE UPDATE OF equipo_id ON jugadores
            WHEN EXISTS (
                SELECT 1 FROM estadisticas_partido ep JOIN partidos p ON p.id=ep.partido_id
                WHERE ep.jugador_id=OLD.id AND NEW.equipo_id NOT IN (p.equipo_local_id,p.equipo_visitante_id)
            )
            BEGIN SELECT RAISE(ABORT, 'El cambio de equipo invalida estadísticas históricas'); END");

        // La compra por operación admite una entrada por persona/solicitud; no
        // se limita el total histórico por usuario y partido.
        $conexion->exec('DROP TRIGGER IF EXISTS evitar_segunda_entrada_pagada');
        $conexion->exec('DROP TRIGGER IF EXISTS evitar_duplicado_entrada_actualizada');
        $conexion->exec('DROP TRIGGER IF EXISTS controlar_aforo_entrada');
        $conexion->exec("CREATE TRIGGER controlar_aforo_entrada
            BEFORE INSERT ON entradas
            WHEN LOWER(COALESCE(NEW.estado_entrada, 'activa')) <> 'cancelada'
             AND LOWER(COALESCE(NEW.estado_pago, 'pendiente')) IN ('pendiente','pagado')
             AND (SELECT COUNT(*) FROM entradas e
                  WHERE e.partido_id=NEW.partido_id
                    AND LOWER(COALESCE(e.estado_entrada,'activa')) <> 'cancelada'
                    AND LOWER(COALESCE(e.estado_pago,'pendiente')) IN ('pendiente','pagado')) >=
                 (SELECT aforo FROM partidos WHERE id=NEW.partido_id)
            BEGIN SELECT RAISE(ABORT, 'AFORO_COMPLETO'); END");
        $conexion->exec("CREATE TRIGGER IF NOT EXISTS controlar_aforo_entrada_update
            BEFORE UPDATE OF partido_id, estado_entrada, estado_pago ON entradas
            WHEN LOWER(COALESCE(NEW.estado_entrada, 'activa')) <> 'cancelada'
             AND LOWER(COALESCE(NEW.estado_pago, 'pendiente')) IN ('pendiente','pagado')
             AND (SELECT COUNT(*) FROM entradas e
                  WHERE e.partido_id=NEW.partido_id AND e.id<>OLD.id
                    AND LOWER(COALESCE(e.estado_entrada,'activa')) <> 'cancelada'
                    AND LOWER(COALESCE(e.estado_pago,'pendiente')) IN ('pendiente','pagado')) >=
                 (SELECT aforo FROM partidos WHERE id=NEW.partido_id)
            BEGIN SELECT RAISE(ABORT, 'AFORO_COMPLETO'); END");

        $conexion->exec('PRAGMA user_version = 3');
        self::migrateInscriptionStatus($conexion);
    }

    private static function migrateInscriptionStatus(PDO $conexion): void
    {
        $columns = array_column($conexion->query('PRAGMA table_info(torneos)')->fetchAll(PDO::FETCH_ASSOC), 'name');
        if (!in_array('inscripciones_abiertas', $columns, true)) {
            $conexion->exec('ALTER TABLE torneos ADD COLUMN inscripciones_abiertas INTEGER NOT NULL DEFAULT 1 CHECK (inscripciones_abiertas IN (0,1))');
        }
        $conexion->exec('PRAGMA user_version = 4');
    }

    /** Crea desde cero la estructura limpia usada por instalaciones nuevas. */
    private static function crearEsquemaCanonico(PDO $conexion): void
    {
        $conexion->exec('PRAGMA foreign_keys = ON');
        // BEGIN IMMEDIATE serializa la primera instalación concurrente. Se
        // revalida el estado tras obtener el bloqueo para que el segundo
        // proceso no intente ejecutar el esquema a medio crear.
        $conexion->exec('BEGIN IMMEDIATE');
        try {
            $versionActual = (int)$conexion->query('PRAGMA user_version')->fetchColumn();
            $tablasActuales = (int)$conexion->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchColumn();
            if ($versionActual >= 4) {
                $conexion->exec('COMMIT');
                return;
            }
            if ($tablasActuales > 0) {
                $conexion->exec('COMMIT');
                self::migrate($conexion);
                return;
            }
        $conexion->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL CHECK (length(trim(nombre)) > 0),
    email TEXT NOT NULL COLLATE NOCASE UNIQUE,
    password TEXT NOT NULL,
    creado_en TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS equipos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL CHECK (length(trim(nombre)) > 0),
    liga TEXT NOT NULL DEFAULT '',
    origen TEXT NOT NULL CHECK (origen IN ('nacional','internacional')),
    escudo TEXT,
    anio TEXT,
    creado_en TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS jugadores (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    equipo_id INTEGER NOT NULL REFERENCES equipos(id) ON DELETE RESTRICT,
    nombre TEXT NOT NULL CHECK (length(trim(nombre)) > 0),
    posicion TEXT NOT NULL DEFAULT '',
    numero_camiseta INTEGER CHECK (numero_camiseta IS NULL OR numero_camiseta BETWEEN 1 AND 99),
    fecha_nacimiento TEXT,
    creado_en TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS torneos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL CHECK (length(trim(nombre)) > 0),
    categoria TEXT NOT NULL CHECK (categoria IN ('nacional','internacional')),
    formato TEXT NOT NULL DEFAULT '',
    estado TEXT NOT NULL DEFAULT 'abierto' CHECK (estado IN ('borrador','abierto','en_curso','finalizado','cancelado')),
    inscripciones_abiertas INTEGER NOT NULL DEFAULT 1 CHECK (inscripciones_abiertas IN (0,1)),
    fecha_inicio TEXT,
    fecha_fin TEXT,
    creado_en TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (fecha_fin IS NULL OR fecha_inicio IS NULL OR fecha_fin >= fecha_inicio)
);
CREATE TABLE IF NOT EXISTS inscripciones (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    torneo_id INTEGER NOT NULL REFERENCES torneos(id) ON DELETE RESTRICT,
    equipo_id INTEGER NOT NULL UNIQUE REFERENCES equipos(id) ON DELETE RESTRICT,
    fecha_inscripcion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (torneo_id,equipo_id)
);
CREATE TABLE IF NOT EXISTS partidos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    torneo_id INTEGER NOT NULL REFERENCES torneos(id) ON DELETE RESTRICT,
    equipo_local_id INTEGER NOT NULL REFERENCES equipos(id) ON DELETE RESTRICT,
    equipo_visitante_id INTEGER NOT NULL REFERENCES equipos(id) ON DELETE RESTRICT,
    fecha_hora TEXT NOT NULL,
    estadio TEXT NOT NULL DEFAULT '',
    precio_entrada NUMERIC NOT NULL DEFAULT 0 CHECK (precio_entrada >= 0),
    aforo INTEGER NOT NULL CHECK (aforo > 0),
    goles_local INTEGER NOT NULL DEFAULT 0 CHECK (goles_local >= 0),
    goles_visitante INTEGER NOT NULL DEFAULT 0 CHECK (goles_visitante >= 0),
    estado TEXT NOT NULL DEFAULT 'programado' CHECK (estado IN ('programado','en_curso','finalizado','cancelado')),
    creado_en TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (equipo_local_id <> equipo_visitante_id)
);
CREATE TABLE IF NOT EXISTS estadisticas_partido (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    partido_id INTEGER NOT NULL REFERENCES partidos(id) ON DELETE RESTRICT,
    jugador_id INTEGER NOT NULL REFERENCES jugadores(id) ON DELETE RESTRICT,
    goles INTEGER NOT NULL DEFAULT 0 CHECK (goles >= 0),
    asistencias INTEGER NOT NULL DEFAULT 0 CHECK (asistencias >= 0),
    tarjetas_amarillas INTEGER NOT NULL DEFAULT 0 CHECK (tarjetas_amarillas >= 0),
    tarjetas_rojas INTEGER NOT NULL DEFAULT 0 CHECK (tarjetas_rojas >= 0),
    fue_mvp INTEGER NOT NULL DEFAULT 0 CHECK (fue_mvp IN (0,1)),
    UNIQUE (partido_id,jugador_id)
);
CREATE TABLE IF NOT EXISTS entradas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER NULL REFERENCES usuarios(id) ON DELETE SET NULL,
    partido_id INTEGER NOT NULL REFERENCES partidos(id) ON DELETE RESTRICT,
    estado_pago TEXT NOT NULL DEFAULT 'pendiente' CHECK (estado_pago IN ('pendiente','pagado','fallido','reembolsado')),
    estado_entrada TEXT NOT NULL DEFAULT 'activa' CHECK (estado_entrada IN ('activa','cancelada','usada')),
    codigo_qr TEXT NOT NULL UNIQUE,
    nombre_comprador TEXT,
    email_comprador TEXT,
    precio_pagado NUMERIC NOT NULL CHECK (precio_pagado >= 0),
    fecha_compra TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_jugadores_equipo ON jugadores(equipo_id);
CREATE INDEX IF NOT EXISTS idx_partidos_torneo_fecha ON partidos(torneo_id,fecha_hora);
CREATE INDEX IF NOT EXISTS idx_entradas_partido_estado ON entradas(partido_id,estado_entrada,estado_pago);
CREATE INDEX IF NOT EXISTS idx_estadisticas_jugador ON estadisticas_partido(jugador_id);
SQL);
        $conexion->exec(<<<'SQL'
CREATE TRIGGER IF NOT EXISTS validar_inscripcion_elegible
BEFORE INSERT ON inscripciones
BEGIN
    SELECT CASE WHEN (SELECT origen FROM equipos WHERE id=NEW.equipo_id)='internacional'
      AND (SELECT categoria FROM torneos WHERE id=NEW.torneo_id)<>'internacional'
      THEN RAISE(ABORT,'Un equipo internacional solo puede inscribirse a un torneo internacional') END;
END;
CREATE TRIGGER IF NOT EXISTS validar_inscripcion_elegible_update
BEFORE UPDATE OF equipo_id,torneo_id ON inscripciones
BEGIN
    SELECT CASE WHEN (SELECT origen FROM equipos WHERE id=NEW.equipo_id)='internacional'
      AND (SELECT categoria FROM torneos WHERE id=NEW.torneo_id)<>'internacional'
      THEN RAISE(ABORT,'Un equipo internacional solo puede inscribirse a un torneo internacional') END;
END;
CREATE TRIGGER IF NOT EXISTS validar_partido_inscripciones
BEFORE INSERT ON partidos
BEGIN
    SELECT CASE WHEN NOT EXISTS (SELECT 1 FROM inscripciones WHERE torneo_id=NEW.torneo_id AND equipo_id=NEW.equipo_local_id)
      THEN RAISE(ABORT,'El equipo local no está inscrito en el torneo') END;
    SELECT CASE WHEN NOT EXISTS (SELECT 1 FROM inscripciones WHERE torneo_id=NEW.torneo_id AND equipo_id=NEW.equipo_visitante_id)
      THEN RAISE(ABORT,'El equipo visitante no está inscrito en el torneo') END;
END;
CREATE TRIGGER IF NOT EXISTS validar_partido_inscripciones_update
BEFORE UPDATE OF torneo_id,equipo_local_id,equipo_visitante_id ON partidos
BEGIN
    SELECT CASE WHEN NOT EXISTS (SELECT 1 FROM inscripciones WHERE torneo_id=NEW.torneo_id AND equipo_id=NEW.equipo_local_id)
      THEN RAISE(ABORT,'El equipo local no está inscrito en el torneo') END;
    SELECT CASE WHEN NOT EXISTS (SELECT 1 FROM inscripciones WHERE torneo_id=NEW.torneo_id AND equipo_id=NEW.equipo_visitante_id)
      THEN RAISE(ABORT,'El equipo visitante no está inscrito en el torneo') END;
END;
CREATE TRIGGER IF NOT EXISTS validar_jugador_estadistica_nueva
BEFORE INSERT ON estadisticas_partido
BEGIN
    SELECT CASE WHEN NOT EXISTS (SELECT 1 FROM partidos p JOIN jugadores j ON j.id=NEW.jugador_id
      WHERE p.id=NEW.partido_id AND j.equipo_id IN (p.equipo_local_id,p.equipo_visitante_id))
      THEN RAISE(ABORT,'El jugador no pertenece a un equipo del partido') END;
END;
CREATE TRIGGER IF NOT EXISTS validar_jugador_estadistica_nueva_update
BEFORE UPDATE OF partido_id,jugador_id ON estadisticas_partido
BEGIN
    SELECT CASE WHEN NOT EXISTS (SELECT 1 FROM partidos p JOIN jugadores j ON j.id=NEW.jugador_id
      WHERE p.id=NEW.partido_id AND j.equipo_id IN (p.equipo_local_id,p.equipo_visitante_id))
      THEN RAISE(ABORT,'El jugador no pertenece a un equipo del partido') END;
END;
CREATE TRIGGER IF NOT EXISTS controlar_aforo_nuevo
BEFORE INSERT ON entradas
WHEN NEW.estado_entrada<>'cancelada' AND NEW.estado_pago IN ('pendiente','pagado')
 AND (SELECT COUNT(*) FROM entradas e WHERE e.partido_id=NEW.partido_id
      AND e.estado_entrada<>'cancelada' AND e.estado_pago IN ('pendiente','pagado')) >=
     (SELECT aforo FROM partidos WHERE id=NEW.partido_id)
BEGIN SELECT RAISE(ABORT,'AFORO_COMPLETO'); END;
CREATE TRIGGER IF NOT EXISTS controlar_aforo_nuevo_update
BEFORE UPDATE OF partido_id,estado_entrada,estado_pago ON entradas
WHEN NEW.estado_entrada<>'cancelada' AND NEW.estado_pago IN ('pendiente','pagado')
 AND (SELECT COUNT(*) FROM entradas e WHERE e.partido_id=NEW.partido_id AND e.id<>OLD.id
      AND e.estado_entrada<>'cancelada' AND e.estado_pago IN ('pendiente','pagado')) >=
     (SELECT aforo FROM partidos WHERE id=NEW.partido_id)
BEGIN SELECT RAISE(ABORT,'AFORO_COMPLETO'); END;
SQL);
        $conexion->exec('PRAGMA user_version = 4');
            $conexion->exec('COMMIT');
        } catch (Throwable $error) {
            try { $conexion->exec('ROLLBACK'); } catch (Throwable) {}
            throw $error;
        }
    }
}
