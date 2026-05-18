<?php

namespace App\Dto\Admin\Product;

readonly class AdminProductDetailDto
{
    /**
     * @param array<int, array<string, mixed>> $discounts
     */
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
        public array $category,
        public array $discounts = [],
    ) {
    }
}
