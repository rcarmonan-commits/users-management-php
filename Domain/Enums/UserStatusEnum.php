<?php


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — ENUM DE DOMINIO: UserStatusEnum
 * ============================================================================
 * CAPA: Dominio (`Domain/Enums`)
 * PATRÓN: Domain Enum / Lifecycle State
 * PRINCIPIO SOLID: SRP (Estados válidos del ciclo de vida del Usuario)
 *
 * ¿QUÉ HACE?
 *   Define los estados del usuario en el sistema: ACTIVE, INACTIVE, SUSPENDED.
 *
 * ¿CÓMO LO HACE?
 *   Ofrece el método estático ensureIsValid() para garantizar transiciones de estado legales.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Garantiza que la máquina de estados del usuario sea coherente en todas las capas.
 * ============================================================================
 */

require_once __DIR__ . '/../Exceptions/InvalidUserStatusException.php';

class UserStatusEnum
{
    const ACTIVE = 'ACTIVE';
    const INACTIVE = 'INACTIVE';
    const PENDING = 'PENDING';
    const BLOCKED = 'BLOCKED';

    public static function values()
    {
        return array(
            self::ACTIVE,
            self::INACTIVE,
            self::PENDING,
            self::BLOCKED
        );
    }

    public static function isValid($value)
    {
        return in_array($value, self::values(), true);
    }

    public static function ensureIsValid($value)
    {
        if (!self::isValid($value)) {
            throw InvalidUserStatusException::becauseValueIsInvalid($value);
        }
    }
}

