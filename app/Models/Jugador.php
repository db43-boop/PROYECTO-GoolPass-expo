<?php
declare(strict_types=1);
namespace GoolPass\Models;
use PDO;

final class Jugador {
    public function __construct(private PDO $db) {}
    public function listarPorEquipo(int $equipoId): array { $s=$this->db->prepare('SELECT * FROM jugadores WHERE equipo_id=:id ORDER BY nombre'); $s->execute([':id'=>$equipoId]); return $s->fetchAll(PDO::FETCH_ASSOC); }
    public function crear(array $d): int { $s=$this->db->prepare('INSERT INTO jugadores(equipo_id,nombre,posicion,numero_camiseta,fecha_nacimiento) VALUES(:equipo,:nombre,:pos,:numero,:fecha)'); $s->execute([':equipo'=>(int)$d['equipo_id'],':nombre'=>trim($d['nombre']??''),':pos'=>$d['posicion']??'',':numero'=>$d['numero_camiseta']??null,':fecha'=>$d['fecha_nacimiento']??null]); return (int)$this->db->lastInsertId(); }
    public function actualizar(int $id,array $d): bool { $s=$this->db->prepare('UPDATE jugadores SET equipo_id=:equipo,nombre=:nombre,posicion=:pos,numero_camiseta=:numero,fecha_nacimiento=:fecha WHERE id=:id'); $s->execute([':equipo'=>(int)$d['equipo_id'],':nombre'=>trim($d['nombre']??''),':pos'=>$d['posicion']??'',':numero'=>$d['numero_camiseta']??null,':fecha'=>$d['fecha_nacimiento']??null,':id'=>$id]); return $s->rowCount()>0; }
    public function eliminar(int $id): bool { $s=$this->db->prepare('DELETE FROM jugadores WHERE id=:id'); $s->execute([':id'=>$id]); return $s->rowCount()>0; }
}
