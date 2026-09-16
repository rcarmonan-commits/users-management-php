<?php

declare(strict_types=1);

require_once __DIR__ . '/../Exceptions/InvalidGastoIdException.php';

final class GastoId
{
    private string $value;

    public function __construct(string $value)
    {
        $normalized = trim($value);

        if ($normalized === '') {
            throw InvalidGastoIdException::becauseValueIsEmpty();
        }

        if (strlen($normalized) > 64) {
            throw InvalidGastoIdException::becauseLengthExceedsMaximum(64);
        }

        $this->value = $normalized;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value();
    }

    public function __toString(): string
    {
        return $this->value;
    }
}