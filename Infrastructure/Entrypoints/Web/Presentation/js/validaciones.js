/**
 * ============================================================================
 * GUÍA DE PRESENTACIÓN CLIENTE — validaciones.js
 * ============================================================================
 * ¿QUÉ HACE?
 *   1. Solicita confirmación explícita antes de ejecutar operaciones destructivas
 *      (`users.delete` y `gastos.delete`).
 *   2. Calcula en tiempo real `ivaTotal` y `valorTotalConIVA` según la tarifa
 *      tributaria seleccionada (`19%`, `5%`, `0%` o manual) redondeando al centavo.
 *
 * NOTA DE SEGURIDAD (OWASP A04 — Insecure Design):
 *   Toda validación en JavaScript es únicamente de cortesía UX; el servidor vuelve
 *   a verificar invariantes en `GastoModel` y `UserModel` dentro del Dominio.
 * ============================================================================
 */

function confirmDelete(form) {
    var name = form.getAttribute('data-name') || '';
    return confirm('¿Confirmas eliminar al usuario "' + name + '"?');
}

function confirmDeleteGasto(form) {
    var lugar = form.getAttribute('data-lugar') || '';
    return confirm('¿Confirmas eliminar el gasto registrado en "' + lugar + '"?');
}

document.addEventListener('DOMContentLoaded', function () {
    var sinIvaInput = document.getElementById('valorTotalSinIVA');
    var ivaInput = document.getElementById('ivaTotal');
    var conIvaInput = document.getElementById('valorTotalConIVA');
    var rateSelect = document.getElementById('ivaRateHelper');

    if (!sinIvaInput || !ivaInput || !conIvaInput || !rateSelect) {
        return;
    }

    function recalculateTotals() {
        var mode = rateSelect.value;
        var base = parseFloat(sinIvaInput.value);

        if (isNaN(base) || base < 0) {
            return;
        }

        if (mode !== 'manual') {
            var rate = parseFloat(mode);
            var iva = Math.round(base * rate) / 100;
            var total = Math.round((base + iva) * 100) / 100;
            ivaInput.value = iva.toFixed(2);
            conIvaInput.value = total.toFixed(2);
        }
    }

    sinIvaInput.addEventListener('input', recalculateTotals);
    rateSelect.addEventListener('change', recalculateTotals);

    ivaInput.addEventListener('input', function () {
        rateSelect.value = 'manual';
        var base = parseFloat(sinIvaInput.value);
        var iva = parseFloat(ivaInput.value);
        if (!isNaN(base) && !isNaN(iva)) {
            conIvaInput.value = (Math.round((base + iva) * 100) / 100).toFixed(2);
        }
    });
});