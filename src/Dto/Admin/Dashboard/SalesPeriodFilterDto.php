<?php

namespace App\Dto\Admin\Dashboard;

readonly class SalesPeriodFilterDto
{
    public function __construct(
        public ?string $from = null,
        public ?string $to = null,
    ) {
    }
}
