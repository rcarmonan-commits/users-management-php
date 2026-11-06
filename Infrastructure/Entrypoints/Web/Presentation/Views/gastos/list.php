<?php
/**
 * Vista de Listado y Filtrado Contable de Gastos — `Views/gastos/list.php`
 * Alimentada por la consulta CQRS `GetAllGastosQuery` con soporte de caché.
 */
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/menu.php';
?>

<div class="page-header">
    <div>
        <h1>Libro de Gastos Registrados (#6)</h1>
        <p class="muted">Consulta CQRS <code>GetAllGastosQuery</code> con consolidación contable automática.</p>
    </div>
    <div class="header-actions">
        <a class="btn btn-secondary" href="?route=reports">Reportes Analíticos</a>
        <a class="btn btn-primary" href="?route=gastos.create">+ Nuevo Gasto</a>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="alert-error">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert-success">
        <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="card-panel" style="margin-bottom: 18px;">
    <form method="GET" action="" class="filter-bar">
        <input type="hidden" name="route" value="gastos.index">
        <input type="text" name="nombreUsuario" placeholder="Filtrar por usuario..."
            value="<?= htmlspecialchars($filters['nombreUsuario'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <input type="date" name="fechaDesde" title="Fecha desde"
            value="<?= htmlspecialchars($filters['fechaDesde'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <input type="date" name="fechaHasta" title="Fecha hasta"
            value="<?= htmlspecialchars($filters['fechaHasta'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" class="btn btn-primary">Filtrar</button>
        <a class="btn btn-secondary" href="?route=gastos.index">Limpiar</a>
    </form>
</div>

<div class="kpi-grid" style="margin-bottom: 18px;">
    <div class="kpi-card">
        <span class="kpi-label">Registros</span>
        <strong class="kpi-value"><?= count($gastos) ?></strong>
    </div>
    <div class="kpi-card">
        <span class="kpi-label">Subtotal Sin IVA</span>
        <strong class="kpi-value">$<?= number_format((float) ($totals['sinIva'] ?? 0), 2, ',', '.') ?></strong>
    </div>
    <div class="kpi-card">
        <span class="kpi-label">Total IVA</span>
        <strong class="kpi-value">$<?= number_format((float) ($totals['iva'] ?? 0), 2, ',', '.') ?></strong>
    </div>
    <div class="kpi-card kpi-highlight">
        <span class="kpi-label">Total Con IVA</span>
        <strong class="kpi-value">$<?= number_format((float) ($totals['conIva'] ?? 0), 2, ',', '.') ?></strong>
    </div>
</div>

<?php if (empty($gastos)): ?>
    <div class="card-panel empty-state">
        <p>No se encontraron gastos registrados para los criterios seleccionados.</p>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th>Lugar</th>
                    <th>Descripción</th>
                    <th class="col-money">Sin IVA</th>
                    <th class="col-money">IVA</th>
                    <th class="col-money">Total Con IVA</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($gastos as $gasto): ?>
                    <tr>
                        <td class="col-id"><?= htmlspecialchars($gasto->getId(), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($gasto->getFecha(), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="badge badge-member"><?= htmlspecialchars($gasto->getNombreUsuario(), ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td><strong><?= htmlspecialchars($gasto->getLugar(), ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td><?= htmlspecialchars($gasto->getDescripcion(), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="col-money">$<?= number_format($gasto->getValorTotalSinIVA(), 2, ',', '.') ?></td>
                        <td class="col-money">$<?= number_format($gasto->getIvaTotal(), 2, ',', '.') ?></td>
                        <td class="col-money"><strong class="text-emerald">$<?= number_format($gasto->getValorTotalConIVA(), 2, ',', '.') ?></strong></td>
                        <td class="col-actions">
                            <a class="btn btn-secondary btn-sm" href="?route=gastos.show&amp;id=<?= urlencode($gasto->getId()) ?>">Ver</a>
                            <a class="btn btn-warning btn-sm" href="?route=gastos.edit&amp;id=<?= urlencode($gasto->getId()) ?>">Editar</a>
                            <form method="POST" action="?route=gastos.delete" class="inline-form"
                                onsubmit="return confirmDeleteGasto(this);"
                                data-lugar="<?= htmlspecialchars($gasto->getLugar(), ENT_QUOTES, 'UTF-8') ?>">
                                <?= SecurityGuard::csrfField() ?>
                                <input type="hidden" name="id" value="<?= htmlspecialchars($gasto->getId(), ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../layouts/footer.php'; ?>