<?php

namespace App\Services\Traits;

use App\Enum\DateRange;
use DateTime;

trait StartDateEndDateGenerator
{
    public function getStartDateEndDate(string $dateRange, string $startDate = null, string $endDate= null): array
    {
        // from the start of the year
        if (DateRange::isYearToDate($dateRange) || DateRange::isEmpty($dateRange)) {
            $startDate = \DateTime::createFromFormat('Y-m-d', date('Y-01-01'));
            $endDate   = new DateTime('now');
        }

        // from the start of the month
        if (DateRange::isMonthToDate($dateRange)) {
            $startDate = \DateTime::createFromFormat('Y-m-d', date('Y-m-01'));
            $endDate   = new DateTime('now');
        }

        // from the start of the week
        if (DateRange::isWeekToDate($dateRange)) {
            $startDate = new \DateTime('Last Monday');
            $startDate->setTime(0, 0);;
            $endDate = new DateTime('now');
        }

        // from the start of the day
        if (DateRange::isToday($dateRange)) {
            $startDate = new \DateTime('midnight');
            $endDate   = new DateTime('now');
        }

        // if it's a custom date get the dates as strings from request query param and format it to DateTime
        if (DateRange::isCustomDate($dateRange)) {
            $startDate = \DateTime::createFromFormat('Y-m-d\TH:i', $startDate);
            $endDate   = \DateTime::createFromFormat('Y-m-d\TH:i', $endDate);
        }

        return [$startDate ?? null, $endDate ?? null];
    }
}