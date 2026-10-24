<?php

declare(strict_types=1);

require_once __DIR__ . '/../Ports/In/CreateGastoUseCase.php';
require_once __DIR__ . '/../Ports/Out/SaveGastoPort.php';
require_once __DIR__ . '/../Ports/Out/CachePort.php';
require_once __DIR__ . '/../Ports/Out/DomainEventPublisherPort.php';
require_once __DIR__ . '/Mappers/GastoApplicationMapper.php';
require_once __DIR__ . '/../../Domain/Events/GastoCreatedDomainEvent.php';

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