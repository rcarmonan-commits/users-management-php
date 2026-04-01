<?php

declare(strict_types=1);

require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

require_once __DIR__ . '/../../../Application/Ports/Out/EmailSenderPort.php';
require_once __DIR__ . '/../../../Domain/Models/EmailDestinationModel.php';
require_once __DIR__ . '/../../../Domain/Exceptions/EmailSenderException.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

final class EmailSenderService implements EmailSenderPort
{
    private string $host;
    private string $username;
    private string $password;
    private string $fromAddress;
    private string $fromName;
    private string $encryption;
    private int $port;

    public function __construct(
        string $host,
        string $username,
        string $password,
        string $fromAddress,
        string $fromName,
        string $encryption = 'tls',
        int $port = 587
    ) {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->fromAddress = $fromAddress;
        $this->fromName = $fromName;
        $this->encryption = $encryption;
        $this->port = $port;
    }

    public function send(EmailDestinationModel $destination): void
    {
        try {
            $mailer = new PHPMailer(true);

            $mailer->isSMTP();
            $mailer->SMTPDebug = 0;
            $mailer->SMTPAuth = true;
            $mailer->Host = $this->host;
            $mailer->Username = $this->username;
            $mailer->Password = $this->password;
            $mailer->SMTPSecure = $this->encryption;
            $mailer->Port = $this->port;
            $mailer->CharSet = 'UTF-8';

            $mailer->setFrom($this->fromAddress, $this->fromName);
            $mailer->addAddress(
                $destination->getDestinationEmail(),
                $destination->getDestinationName()
            );

            $mailer->isHTML(true);
            $mailer->Subject = $destination->getSubject();
            $mailer->Body = $destination->getBody();

            $mailer->send();

        } catch (PHPMailerException $e) {
            throw new EmailSenderException(
                'No se pudo enviar el correo a ' . $destination->getDestinationEmail()
                . '. Error SMTP: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
