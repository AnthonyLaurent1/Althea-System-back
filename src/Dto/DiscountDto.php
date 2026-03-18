<?php

namespace App\Dto;

readonly class DiscountDto
{
    public function __construct(
        public int $id,
        public int $percentage,
        public string $startDate,
        public string $endDate
    ) {}
}
