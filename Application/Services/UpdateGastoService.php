<?php

declare(strict_types=1);

require_once __DIR__ . '/../Ports/In/UpdateGastoUseCase.php';
require_once __DIR__ . '/../Ports/Out/UpdateGastoPort.php';
require_once __DIR__ . '/../Ports/Out/GetGastoByIdPort.php';
require_once __DIR__ . '/../Ports/Out/CachePort.php';
require_once __DIR__ . '/../Ports/Out/DomainEventPublisherPort.php';
require_once __DIR__ . '/Mappers/GastoApplicationMapper.php';
require_once __DIR__ . '/../../Domain/Exceptions/GastoNotFoundException.php';
require_once __DIR__ . '/../../Domain/Events/GastoUpdatedDomainEvent.php';
require_once __DIR__ . '/../../Domain/ValueObjects/GastoId.php';

final class UpdateGastoService implements UpdateGastoUseCase
{
    private UpdateGastoPort $updateGastoPort;
    private GetGastoByIdPort $getGastoByIdPort;
    private ?CachePort $cachePort;
    private ?DomainEventPublisherPort $eventPublisherPort;

    public function __construct(
        UpdateGastoPort $updateGastoPort,
        GetGastoByIdPort $getGastoByIdPort,
        ?CachePort $cachePort = null,
        ?DomainEventPublisherPort $eventPublisherPort = null
    ) {
        $this->updateGastoPort = $updateGastoPort;
        $this->getGastoByIdPort = $getGastoByIdPort;
        $this->cachePort = $cachePort;
        $this->eventPublisherPort = $eventPublisherPort;
    }

    public function execute(UpdateGastoCommand $command): GastoModel
    {
        $gastoId = new GastoId($command->getId());
        $existing = $this->getGastoByIdPort->getById($gastoId);

        if ($existing === null) {
            throw GastoNotFoundException::becauseIdWasNotFound($gastoId->value());
        }

        $gastoToUpdate = GastoApplicationMapper::fromUpdateCommandToModel($command);
        $updatedGasto = $this->updateGastoPort->update($gastoToUpdate);

        if ($this->cachePort !== null) {
            $this->cachePort->flushByPrefix('gastos:');
        }

        if ($this->eventPublisherPort !== null) {
            $this->eventPublisherPort->publish(new GastoUpdatedDomainEvent($updatedGasto));
        }

        return $updatedGasto;
    }
}