<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../Domain/Models/GastoModel.php';

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — PUERTO DE SALIDA GRANULAR: SaveGastoPort
 * ============================================================================
 * CAPA: Aplicación (`Application/Ports/Out`)
 * PATRÓN: Segregación de Interfaces (SOLID - ISP) + Arquitectura Hexagonal
 *
 * ¿QUÉ HACE?
 *   Expone únicamente la operación de persistencia inicial de un `GastoModel`.
 *
 * ¿POR QUÉ PUERTOS GRANULARES EN VEZ DE UN REPOSITORIO GIGANTE?
 *   Cumple el Principio de Segregación de Interfaces (ISP): `CreateGastoService`
 *   solo conoce `SaveGastoPort` y jamás puede invocar accidentalmente `delete()`.
 * ============================================================================
 */
interface SaveGastoPort
{
    public function save(GastoModel $gasto): GastoModel;
}