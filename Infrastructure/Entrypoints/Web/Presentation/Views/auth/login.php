<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="auth-box">
    <h1>Iniciar sesión</h1>

    <?php if (!empty($message)): ?>
        <div class="alert-error">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="?route=auth.authenticate">
        <div class="form-group">
            <label for="email">Correo electrónico</label><br>
            <input type="email" id="email" name="email"
                value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" autofocus>
            <?php if (!empty($errors['email'])): ?>
                <div class="field-error"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="password">Contraseña</label><br>
            <input type="password" id="password" name="password" autocomplete="current-password">
            <?php if (!empty($errors['password'])): ?>
                <div class="field-error"><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary">Entrar</button>
    </form>

    <p style="margin-top: 16px;">
        <a href="?route=auth.forgot">¿Olvidaste tu contraseña?</a>
    </p>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>