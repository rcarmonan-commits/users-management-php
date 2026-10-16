<?php

declare(strict_types=1);

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — DTO DE RESPUESTA DE APLICACIÓN: GastoApplicationResponse
 * ============================================================================
 * CAPA: Aplicación (`Application/Services/Dto/Responses`)
 * PATRÓN: Proyección de Salida Desacoplada del Dominio
 *
 * ¿QUÉ HACE?
 *   Entrega una vista serializable y segura de `GastoModel` hacia los Entrypoints
 *   sin exponer métodos mutadores ni detalles internos de los Value Objects.
 * ============================================================================
 */
final class GastoApplicationResponse
{
    private string $id;
    private string $fecha;
    private float $valorTotalSinIVA;
    private float $ivaTotal;
    private float $valorTotalConIVA;
    private string $nombreUsuario;
    private string $lugar;
    private string $descripcion;

    public function __construct(
        string $id,
        string $fecha,
        float $valorTotalSinIVA,
        float $ivaTotal,
        float $valorTotalConIVA,
        string $nombreUsuario,
        string $lugar,
        string $descripcion
    ) {
        $this->id = $id;
        $this->fecha = $fecha;
        $this->valorTotalSinIVA = $valorTotalSinIVA;
        $this->ivaTotal = $ivaTotal;
        $this->valorTotalConIVA = $valorTotalConIVA;
        $this->nombreUsuario = $nombreUsuario;
        $this->lugar = $lugar;
        $this->descripcion = $descripcion;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getFecha(): string
    {
        return $this->fecha;
    }

    public function getValorTotalSinIVA(): float
    {
        return $this->valorTotalSinIVA;
    }

    public function getIvaTotal(): float
    {
        return $this->ivaTotal;
    }

    public function getValorTotalConIVA(): float
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

    /**
     * @return array<string, float|string>
     */
    public function toArray(): array
    {
        return array(
            'id' => $this->id,
            'fecha' => $this->fecha,
            'valorTotalSinIVA' => $this->valorTotalSinIVA,
            'ivaTotal' => $this->ivaTotal,
            'valorTotalConIVA' => $this->valorTotalConIVA,
            'nombreUsuario' => $this->nombreUsuario,
            'lugar' => $this->lugar,
            'descripcion' => $this->descripcion,
        );
    }
}