<?php

declare(strict_types=1);

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — PUERTO DE INTEGRACIÓN REST EXTERNA: TaxIndicatorRestPort
 * ============================================================================
 * CAPA: Aplicación (`Application/Ports/Out`)
 *
 * ¿QUÉ HACE?
 *   Define el contrato saliente para consultar indicadores tributarios vigentes
 *   (tarifas de IVA y UVT referencial) desde un proveedor externo REST.
 * ============================================================================
 */
interface TaxIndicatorRestPort
{
    /**
     * @return array<string, mixed>
     */
    public function fetchCurrentTaxIndicators(): array;
}