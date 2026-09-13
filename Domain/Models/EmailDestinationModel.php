<?php

declare(strict_types=1);


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — MODELO DE DOMINIO: EmailDestinationModel
 * ============================================================================
 * CAPA: Dominio (`Domain/Models`) — Núcleo de Notificaciones
 * PATRÓN: Domain Entity / Value Carrier
 * PRINCIPIO SOLID: SRP (Encapsular los datos del destinatario de correo)
 *
 * ¿QUÉ HACE?
 *   Modela la tupla de información necesaria para notificar a un usuario por correo electrónico (destinatario, nombre y asunto).
 *
 * ¿CÓMO LO HACE?
 *   Valida la integridad de la dirección de correo y expone getters inmutables para el servicio de notificación.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Evita pasar parámetros sueltos (primitivas) en las capas de aplicación y adaptadores de correo.
 * ============================================================================
 */

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
