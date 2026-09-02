<?php

declare(strict_types=1);

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — ENUMERACIÓN DE DOMINIO: GastoIvaRateEnum
 * ============================================================================
 * CAPA: Dominio (`Domain/Enums`) — Cero dependencias hacia capas externas
 * PATRÓN: Lenguaje Ubicuo DDD (Tarifas Tributarias Estándar de IVA)
 *
 * ¿QUÉ HACE?
 *   Centraliza las tarifas porcentuales estándar del Impuesto al Valor Agregado (IVA)
 *   vigentes en el dominio contable colombiano: Exento (0%), Reducido (5%) y General (19%).
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Elimina "números mágicos" dispersos por el código (Clean Code) y permite validar
 *   o sugerir tarifas tributarias sin acoplar el dominio a servicios externos.
 * ============================================================================
 */
final class GastoIvaRateEnum
{
    public const EXENTO = 0;
    public const REDUCIDO = 5;
    public const GENERAL = 19;

    /**
     * @return int[]
     */
    public static function values(): array
    {
        return array(
            self::EXENTO,
            self::REDUCIDO,
            self::GENERAL,
        );
    }

    public static function isStandardRate(int $percentage): bool
    {
        return in_array($percentage, self::values(), true);
    }
}