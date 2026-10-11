<?php
require_once GOOLPASS_ROOT . '/app/Core/auth.php';
requerir_admin();

$error = '';
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    if (($_POST['accion'] ?? '') !== 'crear_usuario') {
        http_response_code(400);
        exit('Acción no válida.');
    }

    $nombre = trim((string)($_POST['nombre'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    // Desde este panel se crean cuentas de usuario; no se permite conceder el rol admin.
    if ($nombre === '' || mb_strlen($nombre) > 100) {
        $error = 'Introduce un nombre válido de hasta 100 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Introduce un correo electrónico válido.';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe contener al menos 8 caracteres.';
    } else {
        try {
            Usuario::crear($nombre, $email, $password);
            header('Location: usuarios.php?creado=1');
            exit;
        } catch (PDOException $e) {
            $error = 'No fue posible registrar al usuario. Comprueba que el correo no esté registrado.';
        }
    }
}

if (isset($_GET['creado'])) {
    $mensaje = 'Usuario creado correctamente con rol Usuario.';
}
$usuarios = Usuario::listar();
?>
