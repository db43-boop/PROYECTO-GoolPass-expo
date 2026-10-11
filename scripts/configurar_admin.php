<?php
/** Configura o actualiza una cuenta administradora desde la terminal local. */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Acceso denegado. Ejecuta este archivo desde Git Bash o la terminal.');
}
require_once GOOLPASS_ROOT . '/app/bootstrap.php';

echo "\nConfiguración del administrador de GoolPass\n";
echo "Correo electrónico del administrador: ";
$email = strtolower(trim((string)fgets(STDIN)));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Correo inválido.\n");
    exit(1);
}
echo "Nombre completo: ";
$nombre = trim((string)fgets(STDIN));
if ($nombre === '' || mb_strlen($nombre) > 100) {
    fwrite(STDERR, "El nombre no es válido.\n");
    exit(1);
}
echo "Contraseña para el administrador (mínimo 8 caracteres; se muestra al escribir): ";
$pass = rtrim((string)fgets(STDIN), "\r\n");
if (strlen($pass) < 8) {
    fwrite(STDERR, "La contraseña debe tener al menos 8 caracteres.\n");
    exit(1);
}
echo "Confirmar contraseña: ";
$repeat = rtrim((string)fgets(STDIN), "\r\n");
if (!hash_equals($pass, $repeat)) {
    fwrite(STDERR, "Las contraseñas no coinciden.\n");
    exit(1);
}
if ($pass === 'admin123') {
    echo "AVISO: 'admin123' es una contraseña de demostración insegura. Cámbiala antes de publicar la aplicación.\n";
}
try {
    $actualizada = Usuario::configurarAdmin($nombre, $email, $pass);
    echo $actualizada ? "\nCuenta de administrador actualizada.\n" : "\nAdministrador creado.\n";
    echo "Ya puedes iniciar sesión en login.php.\n";
} catch (PDOException $e) {
    fwrite(STDERR, "No se pudo configurar el administrador.\n");
    exit(1);
}
