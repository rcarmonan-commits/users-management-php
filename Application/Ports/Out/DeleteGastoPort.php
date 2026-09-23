<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../Domain/ValueObjects/GastoId.php';

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — PUERTO DE SALIDA GRANULAR: DeleteGastoPort
 * ============================================================================
 * CAPA: Aplicación (`Application/Ports/Out`)
 *
 * ¿QUÉ HACE?
 *   Contrato de eliminación puntual de un gasto identificado por `GastoId`.
 * ============================================================================
 */
interface DeleteGastoPort
{
    public function delete(GastoId $gastoId): void;
}