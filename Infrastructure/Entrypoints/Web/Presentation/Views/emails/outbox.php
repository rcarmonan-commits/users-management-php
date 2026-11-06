<?php
/**
 * Bandeja de Auditoría de Correos — `Views/emails/outbox.php`
 * - Acceso restringido estrictamente al rol `ADMIN` (contiene claves temporales de recuperación).
 * - Renderiza el cuerpo HTML dentro de un `<iframe sandbox="">` aislado para impedir XSS.
 */
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/menu.php';
?>

<div class="page-header">
    <div>
        <h1>Bandeja de Salida y Auditoría de Correos (Admin)</h1>
        <p class="muted">
            Registro auditable de despachos de <code>EmailSenderService</code> (SMTP IPv4 587/465, API HTTPS 443 Brevo/Resend o respaldo local).
        </p>
    </div>
    <div class="header-actions">
        <a class="btn btn-secondary" href="?route=smtp.config">Configurar SMTP</a>
        <a class="btn btn-secondary" href="?route=home">&larr; Inicio</a>
    </div>
</div>

<?php if (empty($messages)): ?>
    <div class="card-panel empty-state">
        <p>Aún no se han generado notificaciones de correo en esta instancia.</p>
    </div>
<?php else: ?>
    <?php foreach ($messages as $item): ?>
        <div class="card-panel" style="margin-bottom: 16px;">
            <p style="margin-top: 0;">
                <strong>Destinatario:</strong> <?= htmlspecialchars($item['to_name'] . ' <' . $item['to_email'] . '>', ENT_QUOTES, 'UTF-8') ?> &middot;
                <strong>Asunto:</strong> <?= htmlspecialchars($item['subject'], ENT_QUOTES, 'UTF-8') ?> &middot;
                <strong>Fecha:</strong> <?= htmlspecialchars($item['date'], ENT_QUOTES, 'UTF-8') ?> &middot;
                <span class="badge badge-emerald">Canal: <?= htmlspecialchars($item['channel'], ENT_QUOTES, 'UTF-8') ?></span>
            </p>
            <details>
                <summary style="cursor: pointer; color: #60a5fa; font-weight: 600;">Ver vista previa aislada (Sandbox HTML)</summary>
                <div style="margin-top: 12px;">
                    <iframe
                        sandbox=""
                        srcdoc="<?= htmlspecialchars($item['html'], ENT_QUOTES, 'UTF-8') ?>"
                        style="width: 100%; min-height: 280px; border: 1px solid #334155; border-radius: 8px; background: #ffffff;"
                        title="Vista previa segura de correo"></iframe>
                </div>
            </details>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/../layouts/footer.php'; ?>