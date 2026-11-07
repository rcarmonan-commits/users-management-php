<?php require __DIR__ . '/../layouts/header.php'; ?>
<?php require __DIR__ . '/../layouts/menu.php'; ?>

<h1>Registrar gasto (Ejercicio #6)</h1>

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

<form method="POST" action="?route=gastos.store" id="form-gasto">
    <div class="form-group">
        <label for="fecha">Fecha del gasto (YYYY-MM-DD)</label><br>
        <input type="date" id="fecha" name="fecha"
            value="<?= htmlspecialchars($old['fecha'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>">
        <?php if (!empty($errors['fecha'])): ?>
            <div class="field-error"><?= htmlspecialchars($errors['fecha'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="valorTotalSinIVA">Valor Total Sin IVA ($)</label><br>
        <input type="number" step="0.01" min="0.01" id="valorTotalSinIVA" name="valorTotalSinIVA"
            value="<?= htmlspecialchars($old['valorTotalSinIVA'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <?php if (!empty($errors['valorTotalSinIVA'])): ?>
            <div class="field-error"><?= htmlspecialchars($errors['valorTotalSinIVA'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="ivaRateHelper">Cálculo rápido de tarifa IVA (opcional)</label><br>
        <select id="ivaRateHelper">
            <option value="19">IVA General 19%</option>
            <option value="5">IVA Reducido 5%</option>
            <option value="0">Exento 0%</option>
            <option value="manual">Ingreso manual</option>
        </select>
    </div>

    <div class="form-group">
        <label for="ivaTotal">IVA Total ($)</label><br>
        <input type="number" step="0.01" min="0" id="ivaTotal" name="ivaTotal"
            value="<?= htmlspecialchars($old['ivaTotal'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <?php if (!empty($errors['ivaTotal'])): ?>
            <div class="field-error"><?= htmlspecialchars($errors['ivaTotal'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="valorTotalConIVA">Valor Total Con IVA ($) <small>(debe cumplir Sin IVA + IVA = Con IVA)</small></label><br>
        <input type="number" step="0.01" min="0.01" id="valorTotalConIVA" name="valorTotalConIVA"
            value="<?= htmlspecialchars($old['valorTotalConIVA'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <?php if (!empty($errors['valorTotalConIVA'])): ?>
            <div class="field-error"><?= htmlspecialchars($errors['valorTotalConIVA'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="nombreUsuario">Nombre del Usuario Responsable</label><br>
        <input type="text" id="nombreUsuario" name="nombreUsuario" list="lista-usuarios"
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

    <div class="form-group">
        <label for="lugar">Lugar / Establecimiento</label><br>
        <input type="text" id="lugar" name="lugar"
            value="<?= htmlspecialchars($old['lugar'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <?php if (!empty($errors['lugar'])): ?>
            <div class="field-error"><?= htmlspecialchars($errors['lugar'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="descripcion">Descripción del gasto</label><br>
        <textarea id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($old['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        <?php if (!empty($errors['descripcion'])): ?>
            <div class="field-error"><?= htmlspecialchars($errors['descripcion'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>

    <button type="submit" class="btn btn-primary">Registrar gasto</button>
    &nbsp;
    <a class="btn" href="?route=gastos.index">Cancelar</a>
</form>

<?php require __DIR__ . '/../layouts/footer.php'; ?>