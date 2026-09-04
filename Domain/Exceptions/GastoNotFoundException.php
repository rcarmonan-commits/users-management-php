<?php

declare(strict_types=1);

final class GastoNotFoundException extends DomainException
{
    public static function becauseIdWasNotFound(string $id): self
    {
        return new self('No se encontró el gasto con identificador: ' . $id);
    }
}