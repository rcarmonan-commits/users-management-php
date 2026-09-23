<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../Domain/Models/GastoModel.php';

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — PUERTO DE SALIDA GRANULAR: UpdateGastoPort
 * ============================================================================
 * CAPA: Aplicación (`Application/Ports/Out`)
 *
 * ¿QUÉ HACE?
 *   Contrato de escritura para actualizar un `GastoModel` previamente validado.
 * ============================================================================
 */
interface UpdateGastoPort
{
    public function update(GastoModel $gasto): GastoModel;
}