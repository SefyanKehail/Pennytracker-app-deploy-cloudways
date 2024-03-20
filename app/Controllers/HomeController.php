<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Contracts\ValidatorFactoryInterface;
use App\Enum\DateRange;
use App\Enum\Granularity;
use App\Exceptions\ValidationException;
use App\ResponseFormatter;
use App\Services\CategoryService;
use App\Services\Traits\StartDateEndDateGenerator;
use App\Services\TransactionsChartService;
use App\Services\TransactionService;
use App\Validators\ChartCustomDateValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class HomeController
{
    public function __construct(
        private readonly Twig                      $twig,
        private readonly TransactionsChartService  $transactionsChartService,
        private readonly CategoryService           $categoryService,
        private readonly ResponseFormatter         $responseFormatter,
        private readonly ValidatorFactoryInterface $validatorFactory
    ) {
    }

    use StartDateEndDateGenerator;

    // this method takes both a post and get part (post to set the customDate and get to get data)
    public function index(Request $request, Response $response, array $args): Response
    {
        $dateRange = $args['dateRange'];

        if (DateRange::isCustomDate($dateRange)) {
            if (strtolower($request->getMethod()) === 'get') {
                $data = $request->getQueryParams();

            } elseif (strtolower($request->getMethod()) === 'post') {
                $data = $request->getParsedBody();
            }

            $data = $this->validatorFactory->make(ChartCustomDateValidator::class)->validate($data ?? null);

            [$startDate, $endDate] = $this->getStartDateEndDate(
                $dateRange,
                $data['startDate'],
                $data['endDate']
            );
        } else {
            [$startDate, $endDate] = $this->getStartDateEndDate($dateRange);
        }


        $totals                = $this->transactionsChartService->getTotals($startDate, $endDate);
        $recentTransactions    = $this->transactionsChartService->getRecentTransactions(10);
        $topSpendingCategories = $this->categoryService->getTopSpendingCategories($startDate, $endDate, 4);

        return $this->twig->render(
            $response,
            'dashboard.twig',
            [
                'totals'                => $totals,
                'transactions'          => $recentTransactions,
                'topSpendingCategories' => $topSpendingCategories,
                'dateRange'             => $dateRange,
            ]
        );
    }

    public function getStatistics(Request $request, Response $response, array $args): Response
    {
        $dateRange = $args['dateRange'];


        $queryParams = $request->getQueryParams();

        $startDate   = $queryParams['startDate'] ?? null;
        $endDate     = $queryParams['endDate'] ?? null;
        $granularity = $queryParams['granularity'] ?? null;

        // this is the format I receive from ajax call Y-m-d\TH:i form
        [$startDate, $endDate] = $this->getStartDateEndDate($dateRange, $startDate, $endDate);


        if (
            DateRange::isYearToDate($dateRange)
            || DateRange::isEmpty($dateRange)
            || (DateRange::isCustomDate($dateRange) && Granularity::isMonthly($granularity))
        ) {
            $data = $this->transactionsChartService->getMonthlySummary(
                $startDate,
                $endDate,
            );
        } elseif (DateRange::isMonthToDate($dateRange) || (DateRange::isCustomDate($dateRange
                ) && Granularity::isDaily($granularity
                ))) {
            $data = $this->transactionsChartService->getDailySummary(
                $startDate,
                $endDate,
            );
        } elseif (DateRange::isWeekToDate($dateRange) || (DateRange::isCustomDate($dateRange
                ) && Granularity::isWeekly($granularity
                ))) {
            $data = $this->transactionsChartService->getDailySummary(
                $startDate,
                $endDate,
            );
        } elseif (DateRange::isToday($dateRange) || (DateRange::isCustomDate($dateRange
                ) && Granularity::isHourly($granularity
                ))) {
            $data = $this->transactionsChartService->getHourlySummary(
                $startDate,
                $endDate,
            );
        } elseif (DateRange::isCustomDate($dateRange) && Granularity::isYearly($granularity)) {
            $data = $this->transactionsChartService->getYearlySummary(
                $startDate,
                $endDate,
            );
        }


        return $this->responseFormatter->asJson($response, $data ?? null);
    }
}