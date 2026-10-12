<?php if (empty($torneoSeleccionado)): ?>
    <header class="page-heading"><div><p class="eyebrow">Calendario</p><h1>Partidos</h1><p>Elige un torneo para ver sus partidos o programar uno nuevo.</p></div></header>
    <?php if (!empty($errors)): ?><div class="alert" role="alert"><?php foreach ($errors as $error): ?><p><?= h($error) ?></p><?php endforeach; ?></div><?php endif; ?>
    <section><h2>Selecciona un torneo</h2>
        <?php if (empty($torneos)): ?><p class="empty-state">Todavía no hay torneos. Primero crea uno en la sección Torneos.</p>
        <?php else: ?><div class="card-list tournament-list">
            <?php foreach ($torneos as $torneo): ?><article class="card tournament-card"><div><p class="eyebrow"><?= h($torneo['categoria']) ?></p><h3><?= h($torneo['nombre']) ?></h3><p><?= h($torneo['estado'] ?? '') ?> · <?= h($torneo['cantidad_partidos'] ?? 0) ?> partidos</p></div><a class="button" href="<?= h(url_for('partidos', ['torneo_id' => $torneo['id']])) ?>">Gestionar torneo</a></article><?php endforeach; ?>
        </div><?php endif; ?>
    </section>
