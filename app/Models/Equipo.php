<?php
declare(strict_types=1);
namespace GoolPass\Models;
use PDO;

final class Equipo {
    public function __construct(private PDO $db) {}
    public function listar(): array { return $this->db->query('SELECT * FROM equipos ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC); }
    public function buscarPorId(int $id): ?array { $s=$this->db->prepare('SELECT * FROM equipos WHERE id=:id'); $s->execute([':id'=>$id]); return $s->fetch(PDO::FETCH_ASSOC) ?: null; }
    public function crear(array $d): int { $s=$this->db->prepare('INSERT INTO equipos(nombre,liga,origen,escudo,anio) VALUES(:nombre,:liga,:origen,:escudo,:anio)'); $s->execute([':nombre'=>trim($d['nombre']??''),':liga'=>$d['liga']??'',':origen'=>$d['origen']??'',':escudo'=>$d['escudo']??null,':anio'=>$d['anio']??null]); return (int)$this->db->lastInsertId(); }
    public function actualizar(int $id,array $d): bool { $s=$this->db->prepare('UPDATE equipos SET nombre=:nombre, liga=:liga, origen=:origen, escudo=:escudo, anio=:anio WHERE id=:id'); $s->execute([':nombre'=>trim($d['nombre']??''),':liga'=>$d['liga']??'',':origen'=>$d['origen']??'',':escudo'=>$d['escudo']??null,':anio'=>$d['anio']??null,':id'=>$id]); return $s->rowCount()>0; }
    public function eliminar(int $id): bool { $s=$this->db->prepare('DELETE FROM equipos WHERE id=:id'); $s->execute([':id'=>$id]); return $s->rowCount()>0; }
}
