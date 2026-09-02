<?php

declare(strict_types=1);

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — EXCEPCIÓN DE DOMINIO: GastoNotFoundException
 * ============================================================================
 * CAPA: Dominio (`Domain/Exceptions`)
 *
 * ¿QUÉ HACE?
 *   Indica que un registro `Gasto` solicitado por su identificador no existe en el
 *   almacén de datos. El `ErrorHandler` REST la traduce automáticamente a HTTP `404`.
 * ============================================================================
 */
final class GastoNotFoundException extends DomainException
{
    public static function becauseIdWasNotFound(string $id): self
    {
        return new self('No se encontró el gasto con identificador: ' . $id);
    }
}