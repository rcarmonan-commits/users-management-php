<?php

declare(strict_types=1);

require_once __DIR__ . '/../../Services/Dto/Queries/GetGastoByIdQuery.php';
require_once __DIR__ . '/../../../Domain/Models/GastoModel.php';

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — PUERTO DE ENTRADA (DRIVING PORT): GetGastoByIdUseCase
 * ============================================================================
 * CAPA: Aplicación (`Application/Ports/In`)
 * PATRÓN: Caso de Uso de Consulta Puntual CQRS (R de CRUDL)
 * ============================================================================
 */
interface GetGastoByIdUseCase
{
    public function execute(GetGastoByIdQuery $query): GastoModel;
}