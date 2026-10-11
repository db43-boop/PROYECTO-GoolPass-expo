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
        if ((int)$conexion->query('PRAGMA user_version')->fetchColumn() >= 1) return;
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
                rol TEXT NOT NULL DEFAULT 'usuario',
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

        // No crear administradores con credenciales conocidas. El primer admin
        // se configura desde la terminal utilizando configurar_admin.php.

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

        $conexion->exec('PRAGMA user_version = 1');
    }
}
