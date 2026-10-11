<?php
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
