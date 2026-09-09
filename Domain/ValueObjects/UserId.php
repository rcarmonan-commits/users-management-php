<?php


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — VALUE OBJECT DDD: UserId
 * ============================================================================
 * CAPA: Dominio (`Domain/ValueObjects`)
 * PATRÓN: Value Object (DDD) + Inmutabilidad + Identity Identifier
 * PRINCIPIO SOLID: SRP (Validación y comparación de identidad de Usuario)
 *
 * ¿QUÉ HACE?
 *   Encapsula la identidad única de un Usuario (UUIDv4 o identificador no vacío), protegiendo la identidad contra cadenas en blanco.
 *
 * ¿CÓMO LO HACE?
 *   Normaliza la cadena entrante con trim() y valida que no sea vacía. Si es inválida, lanza InvalidUserIdException.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Evita el antipatrón 'Primitive Obsession' (obsesión por tipos primitivos), haciendo que el código sea auto-validante y seguro por diseño.
 * ============================================================================
 */

require_once __DIR__ . '/../Exceptions/InvalidUserIdException.php';

class UserId
{
    private $value;

    public function __construct($value)
    {
        $normalizedValue = trim((string) $value);

        if ($normalizedValue === '') {
            throw InvalidUserIdException::becauseValueIsEmpty();
        }

        $this->value = $normalizedValue;
    }

    public function value()
    {
        return $this->value;
    }

    public function equals(UserId $other)
    {
        return $this->value === $other->value();
    }

    public function __toString()
    {
        return $this->value;
    }
}

