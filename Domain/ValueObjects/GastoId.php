<?php

declare(strict_types=1);

require_once __DIR__ . '/../Exceptions/InvalidGastoIdException.php';

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — VALUE OBJECT INMUTABLE: GastoId
 * ============================================================================
 * CAPA: Dominio (`Domain/ValueObjects`)
 *
 * ¿QUÉ HACE?
 *   Representa la identidad única de un `Gasto` (compatible tanto con UUIDv4 de 36
 *   caracteres como con llaves heredadas enteras convertidas a texto, hasta 64 chars).
 *
 * ¿POR QUÉ USAR UN VALUE OBJECT EN VEZ DE UN `string` SUELTO?
 *   Evita el antipatrón *Primitive Obsession*: ninguna entidad `GastoModel` puede
 *   instanciarse jamás con un ID vacío o malformado.
 * ============================================================================
 */
final class GastoId
{
    private string $value;

    public function __construct(string $value)
    {
        $normalized = trim($value);

        if ($normalized === '') {
            throw InvalidGastoIdException::becauseValueIsEmpty();
        }

        if (strlen($normalized) > 64) {
            throw InvalidGastoIdException::becauseLengthExceedsMaximum(64);
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