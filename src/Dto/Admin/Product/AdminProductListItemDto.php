<?php

namespace App\Dto\Admin\Product;

readonly class AdminProductListItemDto
{
    public function __construct(
        public int $id,
        public string $title,
        public string $description,
        public string $price,
        public string $pictureUrl,
        public int $inStock,
        public bool $isPublished,
        public string $powerSupplyType,
        public string $medicalDomain,
        public bool $isPortable,
        public bool $isOneTimeUse,
        public int $categoryId,
        public string $categoryTitle,
        public ?float $activeDiscountPercentage = null,
        public ?string $activeDiscountEndDate = null,
    ) {
    }
}
