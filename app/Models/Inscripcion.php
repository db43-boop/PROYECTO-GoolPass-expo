<?php
declare(strict_types=1);
namespace GoolPass\Models;
use PDO;

final class Inscripcion {
    public function __construct(private PDO $db) {}
    public function inscribir(int $torneoId,int $equipoId): int { $s=$this->db->prepare('INSERT INTO inscripciones(torneo_id,equipo_id) VALUES(:t,:e)'); $s->execute([':t'=>$torneoId,':e'=>$equipoId]); return (int)$this->db->lastInsertId(); }
    public function listarPorTorneo(int $torneoId): array { $s=$this->db->prepare('SELECT i.*,e.nombre equipo_nombre,e.origen FROM inscripciones i JOIN equipos e ON e.id=i.equipo_id WHERE i.torneo_id=:id ORDER BY e.nombre'); $s->execute([':id'=>$torneoId]); return $s->fetchAll(PDO::FETCH_ASSOC); }
    public function listarPorEquipo(int $equipoId): ?array { $s=$this->db->prepare('SELECT i.*,t.nombre torneo_nombre,t.categoria FROM inscripciones i JOIN torneos t ON t.id=i.torneo_id WHERE i.equipo_id=:id'); $s->execute([':id'=>$equipoId]); return $s->fetch(PDO::FETCH_ASSOC) ?: null; }
}
