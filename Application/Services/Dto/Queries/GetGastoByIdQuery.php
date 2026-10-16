<?php

declare(strict_types=1);

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — OBJETO QUERY CQRS: GetGastoByIdQuery
 * ============================================================================
 * CAPA: Aplicación (`Application/Services/Dto/Queries`)
 * PATRÓN: CQRS — Consulta Puntual por Identidad
 * ============================================================================
 */
final class GetGastoByIdQuery
{
    private string $id;

    public function __construct(string $id)
    {
        $this->id = trim($id);
    }

    public function getId(): string
    {
        return $this->id;
    }
}