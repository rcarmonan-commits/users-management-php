<?php require __DIR__ . '/layouts/header.php'; ?>
<?php require __DIR__ . '/layouts/menu.php'; ?>

<h1>Panel Principal — Arquitectura Hexagonal, DDD y CQRS</h1>

<p>Aplicación construida desde el dominio hacia afuera gestionando la entidad común <strong>Usuario</strong> y la entidad asignada <strong>#6 Gasto</strong>.</p>

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

<div class="dashboard-grid">
    <div class="card-panel">
        <h2>Entidad Común: Usuarios (CRUDL)</h2>
        <ul>
            <li><strong>C:</strong> <a href="?route=users.create">Registrar usuario</a></li>
            <li><strong>L / R / U / D:</strong> <a href="?route=users.index">Listar, consultar, editar y eliminar usuarios</a></li>
        </ul>
    </div>

    <div class="card-panel">
        <h2>Ejercicio #6: Gastos (CRUDL)</h2>
        <p><small>Atributos: <code>fecha</code>, <code>valorTotalSinIVA</code>, <code>ivaTotal</code>, <code>valorTotalConIVA</code>, <code>nombreUsuario</code>, <code>lugar</code>, <code>descripcion</code>.</small></p>
        <ul>
            <li><strong>C:</strong> <a href="?route=gastos.create">Registrar nuevo gasto</a></li>
            <li><strong>L / R / U / D:</strong> <a href="?route=gastos.index">Listar, filtrar, consultar, editar y eliminar gastos</a></li>
        </ul>
    </div>

    <div class="card-panel">
        <h2>API RESTful &amp; Documentación OpenAPI</h2>
        <ul>
            <li><a href="?route=swagger" target="_blank">Explorador interactivo Swagger UI</a></li>
            <li><a href="?route=openapi" target="_blank">Especificación JSON OpenAPI 3.0.3</a></li>
        </ul>
    </div>
</div>

<?php require __DIR__ . '/layouts/footer.php'; ?>