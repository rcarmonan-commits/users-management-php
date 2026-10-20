<?php

declare(strict_types=1);

require_once __DIR__ . '/../../Services/Dto/Queries/GetAllGastosQuery.php';
require_once __DIR__ . '/../../../Domain/Models/GastoModel.php';

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — PUERTO DE ENTRADA (DRIVING PORT): GetAllGastosUseCase
 * ============================================================================
 * CAPA: Aplicación (`Application/Ports/In`)
 * PATRÓN: Caso de Uso de Consulta CQRS (L de CRUDL)
 * ============================================================================
 */
interface GetAllGastosUseCase
{
    /**
     * @return GastoModel[]
     */
    public function execute(GetAllGastosQuery $query): array;
}