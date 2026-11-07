<?php require __DIR__ . '/../layouts/header.php'; ?>
<?php require __DIR__ . '/../layouts/menu.php'; ?>

<h1>Bandeja de Salida de Correos (Outbox &amp; Auditoría SMTP/HTTPS)</h1>

<p>
    Registro auditable de notificaciones generadas por <code>EmailSenderService</code>
    (creación/edición de usuarios y recuperación de contraseñas). En plataformas Cloud Free con puertos TCP
    <code>25/465/587</code> restringidos (como Render Free), los mensajes se preservan aquí automáticamente o se despachan por HTTPS <code>443</code> si se configura <code>BREVO_API_KEY</code> / <code>RESEND_API_KEY</code>.
</p>

<?php if (empty($messages)): ?>
    <p><em>Aún no se han generado notificaciones de correo en esta instancia.</em></p>
<?php else: ?>
    <?php foreach ($messages as $item): ?>
        <div class="card-panel" style="margin-bottom: 18px;">
            <p style="margin-top: 0;">
                <strong>Para:</strong> <?= htmlspecialchars($item['to_name'] . ' <' . $item['to_email'] . '>', ENT_QUOTES, 'UTF-8') ?> |
                <strong>Asunto:</strong> <?= htmlspecialchars($item['subject'], ENT_QUOTES, 'UTF-8') ?> |
                <strong>Fecha:</strong> <?= htmlspecialchars($item['date'], ENT_QUOTES, 'UTF-8') ?> |
                <strong>Canal:</strong> <code><?= htmlspecialchars($item['channel'], ENT_QUOTES, 'UTF-8') ?></code>
            </p>
            <details>
                <summary style="cursor: pointer; color: #0b5ed7;"><strong>Ver contenido HTML del correo</strong></summary>
                <div style="margin-top: 10px;">
                    <iframe
                        sandbox=""
                        srcdoc="<?= htmlspecialchars($item['html'], ENT_QUOTES, 'UTF-8') ?>"
                        style="width: 100%; min-height: 280px; border: 1px solid #ddd; border-radius: 4px; background: #fff;"
                        title="Vista previa de correo"></iframe>
                </div>
            </details>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<p><a href="?route=home">Volver al inicio</a></p>

<?php require __DIR__ . '/../layouts/footer.php'; ?>