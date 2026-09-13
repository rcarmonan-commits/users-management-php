<?php


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — EVENTO DE DOMINIO: UserCreatedDomainEvent
 * ============================================================================
 * CAPA: Dominio (`Domain/Events`)
 * PATRÓN: Domain Event (DDD) — Notificación Asíncrona de Creación
 * PRINCIPIO SOLID: SRP (Notificar la creación exitosa de un usuario)
 *
 * ¿QUÉ HACE?
 *   Representa el hecho consumado en el negocio de que un nuevo Usuario ha sido registrado satisfactoriamente.
 *
 * ¿CÓMO LO HACE?
 *   Transporta el id, nombre y correo del usuario recién creado como payload inmutable.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Desacopla la persistencia de los efectos secundarios (como el envío de correo de bienvenida o registro en colas).
 * ============================================================================
 */

require_once __DIR__ . '/EventDomain.php';
require_once __DIR__ . '/../Models/UserModel.php';

class UserCreatedDomainEvent extends DomainEvent
{
    private $user;

    public function __construct(UserModel $user)
    {
        parent::__construct('user.created');
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







