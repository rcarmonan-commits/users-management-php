<?php

declare(strict_types=1);

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — OBJETO QUERY CQRS: GetAllGastosQuery
 * ============================================================================
 * CAPA: Aplicación (`Application/Services/Dto/Queries`)
 * PATRÓN: CQRS — Consulta Libre de Efectos Secundarios (Read Side)
 *
 * ¿QUÉ HACE?
 *   Transporta criterios opcionales de filtrado (`nombreUsuario`, `fechaDesde`,
 *   `fechaHasta`) hacia `GetAllGastosUseCase` tanto para el listado CRUDL como para
 *   el Centro de Reportes Analíticos CQRS.
 * ============================================================================
 */
final class GetAllGastosQuery
{
    private ?string $nombreUsuario;
    private ?string $fechaDesde;
    private ?string $fechaHasta;

    public function __construct(
        ?string $nombreUsuario = null,
        ?string $fechaDesde = null,
        ?string $fechaHasta = null
    ) {
        $this->nombreUsuario = ($nombreUsuario !== null && trim($nombreUsuario) !== '')
            ? trim($nombreUsuario)
            : null;
        $this->fechaDesde = ($fechaDesde !== null && trim($fechaDesde) !== '')
            ? trim($fechaDesde)
            : null;
        $this->fechaHasta = ($fechaHasta !== null && trim($fechaHasta) !== '')
            ? trim($fechaHasta)
            : null;
    }

    public function getNombreUsuario(): ?string
    {
        return $this->nombreUsuario;
    }

    public function getFechaDesde(): ?string
    {
        return $this->fechaDesde;
    }

    public function getFechaHasta(): ?string
    {
        return $this->fechaHasta;
    }
}