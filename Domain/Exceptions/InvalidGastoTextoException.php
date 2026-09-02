<?php

declare(strict_types=1);

final class InvalidGastoTextoException extends InvalidArgumentException
{
    public static function becauseFieldIsEmpty(string $field): self
    {
        return new self(sprintf('El campo "%s" del gasto no puede estar vacío.', $field));
    }

    public static function becauseLengthIsTooShort(string $field, int $minLength): self
    {
        return new self(sprintf('El campo "%s" del gasto debe contener al menos %d caracteres.', $field, $minLength));
    }

    public static function becauseLengthExceedsMaximum(string $field, int $maxLength): self
    {
        return new self(sprintf('El campo "%s" del gasto no puede exceder %d caracteres.', $field, $maxLength));
    }
}