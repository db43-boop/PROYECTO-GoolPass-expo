<?php
/** Herramienta local para conservar la base de datos y escudos de una versión anterior. */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Solo terminal.');
}
$raiz = dirname(__DIR__);
define('GOOLPASS_ROOT', $raiz);
define('GOOLPASS_STORAGE', $raiz . '/storage');
define('GOOLPASS_UPLOADS_DIR', $raiz . '/public/uploads');

$antigua = GOOLPASS_ROOT . '/torneo.db';
$nueva = GOOLPASS_STORAGE . '/torneo.db';
if (!is_file($antigua)) {
    fwrite(STDERR, "No se encontró la base antigua en la raíz del proyecto.\n");
    exit(1);
}
if (!in_array('--confirmar', $argv, true)) {
    echo "Esta operación reemplazará storage/torneo.db por la base antigua torneo.db.\n";
    echo "Se creará un respaldo previo. Para confirmar añade: --confirmar\n";
    exit(0);
}
if (!is_dir(GOOLPASS_STORAGE) && !mkdir(GOOLPASS_STORAGE, 0755, true)) {
    fwrite(STDERR, "No se pudo crear storage.\n");
    exit(1);
}
$marca = date('Ymd_His') . '_' . bin2hex(random_bytes(3));
if (is_file($nueva)) {
    $backup = GOOLPASS_STORAGE . '/backup_pre_migracion_' . $marca . '.db';
    if (!copy($nueva, $backup)) {
        fwrite(STDERR, "No se pudo hacer una copia de seguridad de la base actual.\n");
        exit(1);
    }
    echo "Respaldo creado: storage/" . basename($backup) . "\n";
}
if (!copy($antigua, $nueva)) {
    fwrite(STDERR, "No se pudo copiar la base antigua a storage.\n");
    exit(1);
}
$copiados = 0;
$carpetaVieja = GOOLPASS_ROOT . '/uploads';
if (is_dir($carpetaVieja)) {
    if (!is_dir(GOOLPASS_UPLOADS_DIR) && !mkdir(GOOLPASS_UPLOADS_DIR, 0755, true)) {
        fwrite(STDERR, "No se pudo crear public/uploads.\n");
        exit(1);
    }
    foreach (new DirectoryIterator($carpetaVieja) as $item) {
        if (!$item->isFile() || $item->isLink()) continue;
        $nombre = $item->getFilename();
        if (!preg_match('/^[a-zA-Z0-9_.-]+\.(?:png|jpg|jpeg|webp)$/i', $nombre)) continue;
        $destino = GOOLPASS_UPLOADS_DIR . '/' . $nombre;
        if (!is_file($destino) && copy($item->getPathname(), $destino)) $copiados++;
    }
}
echo "Migración terminada. Se copió la base original a storage/torneo.db y $copiados escudos nuevos.\n";
echo "No se borró la base anterior ni la carpeta uploads antigua.\n";
