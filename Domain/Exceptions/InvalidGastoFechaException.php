<?php

declare(strict_types=1);

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — EXCEPCIÓN DE DOMINIO: InvalidGastoFechaException
 * ============================================================================
 * CAPA: Dominio (`Domain/Exceptions`)
 *
 * ¿QUÉ HACE?
 *   Señala que la fecha suministrada para un `Gasto` está vacía o no corresponde
 *   a una fecha calendario real en formato ISO `YYYY-MM-DD` (ej. rechaza `2026-02-30`).
 * ============================================================================
 */
final class InvalidGastoFechaException extends InvalidArgumentException
{
    public static function becauseValueIsEmpty(): self
    {
        return new self('La fecha del gasto es obligatoria.');
    }

    public static function becauseFormatIsInvalid(string $value): self
    {
        return new self('La fecha del gasto debe tener formato válido YYYY-MM-DD: ' . $value);
    }
}