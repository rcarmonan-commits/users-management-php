<?php

declare(strict_types=1);

require_once __DIR__ . '/../Exceptions/InvalidGastoFechaException.php';

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — VALUE OBJECT INMUTABLE: GastoFecha
 * ============================================================================
 * CAPA: Dominio (`Domain/ValueObjects`)
 *
 * ¿QUÉ HACE?
 *   Garantiza que el atributo de negocio `fecha` del Ejercicio #6 sea siempre una
 *   fecha calendario estrictamente válida bajo formato `YYYY-MM-DD`.
 *
 * ¿CÓMO LO HACE?
 *   Usa `DateTimeImmutable::createFromFormat('!Y-m-d', ...)` e inspecciona
 *   `DateTimeImmutable::getLastErrors()` para rechazar desbordamientos silenciosos
 *   como `2026-02-30` (que PHP sin verificación convertiría a marzo).
 * ============================================================================
 */
final class GastoFecha
{
    private string $value;

    public function __construct(string $value)
    {
        $normalized = trim($value);

        if ($normalized === '') {
            throw InvalidGastoFechaException::becauseValueIsEmpty();
        }

        $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $normalized);
        $errors = DateTimeImmutable::getLastErrors();
        $hasErrors = is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);

        if ($dt === false || $hasErrors || $dt->format('Y-m-d') !== $normalized) {
            throw InvalidGastoFechaException::becauseFormatIsInvalid($normalized);
        }

        $this->value = $normalized;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value();
    }

    public function __toString(): string
    {
        return $this->value;
    }
}