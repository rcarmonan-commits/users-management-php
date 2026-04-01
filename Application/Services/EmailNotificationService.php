<?php

declare(strict_types=1);

require_once __DIR__ . '/../Ports/Out/EmailSenderPort.php';
require_once __DIR__ . '/../../Domain/Models/EmailDestinationModel.php';
require_once __DIR__ . '/../../Domain/Models/UserModel.php';
require_once __DIR__ . '/../../Domain/Exceptions/EmailSenderException.php';

final class EmailNotificationService
{
    private EmailSenderPort $emailSenderPort;

    public function __construct(EmailSenderPort $emailSenderPort)
    {
        $this->emailSenderPort = $emailSenderPort;
    }

    public function notifyUserCreated(UserModel $user, string $plainPassword): void
    {
        try {
            $subject = 'Tu cuenta ha sido creada — CRUD Usuarios';
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
            throw $e;
        }
    }

    public function notifyUserUpdated(UserModel $user): void
    {
        try {
            $subject = 'Tu cuenta ha sido actualizada — CRUD Usuarios';
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
            throw $e;
        }
    }

    private function buildUserCreatedBody(UserModel $user, string $plainPassword): string
    {
        $templatePath = $this->resolveTemplatePath('user-created.html');

        if (!is_file($templatePath)) {
            throw new EmailSenderException('Plantilla no encontrada: ' . $templatePath);
        }

        $html = (string) file_get_contents($templatePath);

        return str_replace(
            ['[[nombre]]', '[[email]]', '[[pass]]', '[[rol]]'],
            [
                htmlspecialchars($user->name()->value(), ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($user->email()->value(), ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($plainPassword, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($user->role(), ENT_QUOTES, 'UTF-8'),
            ],
            $html
        );
    }

    private function buildUserUpdatedBody(UserModel $user): string
    {
        $templatePath = $this->resolveTemplatePath('user-updated.html');

        if (!is_file($templatePath)) {
            throw new EmailSenderException('Plantilla no encontrada: ' . $templatePath);
        }

        $html = (string) file_get_contents($templatePath);

        return str_replace(
            ['[[nombre]]', '[[email]]', '[[rol]]'],
            [
                htmlspecialchars($user->name()->value(), ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($user->email()->value(), ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($user->role(), ENT_QUOTES, 'UTF-8'),
            ],
            $html
        );
    }

    private function resolveTemplatePath(string $filename): string
    {
        // __DIR__ = Application/Services  →  dirname 2 veces = raíz del proyecto
        return dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR . 'Infrastructure'
            . DIRECTORY_SEPARATOR . 'Entrypoints'
            . DIRECTORY_SEPARATOR . 'Web'
            . DIRECTORY_SEPARATOR . 'Presentation'
            . DIRECTORY_SEPARATOR . 'Views'
            . DIRECTORY_SEPARATOR . 'emails'
            . DIRECTORY_SEPARATOR . $filename;
    }
}
