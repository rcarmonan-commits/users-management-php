<?php

declare(strict_types=1);

final class GastoPersistenceDto
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

    public function id(): string
    {
        return $this->id;
    }

    public function fecha(): string
    {
        return $this->fecha;
    }

    public function valorTotalSinIVA(): string
    {
        return $this->valorTotalSinIVA;
    }

    public function ivaTotal(): string
    {
        return $this->ivaTotal;
    }

    public function valorTotalConIVA(): string
    {
        return $this->valorTotalConIVA;
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
}