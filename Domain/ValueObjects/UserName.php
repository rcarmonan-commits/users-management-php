<?php


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — VALUE OBJECT DDD: UserName
 * ============================================================================
 * CAPA: Dominio (`Domain/ValueObjects`)
 * PATRÓN: Value Object (DDD) + Inmutabilidad
 * PRINCIPIO SOLID: SRP (Validación del nombre de Usuario)
 *
 * ¿QUÉ HACE?
 *   Encapsula el nombre del usuario, asegurando que tenga una longitud mínima válida y caracteres legibles.
 *
 * ¿CÓMO LO HACE?
 *   Valida longitud y contenido con trim(). Si no cumple los requisitos del negocio, lanza InvalidUserNameException.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Garantiza que ningún usuario sin nombre o con espacios en blanco pueda ingresar a la aplicación.
 * ============================================================================
 */

require_once __DIR__ . '/../Exceptions/InvalidUserNameException.php';

class UserName
{
    private $value;

    public function __construct($value)
    {
        $normalizedValue = trim((string) $value);

        if ($normalizedValue === '') {
            throw InvalidUserNameException::becauseValueIsEmpty();
        }

        if (mb_strlen($normalizedValue) < 3) {
            throw InvalidUserNameException::becauseLengthIsTooShort(3);
        }

        $this->value = $normalizedValue;
    }

    public function value()
    {
        return $this->value;
    }

    public function equals(UserName $other)
    {
        return $this->value === $other->value();
    }

    public function __toString()
    {
        return $this->value;
    }
}