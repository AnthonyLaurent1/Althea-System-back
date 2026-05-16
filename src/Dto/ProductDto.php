<?php

namespace App\Dto;

readonly class ProductDto
{
    /**
     * @param DiscountDto[] $discounts
     */
    public function __construct(
        public int $id,
        public string $title,
        public string $description,
        public string $price,
        public string $pictureUrl,
        public int $inStock,
        public bool $isPublished,
        public bool $isPortable,
        public bool $isOneTimeUse,
        public string $powerSupplyType,
        public string $medicalDomain,
        public CategorySimpleDto $category,
        public array $discounts = []
    ) {}
}
