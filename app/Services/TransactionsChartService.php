<?php

namespace App\Services;

use App\Entity\Transaction;
use App\Enum\Granularity;
use App\Services\Traits\EmptyLabelsUtilities;
use DateTime;

class TransactionsChartService
{
    public function __construct(private readonly EntityManagerService $entityManagerService)
    {
    }

    use EmptyLabelsUtilities;

    public function getTotals(\DateTime $startDate, \DateTime $endDate): array
    {
        $totals = $this->entityManagerService
            ->getRepository(Transaction::class)
            ->createQueryBuilder('tr')
            ->select(
                'SUM(CASE WHEN tr.amount >= 0 THEN tr.amount ELSE 0 END) as income, 
                SUM(CASE WHEN tr.amount < 0 THEN tr.amount ELSE 0 END) as expense'
            )
            ->where('tr.date BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()->getOneOrNullResult();

        return [
            'net'     => array_sum(array_values($totals)),
            'income'  => $totals['income'],
            'expense' => $totals['expense']
        ];
    }

    public function getRecentTransactions(int $limit): array
    {
        return $this->entityManagerService->getRepository(Transaction::class)
                                          ->createQueryBuilder('tr')
                                          ->select('tr', 'c')
                                          ->leftJoin('tr.category', 'c')
                                          ->orderBy('tr.date', 'desc')
                                          ->getQuery()
                                          ->setMaxResults($limit)
                                          ->getArrayResult();
    }

    public function getMonthlySummary(DateTime $startDate = null, DateTime $endDate = null): array
    {
        $query = $this->entityManagerService
            ->getRepository(Transaction::class)
            ->createQueryBuilder('tr')
            ->select('
            SUM(CASE WHEN tr.amount >= 0 THEN tr.amount ELSE 0 END) as income,
            SUM(CASE WHEN tr.amount < 0 THEN ABS(tr.amount) ELSE 0 END) as expense'
            )
            ->addSelect("DATE_FORMAT(tr.date, '%b %Y') as label");

        if ($startDate === null && $endDate === null) {
            $year = (int)date('Y');

            $labels = $this->getMonthsBetweenDates();

            $query = $query->where('YEAR(tr.date) = :year')
                           ->setParameter('year', $year);
        } else {
            $labels = $this->getMonthsBetweenDates($startDate, $endDate);

            $query = $query->where('tr.date BETWEEN :startDate AND :endDate')
                           ->setParameter('startDate', $startDate)
                           ->setParameter('endDate', $endDate);
        }

        $results = $query->groupBy('label')
                         ->getQuery()
                         ->getArrayResult();

        $results = $this->mergeEmptyLabels($labels, $results);

        return $this->sortResults($results);
    }

    public function getDailySummary(DateTime $startDate = null, DateTime $endDate = null): array
    {
        $query = $this->entityManagerService
            ->getRepository(Transaction::class)
            ->createQueryBuilder('tr')
            ->select('
            SUM(CASE WHEN tr.amount >= 0 THEN tr.amount ELSE 0 END) as income,
            SUM(CASE WHEN tr.amount < 0 THEN ABS(tr.amount) ELSE 0 END) as expense'
            )
            ->addSelect("DATE_FORMAT(tr.date, '%d %b %Y') as label");

        if ($startDate === null && $endDate === null) {
            $year  = (int)date('Y');
            $month = (int)date('m');

            $labels = $this->getDaysBetweenDates();

            $query = $query->where('YEAR(tr.date) = :year')
                           ->andWhere('MONTH(tr.date) = :month')
                           ->setParameter('year', $year)
                           ->setParameter('month', $month);
        } else {
            $labels = $this->getDaysBetweenDates($startDate, $endDate);

            $query = $query->where('tr.date BETWEEN :startDate AND :endDate')
                           ->setParameter('startDate', $startDate)
                           ->setParameter('endDate', $endDate);
        }

        $results = $query->groupBy('label')
                         ->getQuery()
                         ->getArrayResult();

        $results = $this->mergeEmptyLabels($labels, $results);

        return $this->sortResults($results);
    }

    public function getWeeklySummary(DateTime $startDate = null, DateTime $endDate = null): array
    {
        $query = $this->entityManagerService
            ->getRepository(Transaction::class)
            ->createQueryBuilder('tr')
            ->select('
            SUM(CASE WHEN tr.amount >= 0 THEN tr.amount ELSE 0 END) as income,
            SUM(CASE WHEN tr.amount < 0 THEN ABS(tr.amount) ELSE 0 END) as expense'
            )
            ->addSelect("DATE_FORMAT(tr.date, '%u %Y') as label");

        if ($startDate === null && $endDate === null) {
            $year = (int)date('Y');
            $week = (int)date('W');

            $labels = $this->getWeeksBetweenDates();

            $query = $query->where('YEAR(tr.date) = :year')
                           ->andWhere('WEEK(tr.date) = :week')
                           ->setParameter('year', $year)
                           ->setParameter('week', $week);
        } else {
            $labels = $this->getWeeksBetweenDates($startDate, $endDate);

            $query = $query->where('tr.date BETWEEN :startDate AND :endDate')
                           ->setParameter('startDate', $startDate)
                           ->setParameter('endDate', $endDate);
        }

        $results = $query->groupBy('label')
                         ->getQuery()
                         ->getArrayResult();

        $results = $this->mergeEmptyLabels($labels, $results);

        return $this->sortResults($results, Granularity::Weekly);
    }

    public function getHourlySummary(DateTime $startDate = null, DateTime $endDate = null): array
    {
        $query = $this->entityManagerService
            ->getRepository(Transaction::class)
            ->createQueryBuilder('tr')
            ->select('
            SUM(CASE WHEN tr.amount >= 0 THEN tr.amount ELSE 0 END) as income,
            SUM(CASE WHEN tr.amount < 0 THEN ABS(tr.amount) ELSE 0 END) as expense'
            )
            ->addSelect("DATE_FORMAT(tr.date, '%d %b %Y %H') as label");

        if ($startDate === null && $endDate === null) {
            $day = date('Y-m-d');

            $labels = $this->getHoursBetweenDates();

            $query = $query->where("DATE_FORMAT(tr.date, '%Y-%m-%d') = :day")
                           ->setParameter('day', $day);
        } else {
            $labels = $this->getHoursBetweenDates($startDate, $endDate);

            $query = $query->where('tr.date BETWEEN :startDate AND :endDate')
                           ->setParameter('startDate', $startDate)
                           ->setParameter('endDate', $endDate);
        }

        $results = $query->groupBy('label')
                         ->getQuery()
                         ->getArrayResult();


        $results = $this->mergeEmptyLabels($labels, $results);


        return $this->sortResults($results, Granularity::Hourly);
    }

    public function getYearlySummary(DateTime $startDate, DateTime $endDate): array
    {
        $query = $this->entityManagerService
            ->getRepository(Transaction::class)
            ->createQueryBuilder('tr')
            ->select('
            SUM(CASE WHEN tr.amount >= 0 THEN tr.amount ELSE 0 END) as income,
            SUM(CASE WHEN tr.amount < 0 THEN ABS(tr.amount) ELSE 0 END) as expense'
            )
            ->addSelect("DATE_FORMAT(tr.date, '%Y') as label");

        $labels = $this->getYearsBetweenDates($startDate, $endDate);

        $query = $query->where('tr.date BETWEEN :startDate AND :endDate')
                       ->setParameter('startDate', $startDate)
                       ->setParameter('endDate', $endDate);

        $results = $query->groupBy('label')
                         ->getQuery()
                         ->getArrayResult();


        $results = $this->mergeEmptyLabels($labels, $results);
        return $this->sortResults($results, Granularity::Yearly);
    }
}