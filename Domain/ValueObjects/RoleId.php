<?php

declare(strict_types=1);

require_once __DIR__ . '/../Exceptions/InvalidUserRoleException.php';

final class RoleId
{
    private int $value;

    public function __construct(int $value)
    {
        if ($value <= 0) {
            throw InvalidUserRoleException::becauseValueIsInvalid((string) $value);
        }

        $this->value = $value;
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value();
    }
}