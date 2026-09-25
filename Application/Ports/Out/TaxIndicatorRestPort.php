<?php

declare(strict_types=1);

interface TaxIndicatorRestPort
{
    /**
     * Obtiene indicadores tributarios vigentes (tarifas de IVA y UVT referencial)
     * desde un proveedor externo REST o configuración segura de respaldo.
     *
     * @return array<string, mixed>
     */
    public function fetchCurrentTaxIndicators(): array;
}