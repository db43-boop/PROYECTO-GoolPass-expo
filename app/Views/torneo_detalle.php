    <!-- PASO 2 · FASE DE GRUPOS -->
    <section id="paso2" class="paso-seccion">
        <h2 class="seccion-titulo">🏆 Clasificación por Grupos</h2>
        <p id="tituloOrigenGrupos" class="torneo-etiqueta-origen"></p>
        <p class="seccion-sub" id="textoInstruccionesGrupos">
            Pulsa <strong>Clasificar</strong> en los ganadores de cada grupo para enviarlos a las eliminatorias.
        </p>

        <div id="contenedorGrupos" class="grid-grupos"></div>

        <div class="centrado" id="bloqueAccionClasificar">
            <button type="button" class="btn btn-primario" onclick="cargarClasificadosEnBracket()">
                <span class="btn-ico" aria-hidden="true">✨</span>
                <span>Cargar Clasificados a las Eliminatorias →</span>
            </button>
        </div>
    </section>

    <!-- PASO 3 · ELIMINATORIAS -->
    <section id="paso3" class="paso-seccion">
        <div class="bracket-wrapper">
            <h2 class="seccion-titulo">⚔️ Eliminatorias directas</h2>
            <p id="tituloOrigenEliminatorias" class="torneo-etiqueta-origen"></p>
            <p class="seccion-sub" id="textoInstruccionesFinal">Haz clic sobre el equipo vencedor para hacerlo avanzar de ronda.</p>

            <div class="bracket-tree" id="bracketTree"></div>

            <div class="centrado">
                <button type="button" id="btnGuardarCampeon" class="btn btn-primario" onclick="guardarCampeon()" hidden>Guardar y publicar campeón</button>
            </div>
            <div id="campeonBox" class="campeon-box">
                <h2>🏆 ¡Campeón del torneo! 🏆</h2>
                <div id="nombreCampeon"></div>
            </div>
        </div>

        <div class="centrado">
            <button type="button" class="btn btn-fantasma" onclick="mostrarPaso(1)">
                <span class="btn-ico" aria-hidden="true">←</span>
                <span>Volver al sorteo de grupos</span>
            </button>
            <button type="button" id="btnReiniciarTorneo" class="btn btn-fantasma" onclick="reiniciarTorneo()">
                <span class="btn-ico" aria-hidden="true">🌄</span>
                <span>Reiniciar</span>
            </button>
        </div>
    </section>

