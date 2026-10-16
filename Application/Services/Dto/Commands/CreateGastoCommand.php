<?php

declare(strict_types=1);

final class CreateGastoCommand
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
        int|float|string $valorTotalSinIVA,
        int|float|string $ivaTotal,
        int|float|string $valorTotalConIVA,
        string $nombreUsuario,
        string $lugar,
        string $descripcion
    ) {
        $this->id = trim($id);
        $this->fecha = trim($fecha);
        $this->valorTotalSinIVA = trim((string) $valorTotalSinIVA);
        $this->ivaTotal = trim((string) $ivaTotal);
        $this->valorTotalConIVA = trim((string) $valorTotalConIVA);
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