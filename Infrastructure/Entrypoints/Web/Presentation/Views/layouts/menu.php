<?php
declare(strict_types=1);
/**
 * Barra de Navegación Unificada con Control de Acceso Basado en Roles (RBAC).
 * - Adapta dinámicamente sus enlaces según el estado de sesión y rol (`ADMIN` vs `MEMBER`).
 * - Resalta la sección activa para mejorar la orientación visual del usuario.
 */
$authUser = $_SESSION['auth'] ?? null;
$currentRoute = isset($_GET['route']) ? trim((string) $_GET['route']) : 'home';
$isAdminNav = is_array($authUser) && (($authUser['role'] ?? '') === 'ADMIN');
?>

<nav class="top-nav" aria-label="Navegación principal">
    <div class="nav-links">
        <a href="?route=home" class="nav-brand">Control Gastos #6</a>
        <a href="?route=home" class="nav-link <?= $currentRoute === 'home' ? 'active' : '' ?>">Inicio</a>
        <a href="?route=about" class="nav-link <?= $currentRoute === 'about' ? 'active' : '' ?>">Guía &amp; Acerca de</a>

        <?php if ($authUser): ?>
            <a href="?route=users.index" class="nav-link <?= str_starts_with($currentRoute, 'users.') ? 'active' : '' ?>">Usuarios</a>
            <a href="?route=gastos.index" class="nav-link <?= str_starts_with($currentRoute, 'gastos.') ? 'active' : '' ?>">Gastos</a>
            <a href="?route=reports" class="nav-link <?= $currentRoute === 'reports' ? 'active' : '' ?>">Reportes CQRS</a>
            <?php if ($isAdminNav): ?>
                <a href="?route=smtp.config" class="nav-link <?= $currentRoute === 'smtp.config' ? 'active' : '' ?>">Config. SMTP</a>
                <a href="?route=emails.outbox" class="nav-link <?= $currentRoute === 'emails.outbox' ? 'active' : '' ?>">Bandeja correos</a>
            <?php endif; ?>
            <a href="?route=api.tester" class="nav-link <?= $currentRoute === 'api.tester' ? 'active' : '' ?>">Consola API</a>
            <a href="?route=swagger" target="_blank" rel="noopener" class="nav-link">Swagger API</a>
        <?php else: ?>
            <a href="?route=auth.login" class="nav-link <?= $currentRoute === 'auth.login' ? 'active' : '' ?>">Iniciar sesión</a>
            <a href="?route=users.create" class="nav-link <?= $currentRoute === 'users.create' ? 'active' : '' ?>">Registrarse</a>
            <a href="?route=auth.forgot" class="nav-link <?= $currentRoute === 'auth.forgot' ? 'active' : '' ?>">Recuperar clave</a>
            <a href="?route=api.tester" class="nav-link <?= $currentRoute === 'api.tester' ? 'active' : '' ?>">Consola API</a>
            <a href="?route=swagger" target="_blank" rel="noopener" class="nav-link">Swagger API</a>
        <?php endif; ?>
    </div>

    <?php if ($authUser): ?>
        <div class="nav-right">
            <span class="badge <?= $isAdminNav ? 'badge-admin' : 'badge-member' ?>">
                <?= htmlspecialchars((string) ($authUser['role'] ?? 'MEMBER'), ENT_QUOTES, 'UTF-8') ?>
            </span>
            <span class="nav-user"><?= htmlspecialchars((string) ($authUser['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            <a href="?route=auth.logout" class="btn btn-danger btn-sm">Salir</a>
        </div>
    <?php endif; ?>
</nav>