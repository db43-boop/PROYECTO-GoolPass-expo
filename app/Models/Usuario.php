<?php
declare(strict_types=1);
namespace GoolPass\Models;
use PDO;

final class Usuario {
    public function __construct(private PDO $db) {}
    public function crear(string $nombre, string $email, string $passwordHash): int {
        $s=$this->db->prepare('INSERT INTO usuarios(nombre,email,password) VALUES(:n,:e,:p)');
        $s->execute([':n'=>trim($nombre), ':e'=>trim($email), ':p'=>$passwordHash]); return (int)$this->db->lastInsertId();
    }
    public function buscarPorEmail(string $email): ?array { $s=$this->db->prepare('SELECT id,nombre,email,password,creado_en FROM usuarios WHERE email=:e COLLATE NOCASE'); $s->execute([':e'=>trim($email)]); return $s->fetch(PDO::FETCH_ASSOC) ?: null; }
    public function buscarPorId(int $id): ?array { $s=$this->db->prepare('SELECT id,nombre,email,creado_en FROM usuarios WHERE id=:id'); $s->execute([':id'=>$id]); return $s->fetch(PDO::FETCH_ASSOC) ?: null; }
}
