/**
 * Solicita confirmación antes de eliminar un usuario.
 */
function confirmDelete(form) {
    var name = form.getAttribute('data-name') || '';
    return confirm('¿Eliminar al usuario ' + name + '?');
}

/**
 * Solicita confirmación antes de eliminar un gasto.
 */
function confirmDeleteGasto(form) {
    var lugar = form.getAttribute('data-lugar') || '';
    return confirm('¿Eliminar el gasto registrado en "' + lugar + '"?');
}

/**
 * Asistente de cálculo contable en tiempo real para formularios de Gasto:
 * calcula ivaTotal y valorTotalConIVA cuando la tarifa seleccionada no es manual.
 */
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