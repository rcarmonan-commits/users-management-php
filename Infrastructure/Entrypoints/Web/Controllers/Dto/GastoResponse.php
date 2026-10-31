<?php

declare(strict_types=1);

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — VIEW MODEL / RESPONSE DTO WEB: GastoResponse
 * ============================================================================
 * CAPA: Infraestructura — Entrypoint Web (`Infrastructure/Entrypoints/Web/Controllers/Dto`)
 *
 * ¿QUÉ HACE?
 *   Expone datos primitivos de lectura segura (`string` / `float`) para que las
 *   plantillas HTML (`Views/gastos/*`, `Views/reports/*`) rendericen sin acoplarse
 *   a métodos internos de `GastoModel` ni a Value Objects del Dominio.
 * ============================================================================
 */
final class GastoResponse
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