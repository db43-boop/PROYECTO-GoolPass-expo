<?php
declare(strict_types=1);
namespace GoolPass\Models;
use PDO;

final class Torneo {
    public function __construct(private PDO $db) {}
    public function listar(): array { return $this->db->query('SELECT * FROM torneos ORDER BY fecha_inicio DESC, nombre')->fetchAll(PDO::FETCH_ASSOC); }
    public function buscarPorId(int $id): ?array { $s=$this->db->prepare('SELECT * FROM torneos WHERE id=:id'); $s->execute([':id'=>$id]); return $s->fetch(PDO::FETCH_ASSOC) ?: null; }
    public function crear(array $d): int { $s=$this->db->prepare('INSERT INTO torneos(nombre,categoria,formato,estado,fecha_inicio,fecha_fin) VALUES(:n,:c,:f,:e,:i,:fin)'); $s->execute([':n'=>trim($d['nombre']??''),':c'=>$d['categoria']??'',':f'=>$d['formato']??'',':e'=>$d['estado']??'abierto',':i'=>$d['fecha_inicio']??null,':fin'=>$d['fecha_fin']??null]); return (int)$this->db->lastInsertId(); }
    public function actualizar(int $id,array $d): bool { $s=$this->db->prepare('UPDATE torneos SET nombre=:n,categoria=:c,formato=:f,estado=:e,fecha_inicio=:i,fecha_fin=:fin WHERE id=:id'); $s->execute([':n'=>trim($d['nombre']??''),':c'=>$d['categoria']??'',':f'=>$d['formato']??'',':e'=>$d['estado']??'abierto',':i'=>$d['fecha_inicio']??null,':fin'=>$d['fecha_fin']??null,':id'=>$id]); return $s->rowCount()>0; }
}
