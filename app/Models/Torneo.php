<?php
final class Torneo
{
    public static function buscar(string $origen): array|false
    {
        $stmt=Database::connection()->prepare('SELECT estado_json,actualizado_en FROM torneos_guardados WHERE origen=:origen');
        $stmt->execute([':origen'=>$origen]);return $stmt->fetch();
    }
    public static function eliminar(string $origen): void
    {
        Database::connection()->prepare('DELETE FROM torneos_guardados WHERE origen=:origen')->execute([':origen'=>$origen]);
    }
    public static function guardar(string $origen,array $estado): void
    {
        Database::connection()->prepare('INSERT INTO torneos_guardados(origen,estado_json,actualizado_en)
          VALUES (:origen,:estado,CURRENT_TIMESTAMP)
          ON CONFLICT(origen) DO UPDATE SET estado_json=excluded.estado_json,actualizado_en=CURRENT_TIMESTAMP')
          ->execute([':origen'=>$origen,':estado'=>json_encode($estado,JSON_UNESCAPED_UNICODE)]);
    }
}
