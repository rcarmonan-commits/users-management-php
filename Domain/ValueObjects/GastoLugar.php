<?php

declare(strict_types=1);

require_once __DIR__ . '/../Exceptions/InvalidGastoTextoException.php';

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — VALUE OBJECT INMUTABLE: GastoLugar
 * ============================================================================
 * CAPA: Dominio (`Domain/ValueObjects`)
 *
 * ¿QUÉ HACE?
 *   Representa y valida el establecimiento, comercio o sede donde se efectuó el gasto
 *   (mínimo 3 caracteres y máximo 150 caracteres UTF-8 multibyte).
 * ============================================================================
 */
final class GastoLugar
{
    private string $value;

    public function __construct(string $value)
    {
        $normalized = trim($value);

        if ($normalized === '') {
            throw InvalidGastoTextoException::becauseFieldIsEmpty('lugar');
        }

        if (mb_strlen($normalized) < 3) {
            throw InvalidGastoTextoException::becauseLengthIsTooShort('lugar', 3);
        }

        if (mb_strlen($normalized) > 150) {
            throw InvalidGastoTextoException::becauseLengthExceedsMaximum('lugar', 150);
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