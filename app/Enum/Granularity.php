<?php

namespace App\Enum;

enum Granularity: string
{
    case Yearly = 'yearly';
    case Monthly = 'monthly';
    case Weekly = 'weekly';
    case Daily = 'daily';
    case Hourly = 'hourly';

    public static function isYearly(string $granularity): bool
    {
        return self::tryFrom($granularity) === self::Yearly;
    }

    public static function isMonthly(string $granularity): bool
    {
        return self::tryFrom($granularity) === self::Monthly;
    }

    public static function isWeekly(string $granularity): bool
    {
        return self::tryFrom($granularity) === self::Weekly;
    }

    public static function isDaily(string $granularity): bool
    {
        return self::tryFrom($granularity) === self::Daily;
    }

    public static function isHourly(string $granularity): bool
    {
        return self::tryFrom($granularity) === self::Hourly;
    }
}
