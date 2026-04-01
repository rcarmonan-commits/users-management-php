<?php

declare(strict_types=1);

final class EmailDestinationModel
{
    private string $destinationEmail;
    private string $destinationName;
    private string $subject;
    private string $body;

    public function __construct(
        string $destinationEmail,
        string $destinationName,
        string $subject,
        string $body
    ) {
        if (trim($destinationEmail) === '') {
            throw new \InvalidArgumentException('El email del destinatario es requerido para enviar la notificación.');
        }
        if (trim($destinationName) === '') {
            throw new \InvalidArgumentException('El nombre del destinatario es requerido para enviar la notificación.');
        }
        if (trim($subject) === '') {
            throw new \InvalidArgumentException('El asunto es requerido para enviar la notificación.');
        }
        if (trim($body) === '') {
            throw new \InvalidArgumentException('El cuerpo del mensaje es requerido para enviar la notificación.');
        }

        $this->destinationEmail = $destinationEmail;
        $this->destinationName = $destinationName;
        $this->subject = $subject;
        $this->body = $body;
    }

    public function getDestinationEmail(): string
    {
        return $this->destinationEmail;
    }
    public function getDestinationName(): string
    {
        return $this->destinationName;
    }
    public function getSubject(): string
    {
        return $this->subject;
    }
    public function getBody(): string
    {
        return $this->body;
    }
}
