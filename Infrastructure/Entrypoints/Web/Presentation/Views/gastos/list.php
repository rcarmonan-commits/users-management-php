<?php require __DIR__ . '/../layouts/header.php'; ?>
<?php require __DIR__ . '/../layouts/menu.php'; ?>

<h1>Lista de gastos registrados</h1>

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

<form method="GET" action="" class="filter-bar">
    <input type="hidden" name="route" value="gastos.index">
    <input type="text" name="nombreUsuario" placeholder="Filtrar por usuario..."
        value="<?= htmlspecialchars($filters['nombreUsuario'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <input type="date" name="fechaDesde"
        value="<?= htmlspecialchars($filters['fechaDesde'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <input type="date" name="fechaHasta"
        value="<?= htmlspecialchars($filters['fechaHasta'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <button type="submit" class="btn btn-primary">Filtrar</button>
    <a class="btn" href="?route=gastos.index">Limpiar</a>
    <a class="btn btn-primary" href="?route=gastos.create">+ Nuevo gasto</a>
</form>

<div class="summary-strip">
    <span><strong>Registros:</strong> <?= count($gastos) ?></span>
    <span><strong>Subtotal Sin IVA:</strong> $<?= number_format((float) ($totals['sinIva'] ?? 0), 2, ',', '.') ?></span>
    <span><strong>Total IVA:</strong> $<?= number_format((float) ($totals['iva'] ?? 0), 2, ',', '.') ?></span>
    <span><strong>Total Con IVA:</strong> $<?= number_format((float) ($totals['conIva'] ?? 0), 2, ',', '.') ?></span>
</div>

<?php if (empty($gastos)): ?>
    <p>No hay gastos registrados para los criterios seleccionados.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Usuario</th>
                <th>Lugar</th>
                <th>Descripción</th>
                <th>Sin IVA</th>
                <th>IVA</th>
                <th>Total Con IVA</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($gastos as $gasto): ?>
                <tr>
                    <td class="col-id"><?= htmlspecialchars($gasto->getId(), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($gasto->getFecha(), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($gasto->getNombreUsuario(), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($gasto->getLugar(), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($gasto->getDescripcion(), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="col-money">$<?= number_format($gasto->getValorTotalSinIVA(), 2, ',', '.') ?></td>
                    <td class="col-money">$<?= number_format($gasto->getIvaTotal(), 2, ',', '.') ?></td>
                    <td class="col-money"><strong>$<?= number_format($gasto->getValorTotalConIVA(), 2, ',', '.') ?></strong></td>
                    <td class="col-actions">
                        <a class="btn btn-sm" href="?route=gastos.show&amp;id=<?= urlencode($gasto->getId()) ?>">Ver</a>
                        <a class="btn btn-warning btn-sm" href="?route=gastos.edit&amp;id=<?= urlencode($gasto->getId()) ?>">Editar</a>
                        <form method="POST" action="?route=gastos.delete" style="display:inline;"
                            onsubmit="return confirmDeleteGasto(this);"
                            data-lugar="<?= htmlspecialchars($gasto->getLugar(), ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($gasto->getId(), ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/../layouts/footer.php'; ?>