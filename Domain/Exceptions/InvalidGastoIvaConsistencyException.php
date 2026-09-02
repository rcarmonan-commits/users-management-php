<?php

declare(strict_types=1);

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