<?php
/**
 * Configuración Dinámica de Servidor SMTP — `Views/emails/smtp-config.php`
 * Permite al Administrador actualizar en caliente la tabla `configuracion_smtp`
 * compartida en MySQL sin exponer ni sobrescribir la contraseña actual si el campo queda vacío.
 */
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/menu.php';
?>

<div class="page-header">
    <div>
        <h1>Configuración Dinámica de Servidor SMTP</h1>
        <p class="muted">
            Origen activo:
            <strong><?= ($smtp['source'] ?? 'env') === 'database' ? 'Tabla configuracion_smtp (MySQL en caliente)' : 'Variables de entorno (.env)' ?></strong>
        </p>
    </div>
    <a class="btn btn-secondary" href="?route=emails.outbox">Ver Bandeja Outbox</a>
</div>

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

<div class="card-panel form-card">
    <form method="POST" action="?route=smtp.save">
        <?= SecurityGuard::csrfField() ?>

        <div class="form-grid-2">
            <div class="form-group">
                <label for="host">Servidor SMTP (Host)</label>
                <input type="text" id="host" name="host" required
                    placeholder="ej: smtp-app-gastos-ejercicio6.alwaysdata.net"
                    value="<?= htmlspecialchars((string) ($smtp['host'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label for="port">Puerto TCP (587 STARTTLS / 465 SSL)</label>
                <input type="number" id="port" name="port" min="1" max="65535" required
                    value="<?= htmlspecialchars((string) ($smtp['port'] ?? 587), ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label for="username">Usuario / Correo autenticado</label>
                <input type="email" id="username" name="username" required
                    value="<?= htmlspecialchars((string) ($smtp['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label for="password">Contraseña SMTP <small class="muted">(en blanco conserva la actual)</small></label>
                <input type="password" id="password" name="password" value="" autocomplete="new-password">
            </div>
        </div>

        <div class="form-group">
            <label for="remitente">Nombre o Correo Visible del Remitente</label>
            <input type="text" id="remitente" name="remitente"
                placeholder="ej: Administración Control de Gastos"
                value="<?= htmlspecialchars((string) ($smtp['remitente'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar Configuración SMTP (587 / 465)</button>
            <a class="btn btn-secondary" href="?route=home">Cancelar</a>
        </div>
    </form>
</div>

