<?php
/**
 * Vista de Autenticación Segura — `Views/auth/login.php`
 * - Protegida contra CSRF (`SecurityGuard::csrfField()`), fuerza bruta (Rate Limit)
 *   y fijación de sesión (`session_regenerate_id(true)` al autenticar).
 */
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/menu.php';
?>

<div class="auth-box">
    <span class="badge badge-emerald" style="margin-bottom: 10px;">Acceso Protegido &middot; BCrypt + Anti-CSRF</span>
    <h1>Iniciar Sesión</h1>
    <p class="muted">Ingresa tus credenciales para gestionar Usuarios, Gastos (#6) y Reportes CQRS.</p>

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

    <?php
    $emailPrefill = (string) ($old['email'] ?? ($_GET['email'] ?? ''));
    $hasPrefill = $emailPrefill !== '';
    ?>

    <form method="POST" action="?route=auth.authenticate">
        <?= SecurityGuard::csrfField() ?>

        <div class="form-group">
            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email" required autocomplete="username"
                placeholder="usuario@dominio.com"
                value="<?= htmlspecialchars($emailPrefill, ENT_QUOTES, 'UTF-8') ?>" <?= $hasPrefill ? '' : 'autofocus' ?>>
            <?php if (!empty($errors['email'])): ?>
                <div class="field-error"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required autocomplete="current-password"
                placeholder="••••••••" <?= $hasPrefill ? 'autofocus' : '' ?>>
            <?php if (!empty($errors['password'])): ?>
                <div class="field-error"><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Entrar al Sistema</button>
    </form>

    <div style="margin-top: 18px; display: flex; justify-content: space-between; font-size: 0.88rem;">
        <a href="?route=auth.forgot">¿Olvidaste tu contraseña?</a>
        <a href="?route=users.create">Crear cuenta nueva</a>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>