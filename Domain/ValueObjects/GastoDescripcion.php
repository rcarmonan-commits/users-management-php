<?php

declare(strict_types=1);

require_once __DIR__ . '/../Exceptions/InvalidGastoTextoException.php';

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — VALUE OBJECT INMUTABLE: GastoDescripcion
 * ============================================================================
 * CAPA: Dominio (`Domain/ValueObjects`)
 *
 * ¿QUÉ HACE?
 *   Encapsula y valida el concepto detallado o justificación contable del gasto
 *   (mínimo 5 caracteres y máximo 500 caracteres UTF-8 multibyte).
 * ============================================================================
 */
final class GastoDescripcion
{
    private string $value;

    public function __construct(string $value)
    {
        $normalized = trim($value);

        if ($normalized === '') {
            throw InvalidGastoTextoException::becauseFieldIsEmpty('descripcion');
        }

        if (mb_strlen($normalized) < 5) {
            throw InvalidGastoTextoException::becauseLengthIsTooShort('descripcion', 5);
        }

        if (mb_strlen($normalized) > 500) {
            throw InvalidGastoTextoException::becauseLengthExceedsMaximum('descripcion', 500);
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