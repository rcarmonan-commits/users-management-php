<?php

declare(strict_types=1);

require_once __DIR__ . '/../Exceptions/InvalidGastoMontoException.php';

final class GastoMonto
{
    private int $cents;

    private function __construct(int $cents)
    {
        $this->cents = $cents;
    }

    public static function fromInput(int|float|string $raw, string $fieldName, bool $allowZero = true): self
    {
        $normalized = is_string($raw) ? trim($raw) : (string) $raw;

        if ($normalized === '' || !is_numeric($normalized)) {
            throw InvalidGastoMontoException::becauseValueIsNotNumeric($fieldName, (string) $raw);
        }

        $floatVal = (float) $normalized;
        $cents = (int) round($floatVal * 100, 0, PHP_ROUND_HALF_UP);

        if ($cents < 0) {
            throw InvalidGastoMontoException::becauseValueIsNegative($fieldName, $floatVal);
        }

        if (!$allowZero && $cents === 0) {
            throw InvalidGastoMontoException::becauseValueMustBeGreaterThanZero($fieldName);
        }

        return new self($cents);
    }

    public function cents(): int
    {
        return $this->cents;
    }

    public function toFloat(): float
    {
        return round($this->cents / 100, 2);
    }

    public function formatted(): string
    {
        return number_format($this->cents / 100, 2, '.', '');
    }

    public function add(self $other): self
    {
        return new self($this->cents + $other->cents());
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents();
    }

    public function __toString(): string
    {
        return $this->formatted();
    }
}