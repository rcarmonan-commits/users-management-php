<?php

declare(strict_types=1);

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — OBJETO COMMAND CQRS: CreateGastoCommand
 * ============================================================================
 * CAPA: Aplicación (`Application/Services/Dto/Commands`)
 * PATRÓN: CQRS (Command Query Responsibility Segregation) — Intención de Escritura
 *
 * ¿QUÉ HACE?
 *   Encapsula de forma inmutable los datos crudos necesarios para registrar un nuevo
 *   `Gasto` (#6) en el sistema, desacoplando la petición externa (formulario HTML o
 *   cuerpo JSON REST) respecto al Caso de Uso `CreateGastoUseCase`.
 *
 * ¿POR QUÉ SE SEPARA COMMAND DE QUERY (CQRS)?
 *   Los *Commands* mutan el estado del sistema, invalidan caché y emiten Eventos de
 *   Dominio; las *Queries* solo leen sin producir efectos secundarios (*side-effects*).
 * ============================================================================
 */
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