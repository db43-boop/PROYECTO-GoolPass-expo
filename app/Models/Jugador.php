<?php
final class Jugador
{
    public static function porEquipo(int $id): array
    {
        $stmt=Database::connection()->prepare('SELECT * FROM jugadores WHERE equipo_id=:id ORDER BY nombre');
        $stmt->execute([':id'=>$id]);return $stmt->fetchAll();
    }
    public static function porEquipos(array $ids): array
    {
        if (!$ids) return [];
        $ids=array_values(array_unique(array_map('intval',$ids)));
        $marcas=implode(',',array_fill(0,count($ids),'?'));
        $stmt=Database::connection()->prepare("SELECT equipo_id,nombre,posicion FROM jugadores WHERE equipo_id IN ($marcas) ORDER BY id");
        $stmt->execute($ids);return $stmt->fetchAll();
    }
    public static function insertar(int $equipoId,array $j): void
    {
        $stmt=Database::connection()->prepare('INSERT INTO jugadores
         (equipo_id,nombre,posicion,goles,asistencias,tarjetas_amarillas,tarjetas_rojas,mvp_count,trofeos_ganados)
         VALUES (:equipo_id,:nombre,:posicion,:goles,:asistencias,:tarjetas_amarillas,:tarjetas_rojas,:mvp_count,:trofeos_ganados)');
        $stmt->execute([
            ':equipo_id'=>$equipoId,':nombre'=>trim((string)($j['nombre']??'')),':posicion'=>trim((string)($j['posicion']??'')),
            ':goles'=>max(0,(int)($j['goles']??0)),':asistencias'=>max(0,(int)($j['asistencias']??0)),
            ':tarjetas_amarillas'=>max(0,(int)($j['tarjetas_amarillas']??0)),':tarjetas_rojas'=>max(0,(int)($j['tarjetas_rojas']??0)),
            ':mvp_count'=>max(0,(int)($j['mvp_count']??0)),':trofeos_ganados'=>max(0,(int)($j['trofeos_ganados']??0)),
        ]);
    }
    public static function reemplazarPlantilla(int $equipoId,array $jugadores): void
    {
        Database::connection()->prepare('DELETE FROM jugadores WHERE equipo_id=:id')->execute([':id'=>$equipoId]);
        foreach ($jugadores as $j) {
            if (trim((string)($j['nombre']??''))!=='' && trim((string)($j['posicion']??''))!=='') self::insertar($equipoId,$j);
        }
    }
    public static function eliminar(int $id): void
    {
        Database::connection()->prepare('DELETE FROM jugadores WHERE id=:id')->execute([':id'=>$id]);
    }
    public static function actualizarParaEquipo(int $id, int $equipoId, array $j): void
    {
        $stmt = Database::connection()->prepare('UPDATE jugadores SET
            nombre=:nombre, posicion=:posicion, goles=:goles, asistencias=:asistencias,
            tarjetas_amarillas=:tarjetas_amarillas, tarjetas_rojas=:tarjetas_rojas,
            mvp_count=:mvp_count, trofeos_ganados=:trofeos_ganados
            WHERE id=:id AND equipo_id=:equipo_id');
        $stmt->execute([
            ':id'=>$id, ':equipo_id'=>$equipoId,
            ':nombre'=>trim((string)($j['nombre']??'')), ':posicion'=>trim((string)($j['posicion']??'')),
            ':goles'=>max(0,(int)($j['goles']??0)), ':asistencias'=>max(0,(int)($j['asistencias']??0)),
            ':tarjetas_amarillas'=>max(0,(int)($j['tarjetas_amarillas']??0)),
            ':tarjetas_rojas'=>max(0,(int)($j['tarjetas_rojas']??0)),
            ':mvp_count'=>max(0,(int)($j['mvp_count']??0)),
            ':trofeos_ganados'=>max(0,(int)($j['trofeos_ganados']??0)),
        ]);
    }
    public static function actualizar(int $id,array $j): void
    {
        $stmt=Database::connection()->prepare('UPDATE jugadores SET nombre=:nombre,posicion=:posicion,goles=:goles,
          asistencias=:asistencias,tarjetas_amarillas=:tarjetas_amarillas,tarjetas_rojas=:tarjetas_rojas,
          mvp_count=:mvp_count,trofeos_ganados=:trofeos_ganados WHERE id=:id');
        $stmt->execute([
         ':id'=>$id,':nombre'=>trim((string)($j['nombre']??'')),':posicion'=>trim((string)($j['posicion']??'')),
         ':goles'=>max(0,(int)($j['goles']??0)),':asistencias'=>max(0,(int)($j['asistencias']??0)),
         ':tarjetas_amarillas'=>max(0,(int)($j['tarjetas_amarillas']??0)),':tarjetas_rojas'=>max(0,(int)($j['tarjetas_rojas']??0)),
         ':mvp_count'=>max(0,(int)($j['mvp_count']??0)),':trofeos_ganados'=>max(0,(int)($j['trofeos_ganados']??0))
        ]);
    }
}

// Generador de la formación visual de jugadores.
/** Renderiza una formación visual orientativa con los jugadores de la plantilla. */
function mostrar_formacion_equipo(array $jugadores): string {
    $puestos = [
        'Delanteros' => [],
        'Centrocampistas' => [],
        'Defensas' => [],
        'Portero' => [],
    ];
    foreach ($jugadores as $j) {
        $p = (string)($j['posicion'] ?? '');
        if (!isset($puestos[$p])) $p = 'Centrocampistas';
        $puestos[$p][] = $j;
    }
    $titulares = [];
    $suplentes = [];
    // Reparte por puesto; no inventa jugadores si faltan y solo presenta 11.
    foreach (array_reverse(array_keys($puestos)) as $puesto) {
        foreach ($puestos[$puesto] as $jugador) {
            if (count($titulares) < 11) {
                $titulares[] = $jugador;
            } else {
                $suplentes[] = $jugador;
            }
        }
    }
    // Si hay suplentes de otros puestos, se incluyen al final de la lista sin perder ninguno.
    $ids = array_map(static fn($j) => (int)($j['id'] ?? 0), $titulares);
    $porPuesto = [];
    foreach ($puestos as $puesto => $lista) {
        $porPuesto[$puesto] = array_values(array_filter($lista, static fn($j) => in_array((int)($j['id'] ?? 0), $ids, true)));
    }
    $h = static fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    $html = '<section class="formacion-bloque"><div class="formacion-encabezado"><div><h2>Formación sobre la cancha</h2><p>Distribución ilustrativa según la posición de los jugadores registrados. No es una alineación oficial.</p></div><span class="formacion-chip">'.count($titulares).' en cancha · '.count($suplentes).' suplentes</span></div>';
    if ($titulares === []) {
        return $html . '<p class="formacion-vacia">Agrega jugadores a la plantilla para visualizar su distribución.</p></section>';
    }
    $html .= '<div class="cancha-futbol" role="img" aria-label="Distribución de jugadores sobre una cancha de fútbol">';
    $html .= '<div class="cancha-centro"></div><div class="cancha-area cancha-area-superior"></div><div class="cancha-area cancha-area-inferior"></div>';
    $posY = ['Delanteros'=>15, 'Centrocampistas'=>39, 'Defensas'=>64, 'Portero'=>87];
    foreach ($posY as $puesto => $y) {
        $fila = $porPuesto[$puesto];
        $n = count($fila);
        if (!$n) continue;
        foreach ($fila as $i=>$j) {
            $x = ($i + 1) * 100 / ($n + 1);
            $nombre = (string)($j['nombre'] ?? 'Jugador');
            $html .= '<div class="futbol-jugador" style="--x:'.round($x, 2).'%;--y:'.$y.'%" title="'.$h($nombre.' · '.$puesto).'">';
            $html .= '<span class="futbol-camiseta" aria-hidden="true">'.($puesto==='Portero' ? '1' : '●').'</span><span class="futbol-nombre">'.$h($nombre).'</span></div>';
        }
    }
    $html .= '</div><div class="formacion-resumen">';
    foreach (['Portero'=>'Arqueros','Defensas'=>'Defensas','Centrocampistas'=>'Mediocampistas','Delanteros'=>'Delanteros'] as $puesto=>$nombre) {
        $html .= '<span>'.$h($nombre).': <b>'.count($porPuesto[$puesto]).'</b></span>';
    }
    $html .= '</div>';
    if ($suplentes) {
        $html .= '<p class="formacion-suplentes"><b>Otros jugadores de la plantilla:</b> '.implode(', ', array_map(static fn($j)=>$h($j['nombre'] ?? ''), $suplentes)).'</p>';
    }
    return $html.'</section>';
}
