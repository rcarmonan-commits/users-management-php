<?php

declare(strict_types=1);

final class InvalidGastoMontoException extends InvalidArgumentException
{
    public static function becauseValueIsNotNumeric(string $field, string $raw): self
    {
        return new self(sprintf('El campo monetario "%s" debe ser numérico. Valor recibido: %s', $field, $raw));
    }

    public static function becauseValueIsNegative(string $field, float $amount): self
    {
        return new self(sprintf('El campo monetario "%s" no puede ser negativo (%.2f).', $field, $amount));
    }

    public static function becauseValueMustBeGreaterThanZero(string $field): self
    {
        return new self(sprintf('El campo monetario "%s" debe ser mayor que cero.', $field));
    }
}