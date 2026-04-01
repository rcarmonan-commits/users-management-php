/**
 * Solicita confirmación antes de enviar un formulario de eliminación.
 *
 * El formulario debe incluir el atributo `data-name` con el nombre del
 * elemento a eliminar, por ejemplo:
 *   <form ... onsubmit="return confirmDelete(this);" data-name="Juan">
 *
 * @param {HTMLFormElement} form
 * @returns {boolean}
 */
function confirmDelete(form) {

    var name = form.getAttribute('data-name');
    return confirm('¿Eliminar al usuario ' + name + '?');
}
