<?php
declare(strict_types=1);
namespace GoolPass\Models;
use PDO;

final class EstadisticaPartido {
    public function __construct(private PDO $db) {}
    public function guardar(array $d): int { $s=$this->db->prepare('INSERT INTO estadisticas_partido(partido_id,jugador_id,goles,asistencias,tarjetas_amarillas,tarjetas_rojas,fue_mvp) VALUES(:p,:j,:g,:a,:ta,:tr,:m) ON CONFLICT(partido_id,jugador_id) DO UPDATE SET goles=excluded.goles,asistencias=excluded.asistencias,tarjetas_amarillas=excluded.tarjetas_amarillas,tarjetas_rojas=excluded.tarjetas_rojas,fue_mvp=excluded.fue_mvp'); $partido=(int)$d['partido_id']; $jugador=(int)$d['jugador_id']; $s->execute([':p'=>$partido,':j'=>$jugador,':g'=>$d['goles']??0,':a'=>$d['asistencias']??0,':ta'=>$d['tarjetas_amarillas']??0,':tr'=>$d['tarjetas_rojas']??0,':m'=>!empty($d['fue_mvp'])?1:0]); $q=$this->db->prepare('SELECT id FROM estadisticas_partido WHERE partido_id=:p AND jugador_id=:j'); $q->execute([':p'=>$partido,':j'=>$jugador]); return (int)$q->fetchColumn(); }
    public function listarPorPartido(int $partidoId): array { $s=$this->db->prepare('SELECT ep.*,j.nombre jugador_nombre,j.posicion,e.nombre equipo_nombre FROM estadisticas_partido ep JOIN jugadores j ON j.id=ep.jugador_id JOIN equipos e ON e.id=j.equipo_id WHERE ep.partido_id=:id ORDER BY e.nombre,j.nombre'); $s->execute([':id'=>$partidoId]); return $s->fetchAll(PDO::FETCH_ASSOC); }
}