<?php else: ?>
    <header class="page-heading"><div><p class="eyebrow"><?= h($torneoSeleccionado['categoria']) ?></p><h1><?= h($torneoSeleccionado['nombre']) ?></h1><p>Programa y revisa los partidos de este torneo.</p></div><a class="button button-secondary" href="<?= h(url_for('partidos')) ?>">Volver a torneos</a></header>
    <?php if (!empty($errors)): ?><div class="alert" role="alert"><?php foreach ($errors as $error): ?><p><?= h($error) ?></p><?php endforeach; ?></div><?php endif; ?>
    <?php if (count($equipos) < 2): ?><p class="empty-state">Este torneo necesita al menos dos equipos inscritos para crear partidos. Puedes inscribir equipos desde <a href="<?= h(url_for('torneos')) ?>">Torneos</a>.</p>
    <?php else: ?><section class="panel"><h2>Crear partido</h2><form class="form-grid" method="post" action="<?= h(url_for('partidos/crear')) ?>">
        <?= csrf_field() ?><input type="hidden" name="torneo_id" value="<?= h($torneoSeleccionado['id']) ?>">
        <label>Equipo local<select name="equipo_local_id" required><option value="">Selecciona</option><?php foreach ($equipos as $equipo): ?><option value="<?= h($equipo['id']) ?>"><?= h($equipo['nombre']) ?></option><?php endforeach; ?></select></label>
        <label>Equipo visitante<select name="equipo_visitante_id" required><option value="">Selecciona</option><?php foreach ($equipos as $equipo): ?><option value="<?= h($equipo['id']) ?>"><?= h($equipo['nombre']) ?></option><?php endforeach; ?></select></label>
        <label>Fecha y hora<input type="datetime-local" name="fecha_hora" required></label>
        <label>Estadio<select name="estadio" required>
            <option value="">Selecciona un estadio</option>
            <optgroup label="Perú">
                <option value="Estadio Nacional (Lima)">Estadio Nacional (Lima)</option>
                <option value="Estadio Monumental (Lima)">Estadio Monumental (Lima)</option>
                <option value="Estadio Alejandro Villanueva (Lima)">Estadio Alejandro Villanueva (Lima)</option>
                <option value="Estadio Alberto Gallardo (Lima)">Estadio Alberto Gallardo (Lima)</option>
                <option value="Estadio Miguel Grau (Callao)">Estadio Miguel Grau (Callao)</option>
                <option value="Estadio Monumental de la UNSA (Arequipa)">Estadio Monumental de la UNSA (Arequipa)</option>
                <option value="Estadio Garcilaso de la Vega (Cusco)">Estadio Garcilaso de la Vega (Cusco)</option>
                <option value="Estadio Mansiche (Trujillo)">Estadio Mansiche (Trujillo)</option>
            </optgroup>
            <optgroup label="Internacionales">
                <option value="Wembley Stadium (Londres)">Wembley Stadium (Londres)</option>
                <option value="Santiago Bernabéu (Madrid)">Santiago Bernabéu (Madrid)</option>
                <option value="Camp Nou (Barcelona)">Camp Nou (Barcelona)</option>
                <option value="San Siro (Milán)">San Siro (Milán)</option>
                <option value="Allianz Arena (Múnich)">Allianz Arena (Múnich)</option>
                <option value="Maracanã (Río de Janeiro)">Maracanã (Río de Janeiro)</option>
                <option value="La Bombonera (Buenos Aires)">La Bombonera (Buenos Aires)</option>
            </optgroup>
            <option value="otro">Otro estadio</option>
        </select></label>
        <label data-other-stadium hidden style="display:none">Nombre del estadio<input name="estadio_otro" maxlength="140" disabled></label>
        <label>Precio de entrada<input type="number" name="precio_entrada" min="0" step="0.01" value="0.00" required></label><label>Aforo permitido<input type="number" name="aforo" min="1" step="1" required></label>
        <div class="form-action"><button class="button" type="submit">Guardar partido</button></div>
    </form></section>
    <script>
    (() => {
        const home = document.querySelector('[name="equipo_local_id"]');
        const away = document.querySelector('[name="equipo_visitante_id"]');
        const updateOptions = () => {
            if (home.value && home.value === away.value) away.value = '';
            [...home.options].forEach(option => {
                const conflict = option.value !== '' && option.value === away.value;
                option.hidden = conflict;
                option.disabled = conflict;
            });
            [...away.options].forEach(option => {
                const conflict = option.value !== '' && option.value === home.value;
                option.hidden = conflict;
                option.disabled = conflict;
            });
        };
        home.addEventListener('change', updateOptions);
        away.addEventListener('change', updateOptions);
        updateOptions();
    })();
    </script>
    <script>
    (() => {
        const stadium = document.querySelector('[name="estadio"]');
        const otherField = document.querySelector('[data-other-stadium]');
        const otherInput = otherField.querySelector('input');
        const updateOtherField = () => {
            const isOther = stadium.value === 'otro';
            otherField.hidden = !isOther;
            otherField.style.display = isOther ? 'grid' : 'none';
            otherInput.required = isOther;
            otherInput.disabled = !isOther;
        };
        stadium.addEventListener('change', updateOtherField);
        updateOtherField();
    })();
    </script>
    <?php endif; ?>
    <section class="section-gap"><h2>Partidos del torneo</h2><?php if (empty($partidos)): ?><p class="empty-state">Todavía no hay partidos en este torneo.</p><?php else: ?><div class="card-list"><?php foreach ($partidos as $partido): ?><article class="card match-card"><div><h3><?= h($partido['equipo_local_nombre'] ?? 'Equipo local') ?> <span class="vs">vs.</span> <?= h($partido['equipo_visitante_nombre'] ?? 'Equipo visitante') ?></h3><p><?= h($partido['fecha_hora'] ?? '') ?></p><p><?= h($partido['estadio'] ?? '') ?> · Aforo <?= h($partido['aforo'] ?? '') ?> · <?= h($partido['precio_entrada'] ?? '0.00') ?></p></div><div class="card-actions"><a class="button button-secondary" href="<?= h(url_for('entradas', ['partido_id' => $partido['id']])) ?>">Entradas</a><a class="button button-quiet" href="<?= h(url_for('entradas/registro', ['partido_id' => $partido['id']])) ?>">Ver entradas compradas</a></div></article>
        <?php $estadisticasJugadores = $jugadoresPorPartido[$partido['id']] ?? []; ?>
        <details class="panel stats-panel"><summary>Registrar estadísticas por jugador</summary>
            <?php if (empty($estadisticasJugadores)): ?><p class="hint">Aún no hay jugadores registrados en los equipos de este partido.</p><?php else: ?>
            <form method="post" action="<?= h(url_for('partidos/estadisticas', ['id' => $partido['id']])) ?>">
                <?= csrf_field() ?><div class="table-wrap"><table><thead><tr><th>Jugador</th><th>Goles</th><th>Asistencias</th><th>Amarillas</th><th>Rojas</th><th>MVP</th></tr></thead><tbody>
                <?php foreach ($estadisticasJugadores as $jugador): $jid = (string) $jugador['id']; $actual = $estadisticasActuales[$partido['id']][$jid] ?? []; ?>
                    <tr><td><?= h($jugador['nombre']) ?><br><span class="muted"><?= h($jugador['equipo_nombre'] ?? '') ?></span></td>
                    <?php foreach (['goles', 'asistencias', 'tarjetas_amarillas', 'tarjetas_rojas'] as $campo): ?><td><input class="stat-input" type="number" min="0" step="1" name="estadisticas[<?= h($jid) ?>][<?= h($campo) ?>]" value="<?= h($actual[$campo] ?? 0) ?>" aria-label="<?= h($campo) ?> de <?= h($jugador['nombre']) ?>"></td><?php endforeach; ?>
                    <td><input type="checkbox" name="estadisticas[<?= h($jid) ?>][fue_mvp]" value="1" <?= !empty($actual['fue_mvp']) ? 'checked' : '' ?> aria-label="Marcar MVP: <?= h($jugador['nombre']) ?>"></td></tr>
                <?php endforeach; ?></tbody></table></div><button class="button" type="submit">Guardar estadísticas</button>
            </form><?php endif; ?>
        </details>
        <?php endforeach; ?></div><?php endif; ?></section>
<?php endif; ?>
