<?php

namespace App\Dto;

readonly class CategoryDto
{
    /**
     * @param ProductDto[] $products
     */
    public function __construct(
        public int $id,
        public string $title,
        public ?string $pictureUrl,
        public array $products = []
    ) {}
}