<div class="card-panel form-card" style="margin-top: 22px;">
    <h2>Pasarela Cloud HTTPS 443 (Render Free &rarr; Gmail / Brevo / Resend)</h2>
    <p class="muted" style="margin-bottom: 14px;">
        El plan gratuito de <strong>Render Free</strong> bloquea por firewall las conexiones TCP salientes hacia los puertos SMTP
        <code>25</code>, <code>465</code> y <code>587</code>, pero <strong>nunca bloquea HTTPS por puerto <code>443</code></strong>.
        Configura aquí cualquiera de las opciones HTTPS (guardadas en la tabla independiente <code>hex_cloud_email_relay</code>
        sin alterar <code>configuracion_smtp</code>) para entregar correos reales al buzón de Gmail desde Render Free sin reiniciar el contenedor:
        estado actual
        <strong><?= !empty($relay['has_active_relay']) ? 'ACTIVA (HTTPS 443)' : 'NO CONFIGURADA (usando SMTP directo / Outbox)' ?></strong>.
    </p>

    <details open style="margin-bottom: 20px; padding: 16px 18px; border: 1px solid #334155; border-radius: 10px; background: #0f172a; color: #cbd5e1;">
        <summary style="cursor: pointer; font-weight: 700; color: #38bdf8; font-size: 1rem; margin-bottom: 12px; user-select: none;">
            Guía rápida (100% gratis con tu propio Gmail): Crear Webhook Google Apps Script por HTTPS 443
        </summary>
        <div style="color: #cbd5e1; font-size: 0.92rem; line-height: 1.6;">
            <ol style="margin: 0 0 12px 20px; padding: 0; color: #e2e8f0;">
                <li style="margin-bottom: 8px;">Abre <a href="https://script.google.com/home/start" target="_blank" rel="noopener noreferrer" style="color: #38bdf8; text-decoration: underline; font-weight: 600;">script.google.com</a> con tu cuenta de Google y haz clic en <strong>Nuevo proyecto</strong>.</li>
                <li style="margin-bottom: 8px;">Reemplaza todo el contenido del archivo <code style="background: #1e293b; color: #38bdf8; padding: 2px 7px; border-radius: 4px; border: 1px solid #334155;">Código.gs</code> por este script listo para producción:</li>
            </ol>
            <pre style="background: #020617; color: #f1f5f9; padding: 14px 16px; border-radius: 8px; border: 1px solid #334155; overflow-x: auto; font-size: 0.84rem; line-height: 1.45; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;">function doPost(e) {
  try {
    var raw = (e && e.postData && e.postData.contents) ? e.postData.contents : "{}";
    var d = JSON.parse(raw);
    if (d.token !== "CLAVE_RELAY_2026") {
      return ContentService.createTextOutput(JSON.stringify({ok: false, error: "Token secreto invalido"}))
        .setMimeType(ContentService.MimeType.JSON);
    }
    GmailApp.sendEmail(d.to, d.subject, "", {
      htmlBody: d.html,
      name: d.fromName || "Control de Usuarios y Gastos"
    });
    return ContentService.createTextOutput(JSON.stringify({ok: true, message: "Correo despachado con exito"}))
      .setMimeType(ContentService.MimeType.JSON);
  } catch (err) {
    return ContentService.createTextOutput(JSON.stringify({ok: false, error: err.toString()}))
      .setMimeType(ContentService.MimeType.JSON);
  }
}

function doGet(e) {
  return ContentService.createTextOutput(JSON.stringify({
    ok: true,
    service: "Email Relay HTTPS 443 Activo",
    tip: "El servicio esta en linea. Las solicitudes de envio se reciben via POST."
  })).setMimeType(ContentService.MimeType.JSON);
}

