<?php

declare(strict_types=1);

require_once __DIR__ . '/../ValueObjects/GastoId.php';
require_once __DIR__ . '/../ValueObjects/GastoFecha.php';
require_once __DIR__ . '/../ValueObjects/GastoMonto.php';
require_once __DIR__ . '/../ValueObjects/GastoLugar.php';
require_once __DIR__ . '/../ValueObjects/GastoDescripcion.php';
require_once __DIR__ . '/../ValueObjects/UserName.php';
require_once __DIR__ . '/../Exceptions/InvalidGastoIvaConsistencyException.php';

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — RAÍZ DE AGREGADO / MODELO RICO DDD (#6): GastoModel
 * ============================================================================
 * CAPA: Dominio (`Domain/Models`) — Núcleo Puro de Negocio
 * PATRÓN: Rich Domain Model (DDD) + Inmutabilidad + Protección de Invariantes
 *
 * ¿QUÉ HACE?
 *   Modela la entidad asignada N.º 6 (`Gasto`) con su identidad `id` y los 7 atributos
 *   de negocio exigidos por la guía académica:
 *     1. `fecha`             (`GastoFecha`)
 *     2. `valorTotalSinIVA`  (`GastoMonto` > 0)
 *     3. `ivaTotal`          (`GastoMonto` >= 0, soporta gastos exentos)
 *     4. `valorTotalConIVA`  (`GastoMonto` > 0)
 *     5. `nombreUsuario`     (`UserName`)
 *     6. `lugar`             (`GastoLugar`)
 *     7. `descripcion`       (`GastoDescripcion`)
 *
 * ¿CÓMO PROTEGE LA INTEGRIDAD DEL NEGOCIO?
 *   En el mismo constructor verifica que `valorTotalSinIVA->add($ivaTotal)` coincida
 *   al centavo con `valorTotalConIVA`. Si hay descuadre, lanza inmediatamente
 *   `InvalidGastoIvaConsistencyException`, haciendo imposible que exista en memoria
 *   o se persista un gasto contablemente corrupto.
 *
 * ¿POR QUÉ NO DEPENDE DE BASE DE DATOS NI FRAMEWORKS?
 *   Cumple al 100% la Regla de Inversión de Dependencias: la aplicación se construye
 *   desde el dominio hacia afuera.
 * ============================================================================
 */
final class GastoModel
{
    private GastoId $id;
    private GastoFecha $fecha;
    private GastoMonto $valorTotalSinIVA;
    private GastoMonto $ivaTotal;
    private GastoMonto $valorTotalConIVA;
    private UserName $nombreUsuario;
    private GastoLugar $lugar;
    private GastoDescripcion $descripcion;

    public function __construct(
        GastoId $id,
        GastoFecha $fecha,
        GastoMonto $valorTotalSinIVA,
        GastoMonto $ivaTotal,
        GastoMonto $valorTotalConIVA,
        UserName $nombreUsuario,
        GastoLugar $lugar,
        GastoDescripcion $descripcion
    ) {
        $expectedConIva = $valorTotalSinIVA->add($ivaTotal);

        if (!$expectedConIva->equals($valorTotalConIVA)) {
            throw InvalidGastoIvaConsistencyException::becauseTotalsDoNotMatch(
                $valorTotalSinIVA->formatted(),
                $ivaTotal->formatted(),
                $valorTotalConIVA->formatted(),
                $expectedConIva->formatted()
            );
        }

        $this->id = $id;
        $this->fecha = $fecha;
        $this->valorTotalSinIVA = $valorTotalSinIVA;
        $this->ivaTotal = $ivaTotal;
        $this->valorTotalConIVA = $valorTotalConIVA;
        $this->nombreUsuario = $nombreUsuario;
        $this->lugar = $lugar;
        $this->descripcion = $descripcion;
    }

    /**
     * Fábrica semántica expresiva del lenguaje ubicuo DDD.
     */
    public static function create(
        GastoId $id,
        GastoFecha $fecha,
        GastoMonto $valorTotalSinIVA,
        GastoMonto $ivaTotal,
        GastoMonto $valorTotalConIVA,
        UserName $nombreUsuario,
        GastoLugar $lugar,
        GastoDescripcion $descripcion
    ): self {
        return new self(
            $id,
            $fecha,
            $valorTotalSinIVA,
            $ivaTotal,
            $valorTotalConIVA,
            $nombreUsuario,
            $lugar,
            $descripcion
        );
    }

    public function id(): GastoId
    {
        return $this->id;
    }

    public function fecha(): GastoFecha
    {
        return $this->fecha;
    }

    public function valorTotalSinIVA(): GastoMonto
    {
        return $this->valorTotalSinIVA;
    }

    public function ivaTotal(): GastoMonto
    {
        return $this->ivaTotal;
    }

    public function valorTotalConIVA(): GastoMonto
    {
        return $this->valorTotalConIVA;
    }

    public function nombreUsuario(): UserName
    {
        return $this->nombreUsuario;
    }

    public function lugar(): GastoLugar
    {
        return $this->lugar;
    }

    public function descripcion(): GastoDescripcion
    {
        return $this->descripcion;
    }

    /**
     * Comportamiento rico inmutable: retorna una nueva instancia validando el cuadre de IVA.
     */
    public function actualizarMontos(
        GastoMonto $valorTotalSinIVA,
        GastoMonto $ivaTotal,
        GastoMonto $valorTotalConIVA
    ): self {
        return new self(
            $this->id,
            $this->fecha,
            $valorTotalSinIVA,
            $ivaTotal,
            $valorTotalConIVA,
            $this->nombreUsuario,
            $this->lugar,
            $this->descripcion
        );
    }

    /**
     * Comportamiento rico inmutable para actualizar ubicación y detalle descriptivo.
     */
    public function cambiarLugarYDescripcion(GastoLugar $lugar, GastoDescripcion $descripcion): self
    {
        return new self(
            $this->id,
            $this->fecha,
            $this->valorTotalSinIVA,
            $this->ivaTotal,
            $this->valorTotalConIVA,
            $this->nombreUsuario,
            $lugar,
            $descripcion
        );
    }

    /**
     * @return array<string, float|string>
     */
    public function toArray(): array
    {
        return array(
            'id' => $this->id->value(),
            'fecha' => $this->fecha->value(),
            'valorTotalSinIVA' => $this->valorTotalSinIVA->toFloat(),
            'ivaTotal' => $this->ivaTotal->toFloat(),
            'valorTotalConIVA' => $this->valorTotalConIVA->toFloat(),
            'nombreUsuario' => $this->nombreUsuario->value(),
            'lugar' => $this->lugar->value(),
            'descripcion' => $this->descripcion->value(),
        );
    }
}