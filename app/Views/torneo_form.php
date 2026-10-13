    <!-- PASO 1 · CONFIGURACIÓN Y SORTEO -->
    <section id="paso1" class="paso-seccion activo">
        <div class="config-box config-torneo-origen">
            <div class="config-campo">
                <label for="origenTorneo">Origen del torneo</label>
                <select id="origenTorneo" onchange="cambiarOrigenTorneo()">
                    <option value="nacional">Nacional (Perú)</option>
                    <option value="internacional">Internacional</option>
                </select>
            </div>
            <div class="config-campo">
                <label for="numGrupos">Cantidad de grupos</label>
                <select id="numGrupos" onchange="cambiarCantidadGrupos()"></select>
            </div>
            <p id="resumenOrigenTorneo" class="aviso-torneo-origen" role="status" aria-live="polite"></p>
        </div>

        <p id="estadoGuardadoTorneo" class="aviso-torneo-origen estado-publicacion" role="status" aria-live="polite"></p>
        <div class="centrado">
            <button type="button" id="btnSortearGrupos" class="btn btn-primario" onclick="realizarSorteo()">
                <span class="btn-ico" aria-hidden="true">🎲</span>
                <span>Sortear y Ver Grupos →</span>
            </button>
        </div>
    </section>

