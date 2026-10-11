<?php
try {
    // Archivo único de la base de datos SQLite
    $db_file = __DIR__ . '/torneo.db';

    $conexion = new PDO("sqlite:" . $db_file);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- Esquema relacional para torneo de fútbol -------------------------------
    // Se ejecuta una sola vez para crear las 4 tablas principales.
    $crear_tablas = "
        CREATE TABLE IF NOT EXISTS equipos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL,
            liga TEXT NOT NULL,
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
            FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS partidos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            equipo_local_id INTEGER NOT NULL,
            equipo_visitante_id INTEGER NOT NULL,
            fecha_hora DATETIME NOT NULL,
            estadio TEXT NOT NULL,
            precio_entrada DECIMAL(10,2) DEFAULT 0.00,
            FOREIGN KEY (equipo_local_id) REFERENCES equipos(id) ON DELETE CASCADE,
            FOREIGN KEY (equipo_visitante_id) REFERENCES equipos(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS entradas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER,
            partido_id INTEGER NOT NULL,
            estado_pago TEXT NOT NULL DEFAULT 'Pendiente',
            codigo_qr TEXT DEFAULT '',
            fecha_compra DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (partido_id) REFERENCES partidos(id) ON DELETE CASCADE
        );
    ";

    $conexion->exec($crear_tablas);

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

    // --- Cuenta de administrador por defecto (credenciales básicas) ---
    // Se crea una sola vez si todavía no existe ningún administrador.
    $adminEmail = 'admin@goolpass.com';
    $adminPass  = 'admin123';
    $hayAdmin = $conexion->query(
        "SELECT COUNT(*) FROM usuarios WHERE rol = 'admin'"
    )->fetchColumn();

    if ((int) $hayAdmin === 0) {
        $existe = $conexion->prepare("SELECT id FROM usuarios WHERE email = :e LIMIT 1");
        $existe->execute([':e' => $adminEmail]);
        if (!$existe->fetch()) {
            $seed = $conexion->prepare(
                "INSERT INTO usuarios (nombre, email, password, rol) VALUES (:n, :e, :p, 'admin')"
            );
            $seed->execute([
                ':n' => 'Administrador',
                ':e' => $adminEmail,
                ':p' => password_hash($adminPass, PASSWORD_DEFAULT),
            ]);
        }
    }

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

} catch (PDOException $e) {
    die("Error de conexión con SQLite: " . $e->getMessage());
}
?>
