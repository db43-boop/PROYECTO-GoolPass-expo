<?php
declare(strict_types=1);

use GoolPass\Models\Usuario;

final class AuthController
{
    private Usuario $usuarios;
    public function __construct(PDO $db) { $this->usuarios = new Usuario($db); }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { $this->authenticate(); return; }
        if (!empty($_SESSION['user']['id'])) go_to('equipos');
        show_view('auth', ['errors' => [], 'old' => []]);
    }
    public function register(): void
    {
        check_csrf();
        $name = trim((string)($_POST['nombre'] ?? ''));
        $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $errors = [];
        if ($name === '' || mb_strlen($name) > 120) $errors[] = 'El nombre es obligatorio y admite hasta 120 caracteres.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 254) $errors[] = 'Ingresa un correo válido.';
        if (strlen($password) < 8) $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
        if (!$errors && $this->usuarios->buscarPorEmail($email)) $errors[] = 'Ese correo ya está registrado.';
        if ($errors) { show_view('auth', ['errors' => $errors, 'old' => ['nombre' => $name, 'register_email' => $email]]); return; }
        try {
            $id = $this->usuarios->crear($name, $email, password_hash($password, PASSWORD_DEFAULT));
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => $id, 'nombre' => $name, 'email' => $email];
            go_to('equipos');
        } catch (Throwable $e) { error_log('GoolPass register: '.$e->getMessage()); show_view('auth', ['errors' => ['No se pudo completar el registro.'], 'old' => ['nombre' => $name, 'register_email' => $email]]); }
    }
    public function logout(): void
    {
        need_login(); check_csrf(); $_SESSION = [];
        if (ini_get('session.use_cookies')) { $p = session_get_cookie_params(); setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']); }
        session_destroy(); go_to('login');
    }
    private function authenticate(): void
    {
        check_csrf(); $email = mb_strtolower(trim((string)($_POST['email'] ?? ''))); $password = (string)($_POST['password'] ?? '');
        $user = filter_var($email, FILTER_VALIDATE_EMAIL) ? $this->usuarios->buscarPorEmail($email) : null;
        if (!$user || !password_verify($password, (string)$user['password'])) { show_view('auth', ['errors' => ['Correo o contraseña incorrectos.'], 'old' => ['email' => $email]]); return; }
        session_regenerate_id(true); $_SESSION['user'] = ['id' => (int)$user['id'], 'nombre' => (string)$user['nombre'], 'email' => (string)$user['email']]; go_to('equipos');
    }
}
