<?php

declare(strict_types=1);

require_once __DIR__ . '/../Ports/In/DeleteGastoUseCase.php';
require_once __DIR__ . '/../Ports/Out/DeleteGastoPort.php';
require_once __DIR__ . '/../Ports/Out/GetGastoByIdPort.php';
require_once __DIR__ . '/../Ports/Out/CachePort.php';
require_once __DIR__ . '/../Ports/Out/DomainEventPublisherPort.php';
require_once __DIR__ . '/Mappers/GastoApplicationMapper.php';
require_once __DIR__ . '/../../Domain/Exceptions/GastoNotFoundException.php';
require_once __DIR__ . '/../../Domain/Events/GastoDeletedDomainEvent.php';

final class DeleteGastoService implements DeleteGastoUseCase
{
    private DeleteGastoPort $deleteGastoPort;
    private GetGastoByIdPort $getGastoByIdPort;
    private ?CachePort $cachePort;
    private ?DomainEventPublisherPort $eventPublisherPort;

    public function __construct(
        DeleteGastoPort $deleteGastoPort,
        GetGastoByIdPort $getGastoByIdPort,
        ?CachePort $cachePort = null,
        ?DomainEventPublisherPort $eventPublisherPort = null
    ) {
        $this->deleteGastoPort = $deleteGastoPort;
        $this->getGastoByIdPort = $getGastoByIdPort;
        $this->cachePort = $cachePort;
        $this->eventPublisherPort = $eventPublisherPort;
    }

    public function execute(DeleteGastoCommand $command): void
    {
        $gastoId = GastoApplicationMapper::fromDeleteCommandToGastoId($command);
        $existing = $this->getGastoByIdPort->getById($gastoId);

        if ($existing === null) {
            throw GastoNotFoundException::becauseIdWasNotFound($gastoId->value());
        }

        $this->deleteGastoPort->delete($gastoId);

        if ($this->cachePort !== null) {
            $this->cachePort->flushByPrefix('gastos:');
        }

        if ($this->eventPublisherPort !== null) {
            $this->eventPublisherPort->publish(new GastoDeletedDomainEvent($gastoId));
        }
    }
}