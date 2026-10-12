<?php
declare(strict_types=1);
namespace GoolPass\Models;
use PDO;

final class Partido {
    public function __construct(private PDO $db) {}
    public function listar(?int $torneoId=null): array { if($torneoId===null) return $this->db->query('SELECT p.*,el.nombre equipo_local_nombre,ev.nombre equipo_visitante_nombre,t.nombre torneo_nombre FROM partidos p JOIN equipos el ON el.id=p.equipo_local_id JOIN equipos ev ON ev.id=p.equipo_visitante_id LEFT JOIN torneos t ON t.id=p.torneo_id ORDER BY p.fecha_hora')->fetchAll(PDO::FETCH_ASSOC); $s=$this->db->prepare('SELECT p.*,el.nombre equipo_local_nombre,ev.nombre equipo_visitante_nombre,t.nombre torneo_nombre FROM partidos p JOIN equipos el ON el.id=p.equipo_local_id JOIN equipos ev ON ev.id=p.equipo_visitante_id LEFT JOIN torneos t ON t.id=p.torneo_id WHERE p.torneo_id=:t ORDER BY p.fecha_hora'); $s->execute([':t'=>$torneoId]); return $s->fetchAll(PDO::FETCH_ASSOC); }
    public function buscarPorId(int $id): ?array { $s=$this->db->prepare('SELECT p.*,el.nombre equipo_local_nombre,ev.nombre equipo_visitante_nombre,t.nombre torneo_nombre FROM partidos p JOIN equipos el ON el.id=p.equipo_local_id JOIN equipos ev ON ev.id=p.equipo_visitante_id LEFT JOIN torneos t ON t.id=p.torneo_id WHERE p.id=:id'); $s->execute([':id'=>$id]); return $s->fetch(PDO::FETCH_ASSOC) ?: null; }
    public function crear(array $d): int { $s=$this->db->prepare('INSERT INTO partidos(torneo_id,equipo_local_id,equipo_visitante_id,fecha_hora,estadio,precio_entrada,aforo) VALUES(:t,:l,:v,:f,:e,:p,:a)'); $s->execute([':t'=>(int)$d['torneo_id'],':l'=>(int)$d['equipo_local_id'],':v'=>(int)$d['equipo_visitante_id'],':f'=>$d['fecha_hora'],':e'=>$d['estadio']??'',':p'=>$d['precio_entrada']??0,':a'=>(int)$d['aforo']]); return (int)$this->db->lastInsertId(); }
    public function actualizarResultado(int $id,int $golesLocal,int $golesVisitante,string $estado='finalizado'): bool { $s=$this->db->prepare('UPDATE partidos SET goles_local=:gl,goles_visitante=:gv,estado=:e WHERE id=:id'); $s->execute([':gl'=>$golesLocal,':gv'=>$golesVisitante,':e'=>$estado,':id'=>$id]); return $s->rowCount()>0; }
    public function aforoDisponible(int $id): int { $s=$this->db->prepare("SELECT p.aforo - COUNT(CASE WHEN LOWER(COALESCE(e.estado_entrada,'activa')) <> 'cancelada' AND LOWER(COALESCE(e.estado_pago,'pendiente')) IN ('pendiente','pagado') THEN 1 END) AS disponible FROM partidos p LEFT JOIN entradas e ON e.partido_id=p.id WHERE p.id=:id GROUP BY p.id"); $s->execute([':id'=>$id]); return (int)($s->fetchColumn() ?: 0); }
}
