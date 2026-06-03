<?php

namespace App\Dto\Admin\Product;

readonly class CreateProductDto
{
    /**
     * @param array<string, array<string, mixed>> $translations
     */
    public function __construct(
        public string $title,
        public string $description,
        public string $price,
        public string $pictureUrl,
        public int $categoryId,
        public bool $isPublished,
        public string $powerSupplyType,
        public string $medicalDomain,
        public bool $isPortable,
        public bool $isOneTimeUse,
        public int $inStock,
        public array $translations = [],
    ) {
    }
}
