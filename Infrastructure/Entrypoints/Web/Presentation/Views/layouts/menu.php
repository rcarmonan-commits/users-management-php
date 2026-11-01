<?php declare(strict_types=1); ?>
<?php $authUser = $_SESSION['auth'] ?? null; ?>

<nav class="top-nav">
    <a href="?route=home"><strong>Inicio</strong></a>
    <?php if ($authUser): ?>
        <span class="nav-sep">|</span>
        <a href="?route=users.create">Registrar usuario</a>
        <a href="?route=users.index">Listar usuarios</a>
        <span class="nav-sep">|</span>
        <a href="?route=gastos.create">Registrar gasto</a>
        <a href="?route=gastos.index">Listar gastos</a>
        <span class="nav-sep">|</span>
        <?php if (($authUser['role'] ?? '') === 'ADMIN'): ?>
            <a href="?route=smtp.config">Config. SMTP</a>
        <?php endif; ?>
        <a href="?route=emails.outbox">Bandeja correos</a>
        <a href="?route=swagger" target="_blank">Swagger API</a>
        <span class="nav-sep">|</span>
        <span class="nav-user">👤 <?= htmlspecialchars($authUser['name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($authUser['role'] ?? 'USER', ENT_QUOTES, 'UTF-8') ?>)</span>
        <a href="?route=auth.logout">Cerrar sesión</a>
    <?php else: ?>
        <span class="nav-sep">|</span>
        <a href="?route=auth.login">Iniciar sesión</a>
        <a href="?route=users.create">Registrarse</a>
        <a href="?route=auth.forgot">Recuperar contraseña</a>
        <a href="?route=swagger" target="_blank">Swagger API</a>
    <?php endif; ?>
</nav>

<hr>