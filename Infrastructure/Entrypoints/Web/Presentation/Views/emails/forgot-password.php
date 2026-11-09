<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperación de contraseña</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #0b1120; color: #e2e8f0; margin: 0; padding: 24px; }
        .container { max-width: 580px; margin: 20px auto; background-color: #0f172a; border: 1px solid #1e293b; border-radius: 12px; padding: 36px 32px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5); }
        .badge { display: inline-block; padding: 4px 10px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; border-radius: 9999px; background-color: rgba(14, 165, 233, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); margin-bottom: 14px; }
        h1 { color: #f8fafc; font-size: 22px; font-weight: 700; margin: 0 0 16px 0; }
        p { color: #94a3b8; font-size: 14.5px; line-height: 1.6; margin: 12px 0; }
        .code-box { background-color: #020617; border: 1px dashed #38bdf8; border-radius: 8px; padding: 16px 20px; text-align: center; margin: 22px 0; }
        .code-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.1em; color: #64748b; margin-bottom: 6px; }
        .code-val { font-family: 'Courier New', Courier, monospace; font-size: 26px; font-weight: 700; color: #38bdf8; letter-spacing: 4px; }
        .btn-wrapper { text-align: center; margin: 28px 0; }
        .btn-primary { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff !important; text-decoration: none; font-size: 15px; font-weight: 600; padding: 14px 28px; border-radius: 8px; display: inline-block; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.35); }
        .alert-box { background-color: rgba(245, 158, 11, 0.1); border-left: 4px solid #f59e0b; padding: 12px 16px; border-radius: 0 6px 6px 0; margin: 20px 0; font-size: 13.5px; color: #fbbf24; }
        .footer { font-size: 12px; color: #475569; margin-top: 32px; border-top: 1px solid #1e293b; padding-top: 16px; text-align: center; line-height: 1.5; }
        .url-box { font-size: 11.5px; color: #64748b; word-break: break-all; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <span class="badge">Recuperación de Cuenta &middot; Ejercicio #6</span>
        <h1>Hola, <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></h1>

        <p>
            Recibimos una solicitud para restablecer la contraseña asociada al correo
            <strong style="color: #f1f5f9;"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></strong>.
        </p>

        <div class="code-box">
            <div class="code-label">Tu Contraseña Temporal de Acceso</div>
            <div class="code-val"><?= htmlspecialchars($tempPassword, ENT_QUOTES, 'UTF-8') ?></div>
        </div>

        <div class="alert-box">
            <strong>Instrucción de Seguridad:</strong> Al ingresar con esta clave temporal, el sistema te solicitará
            <strong>automáticamente definir tu nueva contraseña definitiva</strong> para continuar navegando.
        </div>

        <?php if (!empty($directLoginUrl)): ?>
            <div class="btn-wrapper">
                <a href="<?= htmlspecialchars($directLoginUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn-primary" target="_blank" rel="noopener noreferrer">
                    Ingresar Directamente y Definir Nueva Clave &rarr;
                </a>
            </div>
            <p class="url-box">
                Si el botón no abre automáticamente, copia y pega este enlace directo en tu navegador:<br>
                <a href="<?= htmlspecialchars($directLoginUrl, ENT_QUOTES, 'UTF-8') ?>" style="color: #38bdf8; text-decoration: underline;"><?= htmlspecialchars($directLoginUrl, ENT_QUOTES, 'UTF-8') ?></a>
            </p>
        <?php endif; ?>

        <div class="footer">
            Sistema Empresarial de Usuarios y Control de Gastos (#6) &middot; Arquitectura Hexagonal PHP 8.3<br>
            Este correo es transaccional y confidencial. Si tú no realizaste esta solicitud, puedes ignorar este mensaje.
        </div>
    </div>
</body>
</html>