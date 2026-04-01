<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="auth-box">
    <h1>Recuperar contraseña</h1>

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

    <p>Introduce el correo con el que te registraste y te enviaremos una contraseña temporal.</p>

    <form method="POST" action="?route=auth.forgot.send">
        <div class="form-group">
            <label for="email">Correo electrónico</label><br>
            <input type="email" id="email" name="email"
                value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" autofocus>
            <?php if (!empty($errors['email'])): ?>
                <div class="field-error"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary">Enviar contraseña temporal</button>
    </form>

    <p style="margin-top: 16px;">
        <a href="?route=auth.login">Volver al inicio de sesión</a>
    </p>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>