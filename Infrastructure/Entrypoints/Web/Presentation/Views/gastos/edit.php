<?php
/**
 * Vista de Edición de Gasto — `Views/gastos/edit.php`
 * Despacha `POST ?route=gastos.update` protegido con CSRF hacia `UpdateGastoUseCase`.
 */
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/menu.php';
?>

<div class="page-header">
    <div>
        <h1>Editar Gasto</h1>
        <p class="muted">ID de registro: <code><?= htmlspecialchars($gasto->getId(), ENT_QUOTES, 'UTF-8') ?></code></p>
    </div>
    <a class="btn btn-secondary" href="?route=gastos.index">&larr; Volver al listado</a>
</div>

<?php if (!empty($message)): ?>
    <div class="alert-error">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="card-panel form-card">
    <form method="POST" action="?route=gastos.update" id="form-gasto">
        <?= SecurityGuard::csrfField() ?>
        <input type="hidden" name="id" value="<?= htmlspecialchars($old['id'] ?? $gasto->getId(), ENT_QUOTES, 'UTF-8') ?>">

        <div class="form-grid-2">
            <div class="form-group">
                <label for="fecha">Fecha del gasto (YYYY-MM-DD)</label>
                <input type="date" id="fecha" name="fecha" required
                    value="<?= htmlspecialchars($old['fecha'] ?? $gasto->getFecha(), ENT_QUOTES, 'UTF-8') ?>">
                <?php if (!empty($errors['fecha'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['fecha'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="nombreUsuario">Nombre del Usuario Responsable</label>
                <input type="text" id="nombreUsuario" name="nombreUsuario" required
                    value="<?= htmlspecialchars($old['nombreUsuario'] ?? $gasto->getNombreUsuario(), ENT_QUOTES, 'UTF-8') ?>">
                <?php if (!empty($errors['nombreUsuario'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['nombreUsuario'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label for="valorTotalSinIVA">Valor Total Sin IVA ($)</label>
                <input type="number" step="0.01" min="0.01" id="valorTotalSinIVA" name="valorTotalSinIVA" required
                    value="<?= htmlspecialchars((string) ($old['valorTotalSinIVA'] ?? number_format($gasto->getValorTotalSinIVA(), 2, '.', '')), ENT_QUOTES, 'UTF-8') ?>">
                <?php if (!empty($errors['valorTotalSinIVA'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['valorTotalSinIVA'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="ivaRateHelper">Recalcular tarifa IVA</label>
                <select id="ivaRateHelper">
                    <option value="manual" selected>Mantener / Ingreso manual</option>
                    <option value="19">Recalcular con IVA 19%</option>
                    <option value="5">Recalcular con IVA 5%</option>
                    <option value="0">Recalcular Exento 0%</option>
                </select>
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label for="ivaTotal">IVA Total ($)</label>
                <input type="number" step="0.01" min="0" id="ivaTotal" name="ivaTotal" required
                    value="<?= htmlspecialchars((string) ($old['ivaTotal'] ?? number_format($gasto->getIvaTotal(), 2, '.', '')), ENT_QUOTES, 'UTF-8') ?>">
                <?php if (!empty($errors['ivaTotal'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['ivaTotal'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="valorTotalConIVA">Valor Total Con IVA ($)</label>
                <input type="number" step="0.01" min="0.01" id="valorTotalConIVA" name="valorTotalConIVA" required
                    value="<?= htmlspecialchars((string) ($old['valorTotalConIVA'] ?? number_format($gasto->getValorTotalConIVA(), 2, '.', '')), ENT_QUOTES, 'UTF-8') ?>">
                <?php if (!empty($errors['valorTotalConIVA'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['valorTotalConIVA'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label for="lugar">Lugar / Establecimiento</label>
            <input type="text" id="lugar" name="lugar" required
                value="<?= htmlspecialchars($old['lugar'] ?? $gasto->getLugar(), ENT_QUOTES, 'UTF-8') ?>">
            <?php if (!empty($errors['lugar'])): ?>
                <div class="field-error"><?= htmlspecialchars($errors['lugar'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción del gasto</label>
            <textarea id="descripcion" name="descripcion" rows="3" required><?= htmlspecialchars($old['descripcion'] ?? $gasto->getDescripcion(), ENT_QUOTES, 'UTF-8') ?></textarea>
            <?php if (!empty($errors['descripcion'])): ?>
                <div class="field-error"><?= htmlspecialchars($errors['descripcion'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            <a class="btn btn-secondary" href="?route=gastos.index">Cancelar</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>