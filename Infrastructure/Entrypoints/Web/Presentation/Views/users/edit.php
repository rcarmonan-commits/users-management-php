<?php
/**
 * Vista de Edición de Usuario — `Views/users/edit.php`
 * - Preserva el hash BCrypt actual cuando el campo contraseña se envía vacío.
 * - Restringe la edición de `role` y `status` exclusivamente a usuarios `ADMIN`.
 */
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/menu.php';
$allowPrivilegeFields = (bool) ($canEditRoleAndStatus ?? false);
?>

<div class="page-header">
    <div>
        <h1>Editar Usuario</h1>
        <p class="muted">ID de cuenta: <code><?= htmlspecialchars($user->getId(), ENT_QUOTES, 'UTF-8') ?></code></p>
    </div>
    <a class="btn btn-secondary" href="?route=users.index">&larr; Volver al directorio</a>
</div>

<?php if (!empty($message)): ?>
    <div class="alert-error">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="card-panel form-card">
    <form method="POST" action="?route=users.update">
        <?= SecurityGuard::csrfField() ?>
        <input type="hidden" name="id" value="<?= htmlspecialchars($old['id'] ?? $user->getId(), ENT_QUOTES, 'UTF-8') ?>">

        <div class="form-grid-2">
            <div class="form-group">
                <label for="name">Nombre completo</label>
                <input type="text" id="name" name="name" required
                    value="<?= htmlspecialchars($old['name'] ?? $user->getName(), ENT_QUOTES, 'UTF-8') ?>">
                <?php if (!empty($errors['name'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" required
                    value="<?= htmlspecialchars($old['email'] ?? $user->getEmail(), ENT_QUOTES, 'UTF-8') ?>">
                <?php if (!empty($errors['email'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label for="password">Nueva Contraseña <small class="muted">(déjala en blanco para mantener la actual)</small></label>
            <input type="password" id="password" name="password" value="" autocomplete="new-password"
                placeholder="Sin cambios si se deja vacío">
            <?php if (!empty($errors['password'])): ?>
                <div class="field-error"><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label for="role">Rol</label>
                <?php if ($allowPrivilegeFields): ?>
                    <select id="role" name="role">
                        <?php foreach ($roleOptions as $opt): ?>
                            <option value="<?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?>"
                                <?= (($old['role'] ?? $user->getRole()) === $opt) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="hidden" name="role" value="<?= htmlspecialchars($user->getRole(), ENT_QUOTES, 'UTF-8') ?>">
                    <div style="padding: 10px 12px; border: 1px solid #334155; border-radius: 8px; background: #0b1220;">
                        <span class="badge badge-member"><?= htmlspecialchars($user->getRole(), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($errors['role'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['role'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="status">Estado de cuenta</label>
                <?php if ($allowPrivilegeFields): ?>
                    <select id="status" name="status">
                        <?php foreach ($statusOptions as $opt): ?>
                            <option value="<?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?>"
                                <?= (($old['status'] ?? $user->getStatus()) === $opt) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="hidden" name="status" value="<?= htmlspecialchars($user->getStatus(), ENT_QUOTES, 'UTF-8') ?>">
                    <div style="padding: 10px 12px; border: 1px solid #334155; border-radius: 8px; background: #0b1220;">
                        <span class="badge badge-active"><?= htmlspecialchars($user->getStatus(), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($errors['status'])): ?>
                    <div class="field-error"><?= htmlspecialchars($errors['status'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            <a class="btn btn-secondary" href="?route=users.index">Cancelar</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>