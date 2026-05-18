<?php

namespace App\Dto\Admin\Dashboard;

readonly class SalesCategoryShareDto
{
    public function __construct(
        public int $categoryId,
        public string $categoryTitle,
        public float $value,
        public float $percentage,
    ) {
    }
}
