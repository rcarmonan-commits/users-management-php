<?php

declare(strict_types=1);

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — DTO HTTP ENTRADA WEB: CreateGastoWebRequest
 * ============================================================================
 * CAPA: Infraestructura — Entrypoint Web (`Infrastructure/Entrypoints/Web/Controllers/Dto`)
 *
 * ¿QUÉ HACE?
 *   Encapsula y sanea (`trim`) los campos recibidos desde el formulario HTML
 *   `POST ?route=gastos.store` antes de entregarlos al controlador `GastoController`.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Evita pasar superglobales crudas (`$_POST`) hacia el controlador o la capa de
 *   Aplicación, manteniendo contratos fuertemente tipados en cada frontera.
 * ============================================================================
 */
final class CreateGastoWebRequest
{
    private string $id;
    private string $fecha;
    private string $valorTotalSinIVA;
    private string $ivaTotal;
    private string $valorTotalConIVA;
    private string $nombreUsuario;
    private string $lugar;
    private string $descripcion;

    public function __construct(
        string $id,
        string $fecha,
        string $valorTotalSinIVA,
        string $ivaTotal,
        string $valorTotalConIVA,
        string $nombreUsuario,
        string $lugar,
        string $descripcion
    ) {
        $this->id = trim($id);
        $this->fecha = trim($fecha);
        $this->valorTotalSinIVA = trim($valorTotalSinIVA);
        $this->ivaTotal = trim($ivaTotal);
        $this->valorTotalConIVA = trim($valorTotalConIVA);
        $this->nombreUsuario = trim($nombreUsuario);
        $this->lugar = trim($lugar);
        $this->descripcion = trim($descripcion);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getFecha(): string
    {
        return $this->fecha;
    }

    public function getValorTotalSinIVA(): string
    {
        return $this->valorTotalSinIVA;
    }

    public function getIvaTotal(): string
    {
        return $this->ivaTotal;
    }

    public function getValorTotalConIVA(): string
    {
        return $this->valorTotalConIVA;
    }

    public function getNombreUsuario(): string
    {
        return $this->nombreUsuario;
    }

    public function getLugar(): string
    {
        return $this->lugar;
    }

    public function getDescripcion(): string
    {
        return $this->descripcion;
    }
}