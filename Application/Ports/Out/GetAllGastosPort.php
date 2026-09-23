<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../Domain/Models/GastoModel.php';

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — PUERTO DE SALIDA CQRS DE CONSULTA: GetAllGastosPort
 * ============================================================================
 * CAPA: Aplicación (`Application/Ports/Out`)
 *
 * ¿QUÉ HACE?
 *   Contrato para listar y filtrar gastos por usuario responsable y rango de fechas
 *   (`fechaDesde` .. `fechaHasta`), alimentando tanto el listado CRUDL como el
 *   Centro de Reportes Analíticos CQRS.
 * ============================================================================
 */
interface GetAllGastosPort
{
    /**
     * @return GastoModel[]
     */
    public function getAll(
        ?string $nombreUsuario = null,
        ?string $fechaDesde = null,
        ?string $fechaHasta = null
    ): array;
}