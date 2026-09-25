<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../Domain/Events/EventDomain.php';

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — PUERTO DE PUBLICACIÓN DE EVENTOS: DomainEventPublisherPort
 * ============================================================================
 * CAPA: Aplicación (`Application/Ports/Out`)
 *
 * ¿QUÉ HACE?
 *   Desacopla el despacho de Eventos de Dominio (`gasto.created`, `gasto.updated`,
 *   `gasto.deleted`) hacia colas asíncronas o buses de mensajería.
 * ============================================================================
 */
interface DomainEventPublisherPort
{
    public function publish(DomainEvent $event): void;
}