<?php


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — EVENTO DE DOMINIO: UserDeletedDomainEvent
 * ============================================================================
 * CAPA: Dominio (`Domain/Events`)
 * PATRÓN: Domain Event (DDD) — Notificación de Baja de Usuario
 * PRINCIPIO SOLID: SRP (Notificar la eliminación de un usuario)
 *
 * ¿QUÉ HACE?
 *   Registra el evento de eliminación de un Usuario en el sistema.
 *
 * ¿CÓMO LO HACE?
 *   Transporta el identificador del usuario eliminado para tareas de purga, auditoría o revocación de sesiones.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Mantiene la trazabilidad y consistencia eventual en sistemas distribuidos.
 * ============================================================================
 */

require_once __DIR__ . '/EventDomain.php';
require_once __DIR__ . '/../ValueObjects/UserId.php';

class UserDeletedDomainEvent extends DomainEvent
{
    private $userId;

    public function __construct(UserId $userId)
    {
        parent::__construct('user.deleted');
        $this->userId = $userId;
    }

    public function userId()
    {
        return $this->userId;
    }

    public function payload()
    {
        return array(
            'id' => $this->userId->value()
        );
    }
}

