<?php
/**
 * Vista de Cambio Obligatorio de Contraseña Temporal — `Views/auth/change-password.php`
 * Se presenta automáticamente al iniciar sesión con una clave de recuperación temporal.
 */
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/menu.php';
?>

<div class="auth-box">
    <span class="badge badge-amber" style="margin-bottom: 10px; background-color: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3);">
        Seguridad Obligatoria &middot; Clave Temporal Detectada
    </span>
    <h1>Definir Nueva Contraseña</h1>
    <p class="muted">
        Has ingresado al sistema con una clave temporal. Por seguridad y para proteger tu cuenta,
        debes establecer a continuación tu nueva contraseña permanente para continuar.
    </p>

    <?php if (!empty($message)): ?>
        <div class="alert-error">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="?route=auth.change-password.save">
        <?= SecurityGuard::csrfField() ?>

        <div class="form-group">
            <label for="new_password">Nueva Contraseña Permanente (mínimo 8 caracteres)</label>
            <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password" placeholder="••••••••" autofocus>
            <?php if (!empty($errors['new_password'])): ?>
                <div class="field-error"><?= htmlspecialchars($errors['new_password'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirmar Nueva Contraseña</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password" placeholder="••••••••">
            <?php if (!empty($errors['confirm_password'])): ?>
                <div class="field-error"><?= htmlspecialchars($errors['confirm_password'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Guardar Nueva Contraseña y Entrar</button>
    </form>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>