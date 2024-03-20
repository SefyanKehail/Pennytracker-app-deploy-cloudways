<?php

namespace App\Services\Traits;

use App\Enum\DateRange;
use App\Enum\Granularity;
use DateInterval;
use DatePeriod;
use DateTime;

trait EmptyLabelsUtilities
{
    use StartDateEndDateGenerator;
    public function getMonthsBetweenDates($startDate = null, $endDate = null): array
    {
        $months   = [];
        $interval = DateInterval::createFromDateString('1 month');

        if ($startDate === null && $endDate === null) {
            [$startDate, $endDate] = $this->getStartDateEndDate(DateRange::YearToDate->value);
        }

        $period = new DatePeriod($startDate, $interval, $endDate);

        foreach ($period as $dt) {
            $months[] = $dt->format('M Y');
        }

        return $months;
    }

    public function getDaysBetweenDates($startDate = null, $endDate = null): array
    {
        $days     = [];
        $interval = DateInterval::createFromDateString('1 day');

        if ($startDate === null && $endDate === null) {
            [$startDate, $endDate] = $this->getStartDateEndDate(DateRange::MonthToDate->value);
        }

        $period = new DatePeriod($startDate, $interval, $endDate);

        foreach ($period as $dt) {
            $days[] = $dt->format('d M Y');
        }

        return $days;
    }

    public function getWeeksBetweenDates($startDate = null, $endDate = null): array
    {
        $weeks    = [];
        $interval = DateInterval::createFromDateString('1 week');

        if ($startDate === null && $endDate === null) {
            [$startDate, $endDate] = $this->getStartDateEndDate(DateRange::WeekToDate->value);
        }

        $period = new DatePeriod($startDate, $interval, $endDate);

        foreach ($period as $dt) {
            $weeks[] = $dt->format('W Y');
        }
        return $weeks;
    }

    public function mergeEmptyLabels(array $labels, array $results): array
    {
        $resultLabels = array_column($results, 'label');
        foreach ($labels as $label) {
            if (! in_array($label, $resultLabels)) {
                $results[] = [
                    'label'   => $label,
                    'income'  => null,
                    'expense' => null
                ];
            }
        }
        return $results;
    }


    public function week52Handling(array $results): array
    {
        if (count($results)<2){
            return $results;
        }
        $lastElement       = (int)explode(' ', $results[count($results) - 1]['label'])[0];
        $beforeLastElement = (int)explode(' ', $results[count($results) - 2]['label'])[0];

        if ($lastElement === 52 && $beforeLastElement !== 51) {
            $element = array_pop($results);
            array_unshift($results, $element);
        }

        return $results;
    }

    // if sorting is set manually this means that strtotime alone can't sort.
    public function sortResults(array $results, Granularity $granularity = null): array
    {
        if (! $granularity) {
            usort($results, function ($a, $b) {
                return strtotime($a['label']) - strtotime($b['label']);
            });
        } elseif ($granularity === Granularity::Weekly) {
            usort($results, function ($a, $b) {
                [$weekA, $yearA] = explode(' ', $a['label']);
                [$weekB, $yearB] = explode(' ', $b['label']);

                $dateA = new DateTime('midnight');
                $dateA->setISODate((int)$yearA, (int)$weekA);

                $dateB = new DateTime('midnight');
                $dateB->setISODate((int)$yearB, (int)$weekB);

                return $dateA <=> $dateB;
            });

            return $this->week52Handling($results);
        } elseif ($granularity === Granularity::Hourly) {
            usort($results, function ($a, $b) {
                $dateA = DateTime::createFromFormat('d M Y H', $a['label']);
                $dateB = DateTime::createFromFormat('d M Y H', $b['label']);

                return $dateA <=> $dateB;
            });
        } elseif ($granularity === Granularity::Yearly) {
            usort($results, function ($a, $b) {
                $dateA = DateTime::createFromFormat('Y', $a['label']);
                $dateB = DateTime::createFromFormat('Y', $b['label']);

                return $dateA <=> $dateB;
            });
        }

        return $results;
    }

    public function getHoursBetweenDates($startDate = null, $endDate = null): array
    {
        $hours    = [];
        $interval = DateInterval::createFromDateString('1 hour');

        if ($startDate === null && $endDate === null) {
            [$startDate, $endDate] = $this->getStartDateEndDate(DateRange::Today->value);
        }

        $period = new DatePeriod($startDate, $interval, $endDate);

        foreach ($period as $dt) {
            $hours[] = $dt->format('d M Y H');
        }

        return $hours;
    }

    public function getYearsBetweenDates($startDate = null, $endDate = null): array
    {
        $years    = [];
        $interval = DateInterval::createFromDateString('1 year');

        $period = new DatePeriod($startDate, $interval, $endDate);

        foreach ($period as $dt) {
            $years[] = $dt->format('Y');
        }

        return $years;
    }
}