<?php

declare(strict_types=1);

require_once __DIR__ . '/../Ports/In/CreateGastoUseCase.php';
require_once __DIR__ . '/../Ports/Out/SaveGastoPort.php';
require_once __DIR__ . '/../Ports/Out/CachePort.php';
require_once __DIR__ . '/../Ports/Out/DomainEventPublisherPort.php';
require_once __DIR__ . '/Mappers/GastoApplicationMapper.php';
require_once __DIR__ . '/../../Domain/Events/GastoCreatedDomainEvent.php';

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — SERVICIO DE APLICACIÓN COMMAND: CreateGastoService
 * ============================================================================
 * CAPA: Aplicación (`Application/Services`)
 * PATRÓN: Orquestador de Caso de Uso CQRS + Invalidación de Caché + Eventos DDD
 *
 * ¿CÓMO FUNCIONA PASO A PASO?
 *   1. Convierte `CreateGastoCommand` en `GastoModel` mediante `GastoApplicationMapper`
 *      (ejecutando todas las validaciones e invariantes de IVA del Dominio).
 *   2. Persiste el agregado a través del puerto de salida `SaveGastoPort`.
 *   3. Invalida las consultas cacheadas bajo el prefijo `gastos:` (`CachePort`).
 *   4. Publica `GastoCreatedDomainEvent` en la cola de dominio (`DomainEventPublisherPort`).
 * ============================================================================
 */
final class CreateGastoService implements CreateGastoUseCase
{
    private SaveGastoPort $saveGastoPort;
    private ?CachePort $cachePort;
    private ?DomainEventPublisherPort $eventPublisherPort;

    public function __construct(
        SaveGastoPort $saveGastoPort,
        ?CachePort $cachePort = null,
        ?DomainEventPublisherPort $eventPublisherPort = null
    ) {
        $this->saveGastoPort = $saveGastoPort;
        $this->cachePort = $cachePort;
        $this->eventPublisherPort = $eventPublisherPort;
    }

    public function execute(CreateGastoCommand $command): GastoModel
    {
        $gasto = GastoApplicationMapper::fromCreateCommandToModel($command);
        $savedGasto = $this->saveGastoPort->save($gasto);

        if ($this->cachePort !== null) {
            $this->cachePort->flushByPrefix('gastos:');
        }

        if ($this->eventPublisherPort !== null) {
            $this->eventPublisherPort->publish(new GastoCreatedDomainEvent($savedGasto));
        }

        return $savedGasto;
    }
}