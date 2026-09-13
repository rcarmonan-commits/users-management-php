<?php


/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — ENUM DE DOMINIO: UserRoleEnum
 * ============================================================================
 * CAPA: Dominio (`Domain/Enums`)
 * PATRÓN: Domain Enum / Type Safe State
 * PRINCIPIO SOLID: OCP (Extensión controlada de roles de acceso)
 *
 * ¿QUÉ HACE?
 *   Define los roles permitidos en el sistema: ADMIN, MEMBER y REVIEWER.
 *
 * ¿CÓMO LO HACE?
 *   Ofrece el método estático ensureIsValid() que valida si un string pertenece a la lista blanca de roles autorizados.
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Evita errores tipográficos en los roles y centraliza el control de acceso en el modelo de dominio.
 * ============================================================================
 */

require_once __DIR__ . '/../Exceptions/InvalidUserRoleException.php';

class UserRoleEnum
{
    const ADMIN = 'ADMIN';
    const MEMBER = 'MEMBER';
    const REVIEWER = 'REVIEWER';

    public static function values()
    {
        return array(
            self::ADMIN,
            self::MEMBER,
            self::REVIEWER
        );
    }

    public static function isValid($value)
    {
        return in_array($value, self::values(), true);
    }

    public static function ensureIsValid($value)
    {
        if (!self::isValid($value)) {
            throw InvalidUserRoleException::becauseValueIsInvalid($value);
        }
    }
}

