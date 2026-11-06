<?php require __DIR__ . '/../layouts/header.php'; ?>
<?php require __DIR__ . '/../layouts/menu.php'; ?>

<h1>Editar gasto</h1>

<?php if (!empty($message)): ?>
    <div class="alert-error">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<form method="POST" action="?route=gastos.update" id="form-gasto">
    <input type="hidden" name="id" value="<?= htmlspecialchars($old['id'] ?? $gasto->getId(), ENT_QUOTES, 'UTF-8') ?>">

    <div class="form-group">
        <label for="fecha">Fecha del gasto (YYYY-MM-DD)</label><br>
        <input type="date" id="fecha" name="fecha"
            value="<?= htmlspecialchars($old['fecha'] ?? $gasto->getFecha(), ENT_QUOTES, 'UTF-8') ?>">
        <?php if (!empty($errors['fecha'])): ?>
            <div class="field-error"><?= htmlspecialchars($errors['fecha'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="valorTotalSinIVA">Valor Total Sin IVA ($)</label><br>
        <input type="number" step="0.01" min="0.01" id="valorTotalSinIVA" name="valorTotalSinIVA"
            value="<?= htmlspecialchars((string) ($old['valorTotalSinIVA'] ?? number_format($gasto->getValorTotalSinIVA(), 2, '.', '')), ENT_QUOTES, 'UTF-8') ?>">
        <?php if (!empty($errors['valorTotalSinIVA'])): ?>
            <div class="field-error"><?= htmlspecialchars($errors['valorTotalSinIVA'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="ivaRateHelper">Cálculo rápido de tarifa IVA</label><br>
        <select id="ivaRateHelper">
            <option value="manual" selected>Mantener / Ingreso manual</option>
            <option value="19">Recalcular con IVA 19%</option>
            <option value="5">Recalcular con IVA 5%</option>
            <option value="0">Recalcular Exento 0%</option>
        </select>
    </div>

    <div class="form-group">
        <label for="ivaTotal">IVA Total ($)</label><br>
        <input type="number" step="0.01" min="0" id="ivaTotal" name="ivaTotal"
            value="<?= htmlspecialchars((string) ($old['ivaTotal'] ?? number_format($gasto->getIvaTotal(), 2, '.', '')), ENT_QUOTES, 'UTF-8') ?>">
        <?php if (!empty($errors['ivaTotal'])): ?>
            <div class="field-error"><?= htmlspecialchars($errors['ivaTotal'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="valorTotalConIVA">Valor Total Con IVA ($)</label><br>
        <input type="number" step="0.01" min="0.01" id="valorTotalConIVA" name="valorTotalConIVA"
            value="<?= htmlspecialchars((string) ($old['valorTotalConIVA'] ?? number_format($gasto->getValorTotalConIVA(), 2, '.', '')), ENT_QUOTES, 'UTF-8') ?>">
        <?php if (!empty($errors['valorTotalConIVA'])): ?>
            <div class="field-error"><?= htmlspecialchars($errors['valorTotalConIVA'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="nombreUsuario">Nombre del Usuario Responsable</label><br>
        <input type="text" id="nombreUsuario" name="nombreUsuario"
            value="<?= htmlspecialchars($old['nombreUsuario'] ?? $gasto->getNombreUsuario(), ENT_QUOTES, 'UTF-8') ?>">
        <?php if (!empty($errors['nombreUsuario'])): ?>
            <div class="field-error"><?= htmlspecialchars($errors['nombreUsuario'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="lugar">Lugar / Establecimiento</label><br>
        <input type="text" id="lugar" name="lugar"
            value="<?= htmlspecialchars($old['lugar'] ?? $gasto->getLugar(), ENT_QUOTES, 'UTF-8') ?>">
        <?php if (!empty($errors['lugar'])): ?>
            <div class="field-error"><?= htmlspecialchars($errors['lugar'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="descripcion">Descripción del gasto</label><br>
        <textarea id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($old['descripcion'] ?? $gasto->getDescripcion(), ENT_QUOTES, 'UTF-8') ?></textarea>
        <?php if (!empty($errors['descripcion'])): ?>
            <div class="field-error"><?= htmlspecialchars($errors['descripcion'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>

    <button type="submit" class="btn btn-primary">Guardar cambios</button>
    &nbsp;
    <a class="btn" href="?route=gastos.index">Cancelar</a>
</form>

<?php require __DIR__ . '/../layouts/footer.php'; ?>