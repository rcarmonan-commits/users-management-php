<?php

declare(strict_types=1);

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — EXCEPCIÓN DE INVARIANTE CONTABLE: InvalidGastoIvaConsistencyException
 * ============================================================================
 * CAPA: Dominio (`Domain/Exceptions`)
 *
 * ¿QUÉ HACE?
 *   Garantiza la regla de negocio central de la entidad #6 (`Gasto`):
 *   `valorTotalSinIVA + ivaTotal === valorTotalConIVA`.
 *
 * ¿POR QUÉ EXTIENDE `DomainException`?
 *   Porque los datos individuales pueden tener formato numérico válido, pero juntos
 *   violan una ley contable innegociable del modelo de negocio.
 * ============================================================================
 */
final class InvalidGastoIvaConsistencyException extends DomainException
{
    public static function becauseTotalsDoNotMatch(
        string $sinIva,
        string $iva,
        string $conIva,
        string $expectedConIva
    ): self {
        return new self(sprintf(
            'Inconsistencia contable en el gasto: valorTotalSinIVA (%s) + ivaTotal (%s) debe ser exactamente igual a valorTotalConIVA (%s), pero se recibió %s.',
            $sinIva,
            $iva,
            $expectedConIva,
            $conIva
        ));
    }
}