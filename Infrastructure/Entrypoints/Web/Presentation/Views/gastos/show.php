<?php require __DIR__ . '/../layouts/header.php'; ?>
<?php require __DIR__ . '/../layouts/menu.php'; ?>

<h1>Detalle del gasto</h1>

<?php if (!empty($message)): ?>
    <div class="alert-error">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<table class="detail-table">
    <tr>
        <th>ID</th>
        <td><?= htmlspecialchars($gasto->getId(), ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <tr>
        <th>Fecha</th>
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
        <td><strong>$<?= number_format($gasto->getValorTotalConIVA(), 2, ',', '.') ?></strong></td>
    </tr>
    <tr>
        <th>Nombre de Usuario</th>
        <td><?= htmlspecialchars($gasto->getNombreUsuario(), ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <tr>
        <th>Lugar</th>
        <td><?= htmlspecialchars($gasto->getLugar(), ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <tr>
        <th>Descripción</th>
        <td><?= htmlspecialchars($gasto->getDescripcion(), ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
</table>

<p style="margin-top: 16px;">
    <a class="btn btn-warning" href="?route=gastos.edit&amp;id=<?= urlencode($gasto->getId()) ?>">Editar</a>
    &nbsp;
    <a class="btn" href="?route=gastos.index">Volver al listado</a>
</p>

<?php require __DIR__ . '/../layouts/footer.php'; ?>