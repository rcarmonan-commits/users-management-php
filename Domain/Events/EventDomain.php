<?php


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — CLASE BASE DE EVENTOS DE DOMINIO: EventDomain
 * ============================================================================
 * CAPA: Dominio (`Domain/Events`)
 * PATRÓN: Domain Event Base Pattern (Fowler / Evans)
 * PRINCIPIO SOLID: OCP (Base extensible para cualquier evento del negocio)
 *
 * ¿QUÉ HACE?
 *   Contrato base para todos los eventos de dominio del sistema, registrando timestamp de ocurrencia e identificador único.
 *
 * ¿CÓMO LO HACE?
 *   Asigna fecha y hora en formato ISO 8601 al momento exacto de instanciar el evento.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Permite que la arquitectura registre auditoría, notificaciones y colas de mensajes de forma desacoplada.
 * ============================================================================
 */

abstract class DomainEvent
{
    private $eventName;
    private $occurredOn;

    public function __construct($eventName)
    {
        $this->eventName = $eventName;
        $this->occurredOn = date('Y-m-d H:i:s');
    }

    public function eventName()
    {
        return $this->eventName;
    }

    public function occurredOn()
    {
        return $this->occurredOn;
    }

    abstract public function payload();
}

