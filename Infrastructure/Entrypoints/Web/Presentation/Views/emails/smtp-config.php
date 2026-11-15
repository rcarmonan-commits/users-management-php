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

    <details open class="webhook-guide-card" style="margin-bottom: 24px; padding: 20px 22px; border: 1px solid #334155 !important; border-radius: 12px; background: #090e1a !important; color: #f1f5f9 !important; color-scheme: dark;">
        <summary style="cursor: pointer; font-weight: 700; color: #38bdf8 !important; font-size: 1.05rem; margin-bottom: 14px; user-select: none;">
            Guía de Configuración Rápida (100% gratis con tu propio Gmail): Pasarela Webhook HTTPS 443
        </summary>
        <div style="color: #f1f5f9 !important; font-size: 0.93rem; line-height: 1.6;">
            <p style="color: #cbd5e1 !important; margin: 0 0 14px 0;">
                Sigue estos 5 sencillos pasos para conectar tu cuenta de Gmail a través de Google Apps Script. Esta solución utiliza HTTPS estándar por el puerto 443 (que <strong>nunca es bloqueado por Render Free</strong>):
            </p>

            <div class="guide-steps">
                <div class="guide-step">
                    <span class="step-number">1</span>
                    <div class="guide-step-body" style="color: #f1f5f9 !important;">
                        Ingresa a <a href="https://script.google.com/home/start" target="_blank" rel="noopener noreferrer" style="color: #38bdf8 !important; font-weight: 600; text-decoration: underline;">script.google.com</a> con tu cuenta de Google y haz clic en <strong>Nuevo proyecto</strong>.
                    </div>
                </div>

                <div class="guide-step">
                    <span class="step-number">2</span>
                    <div class="guide-step-body" style="color: #f1f5f9 !important;">
                        En el editor de código, borra todo lo que esté en <code style="background: #020617 !important; color: #38bdf8 !important; border: 1px solid #334155; padding: 2px 7px; border-radius: 4px;">Código.gs</code> y pega el siguiente script listo para producción:
                    </div>
                </div>
            </div>

            <div class="code-container-header" style="background: #0b1220 !important; border: 1px solid #334155 !important; border-bottom: none !important;">
                <span style="color: #94a3b8 !important; font-weight: 600;">Código.gs — Google Apps Script (Email Relay HTTPS)</span>
                <button type="button" class="copy-script-btn" onclick="copiarCodigoAppsScript(this)">📋 Copiar Código</button>
            </div>
            <pre id="codigo-apps-script" class="guide-pre" style="background: #020617 !important; color: #f8fafc !important; border: 1px solid #334155 !important;"><?= htmlspecialchars(trim(<<<'GAS_SCRIPT'
/**
 * Relay Transaccional de Correo Seguro por HTTPS 443
 * Proyecto: Control de Usuarios y Gastos (Render Free -> Gmail)
 * 
 * Permite a la aplicación en Render Free entregar correos reales
 * a cualquier destinatario a través de tu cuenta de Gmail sin costo.
 */

function doPost(e) {
  try {
    // Si se presiona el botón 'Ejecutar' manualmente en el editor sin cuerpo HTTP
    if (!e || (!e.postData && !e.parameter)) {
      return ejecutarPruebaLocal();
    }

    var token = "";
    var to = "";
    var toName = "";
    var subject = "";
    var html = "";
    var fromName = "Control de Usuarios y Gastos";

    // 1. Intentar leer payload JSON (método estándar de la aplicación)
    if (e.postData && e.postData.contents) {
      try {
        var d = JSON.parse(e.postData.contents);
        token = d.token || "";
        to = d.to || "";
        toName = d.toName || "";
        subject = d.subject || "";
        html = d.html || "";
        fromName = d.fromName || fromName;
      } catch (errJson) {
        // Formato no JSON estricto
      }
    }

    // 2. Soporte fallback para formulario POST x-www-form-urlencoded
    if (!to && e.parameter) {
      token = token || e.parameter.token || "";
      to = e.parameter.to || "";
      toName = e.parameter.toName || "";
      subject = e.parameter.subject || "";
      html = e.parameter.html || "";
      fromName = e.parameter.fromName || fromName;
    }

    // 3. Validar token secreto
    if (token !== "CLAVE_RELAY_2026") {
      return ContentService.createTextOutput(JSON.stringify({
        ok: false,
        error: "Token secreto inválido. Configura 'CLAVE_RELAY_2026' en el panel de la aplicación."
      })).setMimeType(ContentService.MimeType.JSON);
    }

    // 4. Validar destinatario
    if (!to) {
      return ContentService.createTextOutput(JSON.stringify({
        ok: false,
        error: "Falta el destinatario ('to')."
      })).setMimeType(ContentService.MimeType.JSON);
    }

    // 5. Despachar correo a través de GmailApp
    GmailApp.sendEmail(to, subject || "Notificación del Sistema", "", {
      htmlBody: html || "<p>Notificación enviada desde el sistema de Control de Usuarios y Gastos.</p>",
      name: fromName
    });

    return ContentService.createTextOutput(JSON.stringify({
      ok: true,
      message: "Correo entregado exitosamente a " + to,
      timestamp: new Date().toISOString()
    })).setMimeType(ContentService.MimeType.JSON);

  } catch (err) {
    return ContentService.createTextOutput(JSON.stringify({
      ok: false,
      error: "Error interno en Google Apps Script: " + err.toString()
    })).setMimeType(ContentService.MimeType.JSON);
  }
}

/**
 * Responde a peticiones GET (para verificar desde el navegador que el Webhook está activo)
 */
function doGet(e) {
  var miCorreo = "";
  try {
    miCorreo = Session.getActiveUser().getEmail();
  } catch (ignore) {}

  return ContentService.createTextOutput(JSON.stringify({
    ok: true,
    service: "Email Relay HTTPS 443 Activo",
    status: "Online y listo para recibir correos vía POST",
    cuenta_gmail: miCorreo || "Autenticada",
    fecha: new Date().toISOString(),
    instrucciones: "Copia la URL terminada en /exec y configúrala con el token CLAVE_RELAY_2026."
  })).setMimeType(ContentService.MimeType.JSON);
}

/**
 * Función auxiliar para cuando se presiona 'Ejecutar' dentro del editor de Google
 */
function ejecutarPruebaLocal() {
  var miCorreo = Session.getActiveUser().getEmail();
  if (miCorreo) {
    GmailApp.sendEmail(miCorreo, "Prueba de Relay Google Apps Script Exitosa", "", {
      htmlBody: "<h3>¡Felicitaciones!</h3><p>Tu pasarela de correo Google Apps Script está funcionando al 100%.</p><p>Ya puedes copiar la URL de implementación y pegarla en tu aplicación de Gastos.</p>",
      name: "Prueba Relay Apps Script"
    });
    Logger.log("Correo de prueba enviado exitosamente a: " + miCorreo);
  }
  return ContentService.createTextOutput(JSON.stringify({
    ok: true,
    mensaje: "Prueba local ejecutada exitosamente. Correo enviado a " + miCorreo
  })).setMimeType(ContentService.MimeType.JSON);
}
GAS_SCRIPT), ENT_QUOTES, 'UTF-8') ?></pre>

            <div class="guide-steps">
                <div class="guide-step">
                    <span class="step-number">3</span>
                    <div class="guide-step-body" style="color: #f1f5f9 !important;">
                        Haz clic en el botón azul superior <strong>Implementar &rarr; Nueva implementación</strong>. En el engranaje (<em>Seleccionar tipo</em>), elige <strong>Aplicación web</strong> y configura exactamente:
                        <ul style="margin: 6px 0 6px 20px; color: #cbd5e1 !important;">
                            <li style="margin-bottom: 4px;"><strong>Ejecutar como:</strong> <em>Yo (&lt;tu-cuenta@gmail.com&gt;)</em></li>
                            <li style="margin-bottom: 4px;"><strong>Quién tiene acceso:</strong> <strong style="color: #38bdf8 !important;">Cualquier persona (Anyone)</strong> &mdash; <em>Imprescindible para que Render pueda invocarlo</em>.</li>
                        </ul>
                    </div>
                </div>

                <div class="guide-step">
                    <span class="step-number">4</span>
                    <div class="guide-step-body" style="color: #f1f5f9 !important;">
                        Haz clic en <strong>Implementar</strong>. Google solicitará autorizar permisos de Gmail. Si muestra la pantalla de aviso <em>"Google no ha verificado esta aplicación"</em>, haz clic en <strong style="color: #fbbf24 !important;">Configuración avanzada</strong> &rarr; <strong style="color: #fbbf24 !important;">Ir a Proyecto (no seguro)</strong> &rarr; <strong style="color: #34d399 !important;">Permitir</strong>.
                    </div>
                </div>

                <div class="guide-step">
                    <span class="step-number">5</span>
                    <div class="guide-step-body" style="color: #f1f5f9 !important;">
                        <strong>Copia la URL de la aplicación web</strong> (termina en <code style="background: #020617 !important; color: #38bdf8 !important; border: 1px solid #334155; padding: 2px 7px; border-radius: 4px;">/exec</code>). Pégala abajo en <em>Opción A &mdash; Webhook HTTPS 443</em>, escribe el token secreto <code style="background: #020617 !important; color: #38bdf8 !important; border: 1px solid #334155; padding: 2px 7px; border-radius: 4px;">CLAVE_RELAY_2026</code> y haz clic en <strong>Guardar Pasarela HTTPS 443</strong>.
                    </div>
                </div>
            </div>

            <div class="guide-note-box" style="margin-top: 16px; padding: 14px 18px; background: rgba(14, 165, 233, 0.12) !important; border-left: 4px solid #38bdf8 !important; border-radius: 8px; color: #e0f2fe !important;">
                <strong style="color: #7dd3fc !important;">Comprobación instantánea:</strong>
                Puedes abrir la URL terminada en <code style="background: #020617; color: #38bdf8; padding: 1px 5px; border-radius: 3px;">/exec</code> directamente en una pestaña de tu navegador (Chrome/Edge). Deberá responder un JSON confirmando <code style="background: #020617; color: #34d399; padding: 1px 5px; border-radius: 3px;">{"ok":true,"service":"Email Relay HTTPS 443 Activo"}</code>. Luego, haz una prueba real desde el formulario inferior <em>Diagnóstico y Envío de Prueba en Vivo</em>.
            </div>
        </div>
    </details>

    <script>
    function copiarCodigoAppsScript(btn) {
        var el = document.getElementById('codigo-apps-script');
        if (!el) return;
        var text = el.innerText || el.textContent;
        navigator.clipboard.writeText(text).then(function() {
            var orig = btn.innerText;
            btn.innerText = '✅ ¡Copiado!';
            btn.style.background = '#065f46';
            btn.style.color = '#34d399';
            setTimeout(function() {
                btn.innerText = orig;
                btn.style.background = '';
                btn.style.color = '';
            }, 3000);
        }).catch(function() {
            alert('Por favor selecciona y copia el texto del código manualmente.');
        });
    }
    </script>

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