<?php

namespace App\Service\Admin\Dashboard;

use App\Dto\Admin\Dashboard\SalesCategorySeriesDto;
use App\Dto\Admin\Dashboard\SalesCategoryShareDto;
use App\Dto\Admin\Dashboard\SalesHistogramPointDto;
use App\Dto\Admin\Dashboard\SalesPeriodFilterDto;
use App\Repository\OrdersRepository;

final class SalesAnalyticsService
{
    public function __construct(
        private readonly OrdersRepository $ordersRepository,
    ) {
    }

    /**
     * @return SalesHistogramPointDto[]
     */
    public function daily(?SalesPeriodFilterDto $filter = null): array
    {
        [$startDate, $endDate] = $this->resolveBounds($filter, 'daily');
        $rows = $this->ordersRepository->fetchSalesHistogram($startDate, $endDate, 'day');

        return $this->buildHistogramPoints($startDate, $endDate, $rows, 'day');
    }

    /**
     * @return SalesHistogramPointDto[]
     */
    public function weekly(?SalesPeriodFilterDto $filter = null): array
    {
        [$startDate, $endDate] = $this->resolveBounds($filter, 'weekly');
        $rows = $this->ordersRepository->fetchSalesHistogram($startDate, $endDate, 'week');

        return $this->buildHistogramPoints($startDate, $endDate, $rows, 'week');
    }

    /**
     * @return SalesCategorySeriesDto[]
     */
    public function weeklyByCategory(?SalesPeriodFilterDto $filter = null): array
    {
        [$startDate, $endDate] = $this->resolveBounds($filter, 'weekly');
        $periods = $this->buildPeriods($startDate, $endDate, 'week');
        $rows = $this->ordersRepository->fetchSalesByCategorySeries($startDate, $endDate, 'week');

        $categories = [];
        foreach ($rows as $row) {
            $categoryId = (int) $row['category_id'];
            $categoryTitle = (string) $row['category_title'];
            $periodLabel = (string) $row['period_label'];
            $revenue = (float) $row['revenue'];

            if (!isset($categories[$categoryId])) {
                $categories[$categoryId] = [
                    'categoryId' => $categoryId,
                    'categoryTitle' => $categoryTitle,
                    'points' => [],
                ];
            }

            $categories[$categoryId]['points'][$periodLabel] = $revenue;
        }

        $series = [];
        foreach ($categories as $category) {
            $points = [];
            foreach ($periods as $period) {
                $points[] = new SalesHistogramPointDto(
                    $period['label'],
                    $period['key'],
                    (float) ($category['points'][$period['label']] ?? 0.0)
                );
            }

            $series[] = new SalesCategorySeriesDto(
                $category['categoryId'],
                $category['categoryTitle'],
                $points
            );
        }

        return $series;
    }

    /**
     * @return SalesCategoryShareDto[]
     */
    public function categoryShare(?SalesPeriodFilterDto $filter = null): array
    {
        [$startDate, $endDate] = $this->resolveBounds($filter, 'weekly');
        $rows = $this->ordersRepository->fetchCategoryShare($startDate, $endDate);

        $totalRevenue = array_reduce($rows, static fn (float $carry, array $row): float => $carry + (float) $row['revenue'], 0.0);

        if ($totalRevenue <= 0) {
            return [];
        }

        return array_map(
            static function (array $row) use ($totalRevenue): SalesCategoryShareDto {
                $revenue = (float) $row['revenue'];

                return new SalesCategoryShareDto(
                    (int) $row['category_id'],
                    (string) $row['category_title'],
                    $revenue,
                    round(($revenue / $totalRevenue) * 100, 2)
                );
            },
            $rows
        );
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return SalesHistogramPointDto[]
     */
    private function buildHistogramPoints(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate, array $rows, string $granularity): array
    {
        $rowsByLabel = [];
        foreach ($rows as $row) {
            $rowsByLabel[(string) $row['period_label']] = $row;
        }

        $points = [];
        foreach ($this->buildPeriods($startDate, $endDate, $granularity) as $period) {
            $row = $rowsByLabel[$period['label']] ?? null;

            $points[] = new SalesHistogramPointDto(
                $period['label'],
                $period['key'],
                (float) ($row['total_revenue'] ?? 0.0),
                (int) ($row['orders_count'] ?? 0)
            );
        }

        return $points;
    }

    /**
     * @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable}
     */
    private function resolveBounds(?SalesPeriodFilterDto $filter, string $mode): array
    {
        if ($filter?->from !== null && $filter?->to !== null) {
            try {
                return [
                    new \DateTimeImmutable($filter->from),
                    new \DateTimeImmutable($filter->to),
                ];
            } catch (\Exception $exception) {
                throw new \InvalidArgumentException('Période invalide.', previous: $exception);
            }
        }

        $today = new \DateTimeImmutable('today');

        return match ($mode) {
            'daily' => [$today->modify('-6 days'), $today],
            'weekly' => [$today->modify('monday this week')->modify('-4 weeks'), $today],
            default => [$today->modify('-6 days'), $today],
        };
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    private function buildPeriods(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate, string $granularity): array
    {
        $periods = [];

        if ($granularity === 'week') {
            $cursor = $startDate->modify('monday this week');
            $limit = $endDate->modify('monday this week');

            while ($cursor <= $limit) {
                $label = sprintf('%s-W%s', $cursor->format('o'), $cursor->format('W'));
                $periods[] = [
                    'key' => $cursor->format('oW'),
                    'label' => $label,
                ];
                $cursor = $cursor->modify('+1 week');
            }

            return $periods;
        }

        $cursor = $startDate;
        while ($cursor <= $endDate) {
            $label = $cursor->format('Y-m-d');
            $periods[] = [
                'key' => $label,
                'label' => $label,
            ];
            $cursor = $cursor->modify('+1 day');
        }

        return $periods;
    }
}
