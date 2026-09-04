<?php

declare(strict_types=1);

final class GastoIvaRateEnum
{
    public const EXENTO = 0;
    public const REDUCIDO = 5;
    public const GENERAL = 19;

    /**
     * @return int[]
     */
    public static function values(): array
    {
        return array(
            self::EXENTO,
            self::REDUCIDO,
            self::GENERAL,
        );
    }

    public static function isStandardRate(int $percentage): bool
    {
        return in_array($percentage, self::values(), true);
    }
}