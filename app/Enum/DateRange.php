<?php

namespace App\Enum;

enum DateRange: string
{
    case YearToDate = 'YTD';
    case MonthToDate = 'MTD';
    case WeekToDate = 'WTD';
    case Today = 'TODAY';
    case CustomDate = 'customDate';

    case Empty = '';

    public static function isYearToDate(string $dateRange): bool
    {
        return self::tryFrom($dateRange) === self::YearToDate;
    }

    public static function isMonthToDate(string $dateRange): bool
    {
        return self::tryFrom($dateRange) === self::MonthToDate;
    }

    public static function isWeekToDate(string $dateRange): bool
    {
        return self::tryFrom($dateRange) === self::WeekToDate;
    }

    public static function isToday(string $dateRange): bool
    {
        return self::tryFrom($dateRange) === self::Today;
    }
    public static function isCustomDate(string $dateRange): bool
    {
        return self::tryFrom($dateRange) === self::CustomDate;
    }

    public static function isEmpty(string $dateRange): bool
    {
        return self::tryFrom($dateRange) === self::Empty;
    }
}
