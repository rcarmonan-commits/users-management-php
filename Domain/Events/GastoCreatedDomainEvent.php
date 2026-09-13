<?php

declare(strict_types=1);

require_once __DIR__ . '/EventDomain.php';
require_once __DIR__ . '/../Models/GastoModel.php';

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — EVENTO DE DOMINIO: GastoCreatedDomainEvent
 * ============================================================================
 * CAPA: Dominio (`Domain/Events`)
 * PATRÓN: Domain Events (DDD) para auditoría e integración desacoplada por colas
 *
 * ¿QUÉ HACE?
 *   Notifica como hecho inmutable del negocio (`gasto.created`) que un nuevo gasto
 *   válido fue registrado satisfactoriamente.
 * ============================================================================
 */
final class GastoCreatedDomainEvent extends DomainEvent
{
    private GastoModel $gasto;

    public function __construct(GastoModel $gasto)
    {
        parent::__construct('gasto.created');
        $this->gasto = $gasto;
    }

    public function gasto(): GastoModel
    {
        return $this->gasto;
    }

    /**
     * @return array<string, float|string>
     */
    public function payload(): array
    {
        return $this->gasto->toArray();
    }
}