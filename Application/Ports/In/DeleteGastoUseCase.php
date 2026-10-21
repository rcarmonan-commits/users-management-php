<?php

declare(strict_types=1);

require_once __DIR__ . '/../../Services/Dto/Commands/DeleteGastoCommand.php';

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — PUERTO DE ENTRADA (DRIVING PORT): DeleteGastoUseCase
 * ============================================================================
 * CAPA: Aplicación (`Application/Ports/In`)
 * PATRÓN: Caso de Uso de Comando CQRS (D de CRUDL)
 * ============================================================================
 */
interface DeleteGastoUseCase
{
    public function execute(DeleteGastoCommand $command): void;
}