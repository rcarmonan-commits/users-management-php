<?php
/**
 * Vista de Creación de Gasto — `Views/gastos/create.php`
 * - Incluye token sincronizador anti-CSRF (`SecurityGuard::csrfField()`).
 * - El cálculo JS de IVA es una ayuda visual UX; la autoridad contable real reside
 *   en `GastoModel::assertIvaConsistency()` dentro de la capa `Domain/`.
 */
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/menu.php';
?>

<div class="page-header">
    <div>
        <h1>Registrar Nuevo Gasto (Ejercicio #6)</h1>
        <p class="muted">Comando CQRS <code>CreateGastoCommand</code> &rarr; validación contable estricta en <code>GastoModel</code>.</p>
    </div>
    <a class="btn btn-secondary" href="?route=gastos.index">&larr; Volver al listado</a>
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

<div class="card-panel form-card">
    <form method="POST" action="?route=gastos.store" id="form-gasto">
        <?= SecurityGuard::csrfField() ?>

        <div class="form-grid-2">
            <div class="form-group">
                <label for="fecha">Fecha del gasto (YYYY-MM-DD)</label>
                <input type="date" id="fecha" name="fecha" required
                    value="<?= htmlspecialchars($old['fecha'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>">
                <?php if (!empty($errors['fecha'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['fecha'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="nombreUsuario">Nombre del Usuario Responsable</label>
                <input type="text" id="nombreUsuario" name="nombreUsuario" list="lista-usuarios" required
                    placeholder="Ej: Administrador"
                    value="<?= htmlspecialchars($old['nombreUsuario'] ?? ($defaultUserName ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <?php if (!empty($registeredUsers)): ?>
                    <datalist id="lista-usuarios">
                        <?php foreach ($registeredUsers as $u): ?>
                            <option value="<?= htmlspecialchars($u->getName(), ENT_QUOTES, 'UTF-8') ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                <?php endif; ?>
                <?php if (!empty($errors['nombreUsuario'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['nombreUsuario'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label for="valorTotalSinIVA">Valor Total Sin IVA ($)</label>
                <input type="number" step="0.01" min="0.01" id="valorTotalSinIVA" name="valorTotalSinIVA" required
                    placeholder="0.00"
                    value="<?= htmlspecialchars($old['valorTotalSinIVA'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <?php if (!empty($errors['valorTotalSinIVA'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['valorTotalSinIVA'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="ivaRateHelper">Asistente de Tarifa IVA (Colombia)</label>
                <select id="ivaRateHelper">
                    <option value="19">IVA General 19%</option>
                    <option value="5">IVA Reducido 5%</option>
                    <option value="0">Exento 0%</option>
                    <option value="manual">Ingreso manual</option>
                </select>
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label for="ivaTotal">IVA Total ($)</label>
                <input type="number" step="0.01" min="0" id="ivaTotal" name="ivaTotal" required
                    placeholder="0.00"
                    value="<?= htmlspecialchars($old['ivaTotal'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <?php if (!empty($errors['ivaTotal'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['ivaTotal'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="valorTotalConIVA">Valor Total Con IVA ($) <small class="muted">(Sin IVA + IVA)</small></label>
                <input type="number" step="0.01" min="0.01" id="valorTotalConIVA" name="valorTotalConIVA" required
                    placeholder="0.00"
                    value="<?= htmlspecialchars($old['valorTotalConIVA'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <?php if (!empty($errors['valorTotalConIVA'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['valorTotalConIVA'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label for="lugar">Lugar / Establecimiento Comercial</label>
            <input type="text" id="lugar" name="lugar" required
                placeholder="Ej: Librería Nacional Cartagena"
                value="<?= htmlspecialchars($old['lugar'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <?php if (!empty($errors['lugar'])): ?>
                <div class="field-error"><?= htmlspecialchars($errors['lugar'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción detallada del gasto</label>
            <textarea id="descripcion" name="descripcion" rows="3" required
                placeholder="Detalle contable del bien o servicio adquirido..."><?= htmlspecialchars($old['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            <?php if (!empty($errors['descripcion'])): ?>
                <div class="field-error"><?= htmlspecialchars($errors['descripcion'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Registrar Gasto</button>
            <a class="btn btn-secondary" href="?route=gastos.index">Cancelar</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>