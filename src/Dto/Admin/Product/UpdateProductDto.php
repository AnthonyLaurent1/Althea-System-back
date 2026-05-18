<?php

namespace App\Dto\Admin\Product;

readonly class UpdateProductDto
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $price = null,
        public ?string $pictureUrl = null,
        public ?int $categoryId = null,
        public ?bool $isPublished = null,
        public ?string $powerSupplyType = null,
        public ?string $medicalDomain = null,
        public ?bool $isPortable = null,
        public ?bool $isOneTimeUse = null,
        public ?int $inStock = null,
    ) {
    }
}
