<?php if (!$esAdmin): ?>
<div id="modalPago" class="modal-pago" role="dialog" aria-modal="true">
    <div class="modal-caja">
        <h3>💳 Pagar entrada</h3>
        <p id="pagoDetalle" style="color:#fff;"></p>
        <form method="post">
            <?= csrf_input() ?>
            <input type="hidden" name="accion" value="pagar_entrada">
            <input type="hidden" name="partido_id" id="pagoPartidoId">
            <label>Nombre del comprador
                <input type="text" name="nombre_comprador" placeholder="Tu nombre" required>
            </label>
            <label>Número de tarjeta (simulado)
                <input type="text" id="tarjetaSimulada" name="tarjeta" inputmode="numeric"
                       autocomplete="off" maxlength="19" minlength="19"
                       pattern="[0-9]{4}( [0-9]{4}){3}" placeholder="0001 0002 0003 0004"
                       title="Escribe exactamente 16 números, separados automáticamente cada cuatro dígitos" required>
                <small style="color:#aaa;line-height:1.4;">Separa los números automáticamente de 4 en 4. Usa datos inventados: no introduzcas tarjetas reales.</small>
            </label>
            <div class="modal-acciones">
                <button type="submit" class="btn btn-primario"><span class="btn-ico">✅</span><span>Confirmar pago</span></button>
                <button type="button" class="btn btn-fantasma" onclick="cerrarPago()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
const tarjetaSimulada = document.getElementById('tarjetaSimulada');
if (tarjetaSimulada) {
    tarjetaSimulada.addEventListener('input', function () {
        const anterior = this.value;
        const indice = this.selectionStart ?? anterior.length;
        const cantidadAntes = anterior.slice(0, indice).replace(/\D/g, '').length;
        const digitos = anterior.replace(/\D/g, '').slice(0, 16);
        const grupos = digitos.match(/.{1,4}/g) || [];
        const formateado = grupos.join(' ');
        this.value = formateado;
        let posicion = 0;
        let vistos = 0;
        while (posicion < formateado.length && vistos < cantidadAntes) {
            if (/\d/.test(formateado[posicion])) vistos++;
            posicion++;
        }
        if (formateado[posicion] === ' ') posicion++;
        this.setSelectionRange(posicion, posicion);
    });
}
function abrirPago(id, titulo, precio) {
    document.getElementById('pagoPartidoId').value = id;
    document.getElementById('pagoDetalle').textContent = titulo + ' — Total: S/ ' + Number(precio).toFixed(2);
    document.getElementById('modalPago').classList.add('abierto');
}
function cerrarPago() {
    document.getElementById('modalPago').classList.remove('abierto');
}
function cargarPartido() {
    const sel = document.getElementById('selPartido');
    const f = document.getElementById('vFecha');
    const e = document.getElementById('vEstadio');
    const p = document.getElementById('vPrecio');
    const opt = (sel && sel.selectedIndex > 0) ? sel.options[sel.selectedIndex] : null;
    if (!opt) { f.value = ''; e.value = ''; p.value = ''; return; }
    f.value = (opt.dataset.fecha || '').replace(' ', 'T').slice(0, 16);
    e.value = opt.dataset.estadio || '';
    p.value = opt.dataset.precio || '';
}
function pagarSeleccionado() {
    const sel = document.getElementById('selPartido');
    if (!sel || !sel.value) {
        alert('Primero elige un partido en el selector.');
        return;
    }
    const opt = sel.options[sel.selectedIndex];
    abrirPago(parseInt(sel.value, 10), opt.dataset.titulo || '', parseFloat(opt.dataset.precio) || 0);
}
</script>
<?php endif; ?>