// Opcional: para probar desde el editor de Google sin recibir peticion web
function probarDesdeEditor() {
  var miCorreo = Session.getActiveUser().getEmail();
  GmailApp.sendEmail(miCorreo, "Prueba Relay Apps Script", "Tu Relay de Google Apps Script esta funcionando correctamente.");
  Logger.log("Correo de prueba enviado con exito a: " + miCorreo);
}</pre>
            <ol start="3" style="margin: 12px 0 12px 20px; padding: 0; color: #e2e8f0;">
                <li style="margin-bottom: 8px;">Haz clic en el botón azul superior <strong>Implementar &rarr; Nueva implementación</strong>.</li>
                <li style="margin-bottom: 8px;">En el icono de engranaje (<em>Seleccionar tipo</em>), elige <strong>Aplicación web</strong>.</li>
                <li style="margin-bottom: 8px;">Configura exactamente estos dos campos:
                    <ul style="margin: 6px 0 6px 20px; color: #94a3b8;">
                        <li style="margin-bottom: 4px;"><strong>Ejecutar como:</strong> <em>Yo (&lt;tu-cuenta@gmail.com&gt;)</em></li>
                        <li style="margin-bottom: 4px;"><strong>Quién tiene acceso:</strong> <em>Cualquier persona (Anyone)</em></li>
                    </ul>
                </li>
                <li style="margin-bottom: 8px;">Haz clic en <strong>Implementar</strong>, autoriza los permisos de acceso de Gmail y <strong>copia la URL</strong> que termina en <code style="background: #1e293b; color: #38bdf8; padding: 2px 7px; border-radius: 4px; border: 1px solid #334155;">/exec</code>.</li>
                <li style="margin-bottom: 6px;">Pégala abajo en <em>Opción A — Webhook HTTPS 443</em>, escribe el token secreto <code style="background: #1e293b; color: #38bdf8; padding: 2px 7px; border-radius: 4px; border: 1px solid #334155;">CLAVE_RELAY_2026</code> y haz clic en <strong>Guardar Pasarela HTTPS 443</strong>.</li>
            </ol>
            <div style="margin-top: 14px; padding: 12px 14px; background: rgba(56, 189, 248, 0.08); border-left: 4px solid #38bdf8; border-radius: 6px; font-size: 0.88rem; color: #bae6fd; line-height: 1.5;">
                <strong>Nota técnica:</strong> No presiones el botón "Ejecutar" sobre <code style="background: #1e293b; color: #38bdf8; padding: 1px 5px; border-radius: 3px;">doPost</code> dentro del editor de Google Apps Script. Ese botón local no incluye el cuerpo HTTP y mostrará error en la consola de Google. La prueba real se realiza enviando un correo desde el formulario inferior: <em>Diagnóstico y Envío de Prueba en Vivo</em>.
            </div>
        </div>
    </details>

    <form method="POST" action="?route=smtp.relay.save">
        <?= SecurityGuard::csrfField() ?>

        <div class="form-grid-2">
            <div class="form-group">
                <label for="webhook_url">Opción A — Webhook HTTPS 443 (ej. Google Apps Script URL)</label>
                <input type="url" id="webhook_url" name="webhook_url"
                    placeholder="https://script.google.com/macros/s/.../exec"
                    value="<?= htmlspecialchars((string) ($relay['webhook_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label for="webhook_token">Token secreto del Webhook <small class="muted">(en blanco conserva el actual)</small></label>
                <input type="password" id="webhook_token" name="webhook_token" value=""
                    placeholder="<?= !empty($relay['webhook_token']) ? '•••••••• (configurado)' : 'ej: CLAVE_RELAY_2026' ?>"
                    autocomplete="new-password">
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label for="brevo_api_key">Opción B — Brevo API Key HTTPS 443 <small class="muted">(xkeysib-...)</small></label>
                <input type="password" id="brevo_api_key" name="brevo_api_key" value=""
                    placeholder="<?= !empty($relay['brevo_api_key']) ? '•••••••• (configurada)' : 'xkeysib-...' ?>"
                    autocomplete="new-password">
            </div>

            <div class="form-group">
                <label for="resend_api_key">Opción C — Resend API Key HTTPS 443 <small class="muted">(re_...)</small></label>
                <input type="password" id="resend_api_key" name="resend_api_key" value=""
                    placeholder="<?= !empty($relay['resend_api_key']) ? '•••••••• (configurada)' : 're_...' ?>"
                    autocomplete="new-password">
            </div>
        </div>

        <div class="form-group">
            <label for="https_from_address">Remitente verificado para Brevo / Resend <small class="muted">(opcional; ej. tu correo verificado en Brevo)</small></label>
            <input type="email" id="https_from_address" name="https_from_address"
                placeholder="ej: rcarmonan@unicartagena.edu.co"
                value="<?= htmlspecialchars((string) ($relay['https_from_address'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group" style="margin-top: 8px;">
            <label style="display: inline-flex; align-items: center; gap: 8px; font-weight: normal;">
                <input type="checkbox" name="clear_secrets" value="1">
                Limpiar tokens / API keys HTTPS guardados previamente
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar Pasarela HTTPS 443</button>
        </div>
    </form>
</div>

<div class="card-panel form-card" style="margin-top: 22px;">
    <h2>Diagnóstico y Envío de Prueba en Vivo</h2>
    <p class="muted">Envía un correo de verificación y comprueba qué canal utilizó el servidor (<code>https_api</code>, <code>smtp</code> u <code>outbox</code>).</p>
    <form method="POST" action="?route=smtp.test" class="filter-bar">
        <?= SecurityGuard::csrfField() ?>
        <input type="email" name="test_email" required placeholder="ej: namtlakh@gmail.com" style="min-width: 280px;">
        <button type="submit" class="btn btn-primary">Enviar Correo de Prueba</button>
    </form>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>