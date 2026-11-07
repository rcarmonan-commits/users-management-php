<?php require __DIR__ . '/../layouts/header.php'; ?>
<?php require __DIR__ . '/../layouts/menu.php'; ?>

<h1>Configuración Dinámica de Servidor de Correo (SMTP)</h1>

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

<p>
    Origen activo de credenciales:
    <strong><?= ($smtp['source'] ?? 'env') === 'database' ? 'Tabla configuracion_smtp (MySQL en caliente)' : 'Variables de entorno (.env)' ?></strong>.
    Estos parámetros se leen dinámicamente desde la base de datos para despachar correos de creación, actualización y recuperación de contraseñas sin requerir variables estáticas en el servidor Cloud.
</p>

<form method="POST" action="?route=smtp.save">
    <div class="form-group">
        <label for="host">Servidor SMTP (Host)</label><br>
        <input type="text" id="host" name="host" required
            placeholder="ej: smtp-app-gastos-ejercicio6.alwaysdata.net"
            value="<?= htmlspecialchars((string) ($smtp['host'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div class="form-group">
        <label for="port">Puerto SMTP</label><br>
        <input type="number" id="port" name="port" min="1" max="65535" required
            value="<?= htmlspecialchars((string) ($smtp['port'] ?? 587), ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div class="form-group">
        <label for="username">Usuario / Correo autenticado</label><br>
        <input type="email" id="username" name="username" required
            value="<?= htmlspecialchars((string) ($smtp['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div class="form-group">
        <label for="password">Contraseña SMTP <small>(dejar en blanco para conservar la actual)</small></label><br>
        <input type="password" id="password" name="password" value="">
    </div>

    <div class="form-group">
        <label for="remitente">Nombre o Correo del Remitente</label><br>
        <input type="text" id="remitente" name="remitente"
            placeholder="ej: Administración del Sistema"
            value="<?= htmlspecialchars((string) ($smtp['remitente'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <button type="submit" class="btn btn-primary">Guardar Configuración SMTP</button>
    &nbsp;
    <a class="btn" href="?route=home">Cancelar</a>
</form>

<?php require __DIR__ . '/../layouts/footer.php'; ?>