<?php

declare(strict_types=1);

final class InvalidGastoFechaException extends InvalidArgumentException
{
    public static function becauseValueIsEmpty(): self
    {
        return new self('La fecha del gasto es obligatoria.');
    }

    public static function becauseFormatIsInvalid(string $value): self
    {
        return new self('La fecha del gasto debe tener formato válido YYYY-MM-DD: ' . $value);
    }
}