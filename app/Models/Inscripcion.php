<?php
/** Participación de equipos en el torneo guardado. No crea una tabla adicional. */
final class Inscripcion {
    public static function idsEquipos(string $origen): array {
        $fila = Torneo::buscar($origen);
        if (!$fila) return [];
        $estado = json_decode((string)$fila['estado_json'], true);
        return is_array($estado) && is_array($estado['orden'] ?? null)
            ? array_values(array_map('intval', $estado['orden'])) : [];
    }
}
