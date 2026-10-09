<?php

declare(strict_types=1);

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — ENTIDAD DE BASE DE DATOS: GastoEntity
 * ============================================================================
 * CAPA: Infraestructura — Adaptadores (`Infrastructure/Adapters/Persistence/MySQL/Entity`)
 * PATRÓN: Separación Modelo de Dominio vs Entidad Relacional
 *
 * ¿POR QUÉ EXISTE `GastoEntity` SEPARADO DE `GastoModel`?
 *   En Arquitectura Hexagonal pura, las columnas técnicas de base de datos
 *   (`created_at`, `updated_at`, nombres snake_case) pertenecen a Infraestructura
 *   y no deben contaminar la entidad rica de Dominio (`GastoModel`).
 * ============================================================================
 */
final class GastoEntity
{
    private string $id;
    private string $fecha;
    private string $valorTotalSinIva;
    private string $ivaTotal;
    private string $valorTotalConIva;
    private string $nombreUsuario;
    private string $lugar;
    private string $descripcion;
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(
        string $id,
        string $fecha,
        string $valorTotalSinIva,
        string $ivaTotal,
        string $valorTotalConIva,
        string $nombreUsuario,
        string $lugar,
        string $descripcion,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = trim($id);
        $this->fecha = trim($fecha);
        $this->valorTotalSinIva = trim($valorTotalSinIva);
        $this->ivaTotal = trim($ivaTotal);
        $this->valorTotalConIva = trim($valorTotalConIva);
        $this->nombreUsuario = trim($nombreUsuario);
        $this->lugar = trim($lugar);
        $this->descripcion = trim($descripcion);
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function fecha(): string
    {
        return $this->fecha;
    }

    public function valorTotalSinIva(): string
    {
        return $this->valorTotalSinIva;
    }

    public function ivaTotal(): string
    {
        return $this->ivaTotal;
    }

    public function valorTotalConIva(): string
    {
        return $this->valorTotalConIva;
    }

    public function nombreUsuario(): string
    {
        return $this->nombreUsuario;
    }

    public function lugar(): string
    {
        return $this->lugar;
    }

    public function descripcion(): string
    {
        return $this->descripcion;
    }

    public function createdAt(): ?string
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?string
    {
        return $this->updatedAt;
    }
}