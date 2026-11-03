<?php
/**
 * Vista Principal (Dashboard Ejecutivo) — `Infrastructure/Entrypoints/Web/Presentation/Views/home.php`
 * Presenta indicadores financieros en tiempo real cuando hay sesión autenticada
 * y acceso directo a los módulos CRUDL, Reportes CQRS, Guía Técnica y API OpenAPI.
 */
require __DIR__ . '/layouts/header.php';
require __DIR__ . '/layouts/menu.php';
$authHome = $_SESSION['auth'] ?? null;
?>

<section class="hero-card">
    <div>
        <span class="badge badge-emerald">Dominio Puro &rarr; Aplicación CQRS &rarr; Puertos &amp; Adaptadores</span>
        <h1>Sistema Empresarial de Usuarios y Control de Gastos (#6)</h1>
        <p class="subtitle">
            Aplicación construida desde el dominio hacia afuera en <strong>PHP 8.3</strong> siguiendo
            <strong>Domain-Driven Design (DDD)</strong>, <strong>Arquitectura Hexagonal</strong>,
            <strong>CQRS</strong> e <strong>Inversión de Dependencias</strong> con protección integral <strong>OWASP Top 10</strong>.
        </p>
    </div>
    <div class="hero-actions">
        <?php if ($authHome): ?>
            <a class="btn btn-primary" href="?route=gastos.create">+ Nuevo Gasto</a>
            <a class="btn btn-secondary" href="?route=reports">Ver Reportes CQRS</a>
        <?php else: ?>
            <a class="btn btn-primary" href="?route=auth.login">Iniciar Sesión</a>
            <a class="btn btn-secondary" href="?route=about">Explorar Guía Técnica</a>
        <?php endif; ?>
    </div>
</section>

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

<?php if (!empty($kpis) && is_array($kpis)): ?>
    <section class="kpi-grid" aria-label="Indicadores globales del sistema">
        <div class="kpi-card">
            <span class="kpi-label">Gastos Registrados</span>
            <strong class="kpi-value"><?= (int) ($kpis['gastosCount'] ?? 0) ?></strong>
            <span class="kpi-foot">Consultados vía GetAllGastosQuery</span>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">Subtotal Sin IVA</span>
            <strong class="kpi-value">$<?= number_format((float) ($kpis['totalSinIva'] ?? 0), 2, ',', '.') ?></strong>
            <span class="kpi-foot">Precisión exacta en centavos</span>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">IVA Acumulado</span>
            <strong class="kpi-value">$<?= number_format((float) ($kpis['totalIva'] ?? 0), 2, ',', '.') ?></strong>
            <span class="kpi-foot">Invariante Sin IVA + IVA = Total</span>
        </div>
        <div class="kpi-card kpi-highlight">
            <span class="kpi-label">Total General Con IVA</span>
            <strong class="kpi-value">$<?= number_format((float) ($kpis['totalConIva'] ?? 0), 2, ',', '.') ?></strong>
            <span class="kpi-foot">Usuarios activos: <?= (int) ($kpis['usersCount'] ?? 0) ?> &middot; Driver: <?= htmlspecialchars((string) ($kpis['driver'] ?? 'mysql'), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </section>
<?php endif; ?>

<div class="dashboard-grid">
    <div class="card-panel">
        <h2>Entidad Común: Usuarios (CRUDL)</h2>
        <p class="muted">Gestión completa de cuentas con contraseñas BCrypt, control de roles (<code>ADMIN</code>, <code>MEMBER</code>, <code>REVIEWER</code>) y notificaciones transaccionales.</p>
        <ul class="feature-list">
            <li><strong>Crear (Command):</strong> <a href="?route=users.create">Registrar nuevo usuario</a></li>
            <li><strong>Listar / Leer / Editar / Eliminar:</strong> <a href="?route=users.index">Directorio de usuarios</a></li>
        </ul>
    </div>

    <div class="card-panel">
        <h2>Ejercicio #6: Gastos (CRUDL + DDD)</h2>
        <p class="muted">Atributos oficiales: <code>fecha</code>, <code>valorTotalSinIVA</code>, <code>ivaTotal</code>, <code>valorTotalConIVA</code>, <code>nombreUsuario</code>, <code>lugar</code> y <code>descripcion</code>.</p>
        <ul class="feature-list">
            <li><strong>Crear (Command):</strong> <a href="?route=gastos.create">Registrar nuevo gasto</a></li>
            <li><strong>Listar / Filtrar / Editar / Eliminar:</strong> <a href="?route=gastos.index"> Libro contable de gastos</a></li>
        </ul>
    </div>

    <div class="card-panel">
        <h2>Centro de Reportes Analíticos (CQRS)</h2>
        <p class="muted">Consultas especializadas de solo lectura usando puertos de entrada y caché <em>Cache-Aside</em> sin acoplar vistas con SQL.</p>
        <ul class="feature-list">
            <li><a href="?route=reports">Sumatoria y ranking por Lugar / Establecimiento</a></li>
            <li><a href="?route=reports">Auditoría por Rango de Fechas y Usuarios por Rol</a></li>
        </ul>
    </div>

    <div class="card-panel">
        <h2>API RESTful, Swagger &amp; Seguridad OWASP</h2>
        <p class="muted">Especificación OpenAPI 3.0.3 interactiva y manual pedagógico de arquitectura y defensas antivulneraciones.</p>
        <ul class="feature-list">
            <li><a href="?route=swagger" target="_blank" rel="noopener">Explorador interactivo Swagger UI</a></li>
            <li><a href="?route=openapi" target="_blank" rel="noopener">Contrato JSON OpenAPI 3.0.3</a></li>
            <li><a href="?route=about">Manual interactivo: Hexagonal + DDD + OWASP</a></li>
        </ul>
    </div>
</div>

<?php require __DIR__ . '/layouts/footer.php'; ?>