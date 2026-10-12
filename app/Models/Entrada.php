<?php
declare(strict_types=1);
namespace GoolPass\Models;
use PDO;
use RuntimeException;

final class Entrada {
    public function __construct(private PDO $db) {}
    public function comprar(int $partidoId,?int $usuarioId,?string $nombre,?string $email,string $codigoQr,?float $precio=null): int {
        $this->db->beginTransaction();
        try {
            $s=$this->db->prepare("SELECT p.aforo,p.precio_entrada,(SELECT COUNT(*) FROM entradas x WHERE x.partido_id=p.id AND LOWER(x.estado_entrada) <> 'cancelada' AND LOWER(x.estado_pago) IN ('pendiente','pagado')) ocupadas FROM partidos p WHERE p.id=:id AND LOWER(p.estado) IN ('programado','en_curso')");
            $s->execute([':id'=>$partidoId]); $p=$s->fetch(PDO::FETCH_ASSOC);
            if (!$p) throw new RuntimeException('Partido no disponible para compra.');
            if ((int)$p['ocupadas'] >= (int)$p['aforo']) throw new RuntimeException('Aforo completo.');
            $s=$this->db->prepare("INSERT INTO entradas(usuario_id,partido_id,estado_pago,estado_entrada,codigo_qr,nombre_comprador,email_comprador,precio_pagado) VALUES(:u,:p,'pendiente','activa',:qr,:n,:e,:precio)");
            $s->execute([':u'=>$usuarioId,':p'=>$partidoId,':qr'=>$codigoQr,':n'=>$nombre,':e'=>$email,':precio'=>$precio ?? (float)$p['precio_entrada']]);
            $id=(int)$this->db->lastInsertId(); $this->db->commit(); return $id;
        } catch (\Throwable $e) { if($this->db->inTransaction()) $this->db->rollBack(); throw $e; }
    }
    public function listarPorPartido(int $partidoId): array { $s=$this->db->prepare("SELECT e.id,e.partido_id,e.estado_pago,e.estado_entrada,e.codigo_qr,e.precio_pagado,e.fecha_compra,COALESCE(NULLIF(e.nombre_comprador,''),u.nombre,'Invitado') comprador_nombre,COALESCE(NULLIF(e.email_comprador,''),u.email,'') comprador_email,(el.nombre || ' vs ' || ev.nombre) partido_nombre,t.nombre torneo_nombre FROM entradas e JOIN partidos p ON p.id=e.partido_id JOIN equipos el ON el.id=p.equipo_local_id JOIN equipos ev ON ev.id=p.equipo_visitante_id LEFT JOIN usuarios u ON u.id=e.usuario_id LEFT JOIN torneos t ON t.id=p.torneo_id WHERE e.partido_id=:id ORDER BY e.fecha_compra DESC,e.id DESC"); $s->execute([':id'=>$partidoId]); return $s->fetchAll(PDO::FETCH_ASSOC); }
    public function listarPorUsuario(int $usuarioId): array { $s=$this->db->prepare("SELECT e.id,e.usuario_id,e.partido_id,e.estado_pago,e.estado_entrada,e.codigo_qr,e.nombre_comprador,e.email_comprador,e.precio_pagado,e.fecha_compra,p.fecha_hora,p.estadio,p.estado estado_partido,el.nombre equipo_local,ev.nombre equipo_visitante,(el.nombre || ' vs ' || ev.nombre) partido_nombre,t.nombre torneo_nombre FROM entradas e JOIN partidos p ON p.id=e.partido_id JOIN equipos el ON el.id=p.equipo_local_id JOIN equipos ev ON ev.id=p.equipo_visitante_id LEFT JOIN torneos t ON t.id=p.torneo_id WHERE e.usuario_id=:usuario_id ORDER BY e.fecha_compra DESC"); $s->execute([':usuario_id'=>$usuarioId]); return $s->fetchAll(PDO::FETCH_ASSOC); }
    public function listarTodas(): array { return $this->db->query("SELECT e.id,e.estado_pago,e.estado_entrada,e.codigo_qr,e.precio_pagado,e.fecha_compra,COALESCE(NULLIF(e.nombre_comprador,''),u.nombre,'Invitado') comprador_nombre,COALESCE(NULLIF(e.email_comprador,''),u.email,'') comprador_email,(el.nombre || ' vs ' || ev.nombre) partido_nombre,t.nombre torneo_nombre FROM entradas e JOIN partidos p ON p.id=e.partido_id JOIN equipos el ON el.id=p.equipo_local_id JOIN equipos ev ON ev.id=p.equipo_visitante_id LEFT JOIN usuarios u ON u.id=e.usuario_id LEFT JOIN torneos t ON t.id=p.torneo_id ORDER BY e.fecha_compra DESC,e.id DESC")->fetchAll(PDO::FETCH_ASSOC); }
    public function cambiarEstadoPago(int $id,string $estado): bool { $s=$this->db->prepare('UPDATE entradas SET estado_pago=:e WHERE id=:id'); $s->execute([':e'=>$estado,':id'=>$id]); return $s->rowCount()>0; }
}
