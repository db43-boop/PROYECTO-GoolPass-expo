<?php
final class Usuario
{
    public static function buscarPorCorreo(string $email): array|false
    {
        $stmt=Database::connection()->prepare('SELECT id,nombre,email,rol,password FROM usuarios WHERE email=:email LIMIT 1');
        $stmt->execute([':email'=>$email]);return $stmt->fetch();
    }
    public static function listar(): array
    {
        return Database::connection()->query('SELECT id,nombre,email,rol,creado_en FROM usuarios ORDER BY id DESC')->fetchAll();
    }
    public static function crear(string $nombre,string $email,string $password,string $rol='usuario'): int
    {
        if(!in_array($rol,['usuario','admin'],true)) throw new InvalidArgumentException('Rol no válido');
        $db=Database::connection();
        $db->prepare('INSERT INTO usuarios (nombre,email,password,rol) VALUES (:nombre,:email,:password,:rol)')
           ->execute([':nombre'=>$nombre,':email'=>$email,':password'=>password_hash($password,PASSWORD_DEFAULT),':rol'=>$rol]);
        return (int)$db->lastInsertId();
    }
    public static function configurarAdmin(string $nombre,string $email,string $password): bool
    {
        $actual=self::buscarPorCorreo($email);
        if($actual){
            Database::connection()->prepare("UPDATE usuarios SET nombre=:nombre,password=:password,rol='admin' WHERE id=:id")
                ->execute([':nombre'=>$nombre,':password'=>password_hash($password,PASSWORD_DEFAULT),':id'=>$actual['id']]);
            return true;
        }
        self::crear($nombre,$email,$password,'admin');return false;
    }
}
