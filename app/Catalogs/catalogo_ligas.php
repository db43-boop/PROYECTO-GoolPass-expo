<?php
/** Origen y liga; solo los administradores registran o modifican equipos. */
function ligas_internacionales(): array {
    return ['LaLiga', 'Bundesliga', 'Serie A', 'Premier League', 'Ligue 1', 'J-League', 'Liga MX', 'Champions League'];
}
function ligas_nacionales(): array {
    return ['Liga 1 (Perú)', 'Liga 2 (Perú)', 'Liga 3 (Perú)', 'Copa Perú', 'Liga Femenina (Perú)'];
}
function origen_equipo(array $equipo): string {
    if (in_array(($equipo['origen'] ?? ''), ['nacional', 'internacional'], true)) {
        return $equipo['origen'];
    }
    return in_array(($equipo['liga'] ?? ''), ligas_nacionales(), true) ? 'nacional' : 'internacional';
}
function validar_liga_equipo(array $datos): array {
    $origen = (string)($datos['origen'] ?? 'internacional');
    if (!in_array($origen, ['nacional', 'internacional'], true)) {
        throw new InvalidArgumentException('Selecciona nacional o internacional.');
    }
    if ($origen === 'internacional') {
        $liga = trim((string)($datos['liga'] ?? ''));
        if (!in_array($liga, ligas_internacionales(), true)) {
            throw new InvalidArgumentException('Selecciona una liga internacional válida.');
        }
    } else {
        $liga = trim((string)($datos['liga_nacional'] ?? ''));
        if ($liga === '__otra__') {
            $liga = trim((string)($datos['liga_nacional_otra'] ?? ''));
        } elseif (!in_array($liga, ligas_nacionales(), true)) {
            throw new InvalidArgumentException('Selecciona una liga nacional válida.');
        }
        if ($liga === '' || (function_exists('mb_strlen') ? mb_strlen($liga, 'UTF-8') : strlen($liga)) > 90 || preg_match('/[<>\x00-\x1F]/u', $liga)) {
            throw new InvalidArgumentException('Escribe un nombre de liga nacional válido (máximo 90 caracteres).');
        }
    }
    return [$origen, $liga];
}
