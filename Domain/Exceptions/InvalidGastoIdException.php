<?php

declare(strict_types=1);

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