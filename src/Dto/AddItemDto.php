<?php

namespace App\Dto;

readonly class AddItemDto
{
    public function __construct(
        public int $productId,
        public int $quantity
    ) {}
}
