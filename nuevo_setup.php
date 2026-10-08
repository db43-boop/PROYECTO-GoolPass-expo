<?php
/**
 * Script de configuración y reinicio de la base de datos
 * para el nuevo sistema de torneos de fútbol
 * 
 * Elimina todas las tablas existentes y crea las nuevas:
 * - equipos
 * - jugadores
 * - partidos
 * - entradas
 */
require_once 'conexion.php';

echo "=== Reiniciando base de datos para torneo de fútbol ===\n";

try {
    // 1. Eliminar tablas existentes en orden correcto (por dependencias)
    echo "--- Eliminando tablas existentes ---\n";
    $tablas_existentes = [
        'entradas',
        'partidos',
        'jugadores',
        'equipos',
        'participantes'
    ];

    foreach ($tablas_existentes as $tabla) {
        $conexion->exec("DROP TABLE IF EXISTS {$tabla};");
        echo "- Eliminada tabla: {$tabla}\n";
    }

    // 2. Crear tabla 'equipos'
    echo "\n--- Creando tabla 'equipos' ---\n";
    $conexion->exec("CREATE TABLE equipos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre TEXT NOT NULL,
        liga TEXT NOT NULL,
        escudo TEXT DEFAULT 'default.png',
        anio TEXT DEFAULT '',
        creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
    );
");
    echo "- Tabla 'equipos' creada\n";

    // 3. Crear tabla 'jugadores'
    echo "\n--- Creando tabla 'jugadores' ---\n";
    $conexion->exec("CREATE TABLE jugadores (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        equipo_id INTEGER NOT NULL,
        nombre TEXT NOT NULL,
        posicion TEXT NOT NULL,
        goles INTEGER DEFAULT 0,
        asistencias INTEGER DEFAULT 0,
        tarjetas_amarillas INTEGER DEFAULT 0,
        tarjetas_rojas INTEGER DEFAULT 0,
        mvp_count INTEGER DEFAULT 0,
        FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE
    );
");
    echo "- Tabla 'jugadores' creada\n";

    // 4. Crear tabla 'partidos'
    echo "\n--- Creando tabla 'partidos' ---\n";
    $conexion->exec("CREATE TABLE partidos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        equipo_local_id INTEGER NOT NULL,
        equipo_visitante_id INTEGER NOT NULL,
        fecha_hora DATETIME NOT NULL,
        estadio TEXT NOT NULL,
        precio_entrada DECIMAL(10,2) DEFAULT 0.00,
        FOREIGN KEY (equipo_local_id) REFERENCES equipos(id) ON DELETE CASCADE,
        FOREIGN KEY (equipo_visitante_id) REFERENCES equipos(id) ON DELETE CASCADE
    );
");
    echo "- Tabla 'partidos' creada\n";

    // 5. Crear tabla 'entradas'
    echo "\n--- Creando tabla 'entradas' ---\n";
    $conexion->exec("CREATE TABLE entradas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario_id INTEGER,
        partido_id INTEGER NOT NULL,
        estado_pago TEXT NOT NULL DEFAULT 'Pendiente',
        codigo_qr TEXT DEFAULT '',
        fecha_compra DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (partido_id) REFERENCES partidos(id) ON DELETE CASCADE
    );
");
    echo "- Tabla 'entradas' creada\n";

    echo "\n=== Base de datos reiniciada correctamente ===\n";
    echo "Tablas creadas: equipos, jugadores, partidos, entradas\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
