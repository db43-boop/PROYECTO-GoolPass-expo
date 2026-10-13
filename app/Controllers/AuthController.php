<?php
/** Acciones de Auth migradas del proyecto entregado. */
/**
 * auth.php · Sistema de sesiones y control de roles (admin / usuario)
 * Se incluye en todas las páginas que requieran autenticación.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// La conexión se crea una sola vez desde app/bootstrap.php.

/** Devuelve el usuario de la sesión actual o null. */
function usuario_actual(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

/** ¿Hay alguien con la sesión iniciada? */
function esta_logueado(): bool
{
    return isset($_SESSION['usuario']['id']);
}

/** ¿El usuario actual es administrador? */
function es_admin(): bool
{
    return esta_logueado() && ($_SESSION['usuario']['rol'] ?? '') === 'admin';
}

/** Obliga a tener sesión; si no, redirige al login. */
function requerir_login(): void
{
    if (!esta_logueado()) {
        header('Location: index.php?r=login');
        exit;
    }
}

/** Obliga a ser admin; si no, redirige al login (o a inicio si es usuario). */
function requerir_admin(): void
{
    if (!esta_logueado()) {
        header('Location: index.php?r=login');
        exit;
    }
    if (!es_admin()) {
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="es"><meta charset="utf-8"><title>Acceso denegado</title>'
            . '<body style="font-family:Arial,sans-serif;max-width:650px;margin:80px auto;padding:20px">'
            . '<h1>Acceso denegado</h1><p>Esta función está disponible únicamente para administradores.</p>'
            . '<a href="index.php">Volver al inicio</a></body></html>';
        exit;
    }
}

/**
 * Inicia sesión de forma segura regenerando el id de sesión.
 * @param array $u Fila del usuario (id, nombre, email, rol).
 */
function iniciar_sesion(array $u): void
{
    session_regenerate_id(true);
    $_SESSION['usuario'] = [
        'id'    => (int) $u['id'],
        'nombre' => $u['nombre'],
        'email' => $u['email'],
        'rol'   => $u['rol'],
    ];
}

/** Cierra la sesión actual. */
function cerrar_sesion(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Token CSRF asociado a la sesión para proteger formularios que modifican datos. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verificar_csrf(): void
{
    $enviado = $_POST['csrf_token'] ?? '';
    if (!is_string($enviado) || !hash_equals(csrf_token(), $enviado)) {
        http_response_code(403);
        exit('Solicitud rechazada: formulario vencido o token CSRF inválido. Recarga la página e inténtalo nuevamente.');
    }
}

/** Escapa texto para HTML de forma segura. */
function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/**
 * Fragmento HTML para la barra de navegación: muestra el usuario
 * y su rol, con un botón para cerrar sesión.
 */
function nav_usuario_html(string $paginaActiva = ''): string
{
    if (!esta_logueado()) {
        return '<a href="index.php?r=login" class="nav-cta">Iniciar sesión</a>';
    }
    $u = usuario_actual();
    $rol = ($u['rol'] === 'admin')
        ? '<span class="rol-badge rol-admin">Admin</span>'
        : '<span class="rol-badge rol-usuario">Usuario</span>';
    $claseUsuarios = $paginaActiva === 'usuarios' ? ' class="activo" aria-current="page"' : '';
    $claseMisEntradas = $paginaActiva === 'mis_entradas' ? ' class="activo" aria-current="page"' : '';
    $clasePerfil = $paginaActiva === 'perfil' ? ' activo' : '';
    $ariaPerfil = $paginaActiva === 'perfil' ? ' aria-current="page"' : '';
    $administracion = es_admin() ? '<a href="index.php?r=usuarios"' . $claseUsuarios . '>Usuarios</a>' : '';
    $misEntradas = !es_admin() ? '<a href="index.php?r=mis_entradas"' . $claseMisEntradas . '>Mis entradas</a>' : '';
    return $administracion . $misEntradas . '<a href="index.php?r=perfil" class="nav-usuario' . $clasePerfil . '"' . $ariaPerfil . '>' 
        . '<span class="nav-usuario-nombre">' . e($u['nombre']) . '</span>' . $rol . '</a>'
        . '<a href="index.php?r=logout" class="nav-salir" title="Cerrar sesión">Salir</a>';
}

final class AuthController {
    public static function login(): void
    {
        $conexion = Database::connection();
        require_once GOOLPASS_ROOT . '/app/Controllers/AuthController.php';

        // Si ya hay sesión, fuera del login.
        if (esta_logueado()) {
            header('Location: index.php?r=index');
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
                            header('Location: index.php?r=index&bienvenido=1');
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
                            header('Location: index.php?r=index&bienvenido=1');
                            exit;
                        }
                        $error = 'Correo o contraseña incorrectos.';
                    } catch (PDOException $e) {
                        $error = 'Error al iniciar sesión: ' . $e->getMessage();
                    }
                }
            }
        }
        View::render('auth', get_defined_vars());
    }

    public static function perfil(): void
    {
        $conexion = Database::connection();
        require_once GOOLPASS_ROOT . '/app/Controllers/AuthController.php';
        requerir_login();

        $u = usuario_actual();
        $denegado = isset($_GET['denegado']);
        View::render('layout', array_merge(get_defined_vars(), ['__page' => 'perfil']));
    }

    public static function usuarios(): void
    {
        $conexion = Database::connection();
        require_once GOOLPASS_ROOT . '/app/Controllers/AuthController.php';
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
                    header('Location: index.php?r=usuarios&creado=1');
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
        View::render('register', get_defined_vars());
    }

    public static function logout(): void
    {
        $conexion = Database::connection();
        require_once GOOLPASS_ROOT . '/app/Controllers/AuthController.php';
        cerrar_sesion();
        header('Location: index.php?r=login&salida=1');
        exit;
    }

}
