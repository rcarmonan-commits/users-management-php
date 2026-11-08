<?php
/**
 * Vista de Recuperación de Contraseña — `Views/auth/forgot-password.php`
 * - Protegida contra enumeración de cuentas (mensaje uniforme + Rate Limit).
 */
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/menu.php';
?>

<div class="auth-box">
    <span class="badge badge-member" style="margin-bottom: 10px;">Recuperación Transaccional SMTP / HTTPS</span>
    <h1>Recuperar Contraseña</h1>
    <p class="muted">Introduce tu correo registrado para recibir una contraseña temporal segura.</p>

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

    <form method="POST" action="?route=auth.forgot.send">
        <?= SecurityGuard::csrfField() ?>

        <div class="form-group">
            <label for="email">Correo electrónico registrado</label>
            <input type="email" id="email" name="email" required autocomplete="email"
                placeholder="correo@dominio.com"
                value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" autofocus>
            <?php if (!empty($errors['email'])): ?>
                <div class="field-error"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Enviar Contraseña Temporal</button>
    </form>

    <p style="margin-top: 18px; text-align: center; font-size: 0.88rem;">
        <a href="?route=auth.login">&larr; Volver al inicio de sesión</a>
    </p>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>