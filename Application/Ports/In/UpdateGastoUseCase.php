<?php

declare(strict_types=1);

require_once __DIR__ . '/../../Services/Dto/Commands/UpdateGastoCommand.php';
require_once __DIR__ . '/../../../Domain/Models/GastoModel.php';

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — PUERTO DE ENTRADA (DRIVING PORT): UpdateGastoUseCase
 * ============================================================================
 * CAPA: Aplicación (`Application/Ports/In`)
 * PATRÓN: Caso de Uso de Comando CQRS (U de CRUDL)
 * ============================================================================
 */
interface UpdateGastoUseCase
{
    public function execute(UpdateGastoCommand $command): GastoModel;
}