<?php

declare(strict_types=1);

require_once __DIR__ . '/../Ports/In/GetGastoByIdUseCase.php';
require_once __DIR__ . '/../Ports/Out/GetGastoByIdPort.php';
require_once __DIR__ . '/Mappers/GastoApplicationMapper.php';
require_once __DIR__ . '/../../Domain/Exceptions/GastoNotFoundException.php';

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — SERVICIO DE APLICACIÓN QUERY: GetGastoByIdService
 * ============================================================================
 * CAPA: Aplicación (`Application/Services`)
 * PATRÓN: CQRS Read Handler Puntual
 *
 * ¿QUÉ HACE?
 *   Consulta un gasto por su `GastoId` y lanza `GastoNotFoundException` si no existe.
 * ============================================================================
 */
final class GetGastoByIdService implements GetGastoByIdUseCase
{
    private GetGastoByIdPort $getGastoByIdPort;

    public function __construct(GetGastoByIdPort $getGastoByIdPort)
    {
        $this->getGastoByIdPort = $getGastoByIdPort;
    }

    public function execute(GetGastoByIdQuery $query): GastoModel
    {
        $gastoId = GastoApplicationMapper::fromGetByIdQueryToGastoId($query);
        $gasto = $this->getGastoByIdPort->getById($gastoId);

        if ($gasto === null) {
            throw GastoNotFoundException::becauseIdWasNotFound($gastoId->value());
        }

        return $gasto;
    }
}