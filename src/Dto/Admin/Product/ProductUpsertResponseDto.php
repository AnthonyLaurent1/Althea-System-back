<?php

namespace App\Dto\Admin\Product;

readonly class ProductUpsertResponseDto
{
    public function __construct(
        public string $message,
        public AdminProductDetailDto $product,
    ) {
    }
}
