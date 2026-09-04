<?php

declare(strict_types=1);

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — EXCEPCIÓN DE DOMINIO: InvalidGastoIdException
 * ============================================================================
 * CAPA: Dominio (`Domain/Exceptions`)
 *
 * ¿QUÉ HACE?
 *   Representa la violación de las reglas de identidad del Value Object `GastoId`
 *   (identificador vacío o con longitud superior al límite permitido).
 *
 * ¿POR QUÉ USA CONSTRUCTORES SEMÁNTICOS ESTÁTICOS (`because...`)?
 *   Expresa en lenguaje de negocio la causa exacta del rechazo (Clean Code / DDD)
 *   y facilita su traducción a HTTP `422 Unprocessable Entity` en el `ErrorHandler`.
 * ============================================================================
 */
final class InvalidGastoIdException extends InvalidArgumentException
{
    public static function becauseValueIsEmpty(): self
    {
        return new self('El identificador del gasto no puede estar vacío.');
    }

    public static function becauseLengthExceedsMaximum(int $maxLength): self
    {
        return new self('El identificador del gasto no puede superar ' . $maxLength . ' caracteres.');
    }
}