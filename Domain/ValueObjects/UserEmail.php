<?php


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — VALUE OBJECT DDD: UserEmail
 * ============================================================================
 * CAPA: Dominio (`Domain/ValueObjects`)
 * PATRÓN: Value Object (DDD) + Inmutabilidad + Formato RFC 5322
 * PRINCIPIO SOLID: SRP (Validación de direcciones de correo electrónico)
 *
 * ¿QUÉ HACE?
 *   Encapsula la dirección de correo electrónico del usuario, garantizando formato RFC y normalización a minúsculas.
 *
 * ¿CÓMO LO HACE?
 *   Aplica filter_var(..., FILTER_VALIDATE_EMAIL) y strtolower(). Si el formato es erróneo, lanza InvalidUserEmailException.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Centraliza la validación de correos en un solo lugar inmutable, impidiendo errores de sintaxis en capas superiores.
 * ============================================================================
 */

require_once __DIR__ . '/../Exceptions/InvalidUserEmailException.php';

class UserEmail
{
    private $value;

    public function __construct($value)
    {
        $normalizedValue = trim((string) $value);

        if ($normalizedValue === '') {
            throw InvalidUserEmailException::becauseValueIsEmpty();
        }

        if (!filter_var($normalizedValue, FILTER_VALIDATE_EMAIL)) {
            throw InvalidUserEmailException::becauseFormatIsInvalid($normalizedValue);
        }

        $this->value = strtolower($normalizedValue);
    }

    public function value()
    {
        return $this->value;
    }

    public function equals(UserEmail $other)
    {
        return $this->value === $other->value();
    }

    public function __toString()
    {
        return $this->value;
    }
}

