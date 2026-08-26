<?php

declare(strict_types=1);

require_once __DIR__ . '/../Ports/Out/EmailSenderPort.php';
require_once __DIR__ . '/../../Domain/Models/EmailDestinationModel.php';
require_once __DIR__ . '/../../Domain/Models/UserModel.php';
require_once __DIR__ . '/../../Domain/Exceptions/EmailSenderException.php';

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — SERVICIO AUXILIAR DE APLICACIÓN: EmailNotificationService
 * ============================================================================
 * CAPA: Aplicación (`Application/Services`)
 * PATRÓN: Servicio de Aplicación desacoplado por Puerto de Salida (`EmailSenderPort`)
 *
 * ¿QUÉ HACE?
 *   Construye el contenido HTML seguro (con escape anti-XSS) para las notificaciones
 *   de alta y actualización de usuarios y delega el envío al puerto `EmailSenderPort`.
 *
 * ¿CÓMO LO HACE?
 *   1. Escapa cada dato dinámico con `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
 *   2. Carga plantillas HTML si el directorio fue inyectado desde la raíz de composición
 *      (evitando acoplar `Application` con rutas fijas de presentación).
 *   3. Captura `EmailSenderException` registrando auditoría sin abortar la transacción
 *      principal cuando `$strictMode` es `false`.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Corrige el acoplamiento hacia rutas físicas externas y garantiza resiliencia en
 *   entornos locales o Cloud donde el transporte SMTP pueda estar restringido.
 * ============================================================================
 */
final class EmailNotificationService
{
    private EmailSenderPort $emailSenderPort;
    private bool $strictMode;
    private string $templatesDir;

    public function __construct(
        EmailSenderPort $emailSenderPort,
        bool $strictMode = false,
        string $templatesDir = ''
    ) {
        $this->emailSenderPort = $emailSenderPort;
        $this->strictMode = $strictMode;
        $this->templatesDir = rtrim($templatesDir, "/\\");
    }

    /**
     * Notifica al usuario recién registrado sus credenciales iniciales de acceso.
     */
    public function notifyUserCreated(UserModel $user, string $plainPassword): void
    {
        try {
            $subject = 'Tu cuenta ha sido creada — CRUD Usuarios y Gastos';
            $body = $this->buildUserCreatedBody($user, $plainPassword);

            $destination = new EmailDestinationModel(
                $user->email()->value(),
                $user->name()->value(),
                $subject,
                $body
            );

            $this->emailSenderPort->send($destination);
        } catch (EmailSenderException $e) {
            error_log('[EmailNotificationService] ' . $e->getMessage());
            if ($this->strictMode) {
                throw $e;
            }
        }
    }

    /**
     * Notifica al usuario que los datos de su perfil fueron actualizados.
     */
    public function notifyUserUpdated(UserModel $user): void
    {
        try {
            $subject = 'Tu cuenta ha sido actualizada — CRUD Usuarios y Gastos';
            $body = $this->buildUserUpdatedBody($user);

            $destination = new EmailDestinationModel(
                $user->email()->value(),
                $user->name()->value(),
                $subject,
                $body
            );

            $this->emailSenderPort->send($destination);
        } catch (EmailSenderException $e) {
            error_log('[EmailNotificationService] ' . $e->getMessage());
            if ($this->strictMode) {
                throw $e;
            }
        }
    }

    /**
     * Renderiza la plantilla de bienvenida aplicando codificación HTML segura (Anti-XSS).
     */
    private function buildUserCreatedBody(UserModel $user, string $plainPassword): string
    {
        $safeName = htmlspecialchars($user->name()->value(), ENT_QUOTES, 'UTF-8');
        $safeEmail = htmlspecialchars($user->email()->value(), ENT_QUOTES, 'UTF-8');
        $safePass = htmlspecialchars($plainPassword, ENT_QUOTES, 'UTF-8');
        $safeRole = htmlspecialchars($user->role(), ENT_QUOTES, 'UTF-8');

        $templatePath = $this->resolveTemplatePath('user-created.html');
        if ($templatePath !== '' && is_file($templatePath)) {
            $html = (string) file_get_contents($templatePath);

            return str_replace(
                ['[[nombre]]', '[[email]]', '[[pass]]', '[[rol]]'],
                [$safeName, $safeEmail, $safePass, $safeRole],
                $html
            );
        }

        return "<h2>Cuenta creada</h2><p>Hola {$safeName} ({$safeEmail}), tu rol asignado es {$safeRole}.</p>";
    }

    /**
     * Renderiza la plantilla de actualización de cuenta aplicando codificación HTML segura.
     */
    private function buildUserUpdatedBody(UserModel $user): string
    {
        $safeName = htmlspecialchars($user->name()->value(), ENT_QUOTES, 'UTF-8');
        $safeEmail = htmlspecialchars($user->email()->value(), ENT_QUOTES, 'UTF-8');
        $safeRole = htmlspecialchars($user->role(), ENT_QUOTES, 'UTF-8');

        $templatePath = $this->resolveTemplatePath('user-updated.html');
        if ($templatePath !== '' && is_file($templatePath)) {
            $html = (string) file_get_contents($templatePath);

            return str_replace(
                ['[[nombre]]', '[[email]]', '[[rol]]'],
                [$safeName, $safeEmail, $safeRole],
                $html
            );
        }

        return "<h2>Cuenta actualizada</h2><p>Hola {$safeName} ({$safeEmail}), tu rol actual es {$safeRole}.</p>";
    }

    private function resolveTemplatePath(string $filename): string
    {
        if ($this->templatesDir === '') {
            return '';
        }

        return $this->templatesDir . DIRECTORY_SEPARATOR . $filename;
    }
}