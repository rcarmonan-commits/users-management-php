<?php


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — EVENTO DE DOMINIO: UserUpdatedDomainEvent
 * ============================================================================
 * CAPA: Dominio (`Domain/Events`)
 * PATRÓN: Domain Event (DDD) — Notificación de Modificación
 * PRINCIPIO SOLID: SRP (Notificar la actualización de perfil de usuario)
 *
 * ¿QUÉ HACE?
 *   Representa el hecho de que los datos de perfil de un Usuario han sido modificados.
 *
 * ¿CÓMO LO HACE?
 *   Transporta los datos actualizados para su publicación en el bus de eventos o cola de persistencia.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Permite a observadores externos o colas reaccionar ante cambios sin acoplarse al servicio de actualización.
 * ============================================================================
 */

require_once __DIR__ . '/EventDomain.php';
require_once __DIR__ . '/../Models/UserModel.php';

class UserUpdatedDomainEvent extends DomainEvent
{
    private $user;

    public function __construct(UserModel $user)
    {
        parent::__construct('user.updated');
        $this->user = $user;
    }

    public function user()
    {
        return $this->user;
    }

    public function payload()
    {
        return array(
            'id' => $this->user->id()->value(),
            'name' => $this->user->name()->value(),
            'email' => $this->user->email()->value(),
            'role' => $this->user->role(),
            'status' => $this->user->status()
        );
    }
}