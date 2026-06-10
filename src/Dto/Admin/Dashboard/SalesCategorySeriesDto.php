<?php

namespace App\Dto\Admin\Dashboard;

readonly class SalesCategorySeriesDto
{
    /**
     * @param SalesHistogramPointDto[] $points
     */
    public function __construct(
        public int $categoryId,
        public string $categoryTitle,
        public array $points,
    ) {
    }
}
