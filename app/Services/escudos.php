<?php
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
