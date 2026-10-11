<?php
require_once GOOLPASS_ROOT . '/app/Core/auth.php';

// Si ya hay sesión, fuera del login.
if (esta_logueado()) {
    header('Location: index.php');
    exit;
}

$error = '';
$modo = 'login'; // 'login' | 'registro'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'registro') {
        $modo = 'registro';
        $nombre = trim($_POST['nombre'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        $pass   = $_POST['password'] ?? '';
        // El registro público siempre crea usuarios (el admin se crea por defecto).
        $rol = 'usuario';

        if ($nombre === '' || $email === '' || $pass === '') {
            $error = 'Completa nombre, correo y contraseña.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'El correo no tiene un formato válido.';
        } elseif (strlen($pass) < 6) {
            $error = 'La contraseña debe tener al menos 6 caracteres.';
        } else {
            try {
                if (Usuario::buscarPorCorreo($email)) {
                    $error = 'Ese correo ya está registrado. Inicia sesión.';
                } else {
                    $idCreado = Usuario::crear($nombre, $email, $pass, $rol);
                    iniciar_sesion([
                        'id' => $idCreado,
                        'nombre' => $nombre, 'email' => $email, 'rol' => $rol,
                    ]);
                    header('Location: index.php?bienvenido=1');
                    exit;
                }
            } catch (PDOException $e) {
                $error = 'Error al registrar: ' . $e->getMessage();
            }
        }
    } elseif ($accion === 'login') {
        $modo = 'login';
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';

        if ($email === '' || $pass === '') {
            $error = 'Escribe tu correo y contraseña.';
        } else {
            try {
                $u = Usuario::buscarPorCorreo($email);

                if ($u && password_verify($pass, $u['password'])) {
                    iniciar_sesion($u);
                    header('Location: index.php?bienvenido=1');
                    exit;
                }
                $error = 'Correo o contraseña incorrectos.';
            } catch (PDOException $e) {
                $error = 'Error al iniciar sesión: ' . $e->getMessage();
            }
        }
    }
}
?>
