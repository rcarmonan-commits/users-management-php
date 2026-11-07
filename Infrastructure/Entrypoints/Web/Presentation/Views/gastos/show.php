<?php
/**
 * Vista de Detalle Individual de Gasto — `Views/gastos/show.php`
 * Alimentada por `GetGastoByIdQuery`.
 */
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/menu.php';
?>

<div class="page-header">
    <div>
        <h1>Comprobante Detallado de Gasto</h1>
        <p class="muted">Consulta individual CQRS <code>GetGastoByIdQuery</code>.</p>
    </div>
    <div class="header-actions">
        <a class="btn btn-warning" href="?route=gastos.edit&amp;id=<?= urlencode($gasto->getId()) ?>">Editar Gasto</a>
        <a class="btn btn-secondary" href="?route=gastos.index">&larr; Volver al listado</a>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="alert-error">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="card-panel">
    <table class="detail-table">
        <tr>
            <th>Identificador (ID)</th>
            <td class="col-id"><?= htmlspecialchars($gasto->getId(), ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <tr>
            <th>Fecha del Gasto</th>
            <td><?= htmlspecialchars($gasto->getFecha(), ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <tr>
            <th>Valor Total Sin IVA</th>
            <td>$<?= number_format($gasto->getValorTotalSinIVA(), 2, ',', '.') ?></td>
        </tr>
        <tr>
            <th>IVA Total</th>
            <td>$<?= number_format($gasto->getIvaTotal(), 2, ',', '.') ?></td>
        </tr>
        <tr>
            <th>Valor Total Con IVA</th>
            <td><strong class="text-emerald">$<?= number_format($gasto->getValorTotalConIVA(), 2, ',', '.') ?></strong></td>
        </tr>
        <tr>
            <th>Usuario Responsable</th>
            <td><span class="badge badge-member"><?= htmlspecialchars($gasto->getNombreUsuario(), ENT_QUOTES, 'UTF-8') ?></span></td>
        </tr>
        <tr>
            <th>Lugar / Establecimiento</th>
            <td><strong><?= htmlspecialchars($gasto->getLugar(), ENT_QUOTES, 'UTF-8') ?></strong></td>
        </tr>
        <tr>
            <th>Descripción Contable</th>
            <td><?= htmlspecialchars($gasto->getDescripcion(), ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
    </table>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>