<?php
/**
 * Vista de Registro de Usuario — `Views/users/create.php`
 * CORRECCIONES DE SEGURIDAD OWASP APLICADAS SOBRE EL PROYECTO BASE:
 *   1. Jamás re-imprime `$old['password']` en el DOM HTML (evita exposición de secretos).
 *   2. Solo un Administrador autenticado (`$canAssignRole === true`) puede asignar roles
 *      privilegiados (`ADMIN` / `REVIEWER`); el auto-registro público asigna `MEMBER`.
 *   3. Incluye token sincronizador anti-CSRF (`SecurityGuard::csrfField()`).
 */
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/menu.php';
$allowRoleSelect = (bool) ($canAssignRole ?? false);
?>

<div class="page-header">
    <div>
        <h1>Registrar Usuario</h1>
        <p class="muted">Entidad común <code>Usuario</code> &mdash; hash criptográfico BCrypt en <code>UserPassword</code>.</p>
    </div>
    <?php if (!empty($_SESSION['auth'])): ?>
        <a class="btn btn-secondary" href="?route=users.index">&larr; Volver al directorio</a>
    <?php endif; ?>
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
    <form method="POST" action="?route=users.store">
        <?= SecurityGuard::csrfField() ?>

        <div class="form-grid-2">
            <div class="form-group">
                <label for="name">Nombre completo</label>
                <input type="text" id="name" name="name" required
                    placeholder="Ej: Rosary Carmona"
                    value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <?php if (!empty($errors['name'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" required
                    placeholder="usuario@unicartagena.edu.co"
                    value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <?php if (!empty($errors['email'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label for="password">Contraseña <small class="muted">(mínimo 8 caracteres)</small></label>
                <input type="password" id="password" name="password" required minlength="8"
                    value="" autocomplete="new-password" placeholder="Mínimo 8 caracteres">
                <?php if (!empty($errors['password'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="role">Rol de acceso (RBAC)</label>
                <?php if ($allowRoleSelect): ?>
                    <select id="role" name="role">
                        <?php foreach ($roleOptions as $opt): ?>
                            <option value="<?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?>"
                                <?= (($old['role'] ?? 'MEMBER') === $opt) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="hidden" name="role" value="MEMBER">
                    <div style="padding: 10px 12px; border: 1px solid #334155; border-radius: 8px; background: #0b1220;">
                        <span class="badge badge-member">MEMBER</span>
                        <span class="muted" style="font-size: 0.82rem; margin-left: 6px;">Asignado automáticamente por política RBAC</span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($errors['role'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['role'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Registrar Usuario</button>
            <a class="btn btn-secondary" href="<?= !empty($_SESSION['auth']) ? '?route=users.index' : '?route=auth.login' ?>">Cancelar</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>