<?php

namespace App\Dto\Admin\Dashboard;

readonly class SalesHistogramPointDto
{
    public function __construct(
        public string $label,
        public string $periodKey,
        public float $value,
        public int $ordersCount = 0,
    ) {
    }
}
