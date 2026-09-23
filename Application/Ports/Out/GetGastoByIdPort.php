<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../Domain/Models/GastoModel.php';
require_once __DIR__ . '/../../../Domain/ValueObjects/GastoId.php';

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — PUERTO DE SALIDA GRANULAR: GetGastoByIdPort
 * ============================================================================
 * CAPA: Aplicación (`Application/Ports/Out`)
 *
 * ¿QUÉ HACE?
 *   Contrato de consulta puntual por `GastoId`. Retorna `?GastoModel` (`null` si
 *   no existe) para que el Caso de Uso decida si lanza `GastoNotFoundException`.
 * ============================================================================
 */
interface GetGastoByIdPort
{
    public function getById(GastoId $gastoId): ?GastoModel;
}