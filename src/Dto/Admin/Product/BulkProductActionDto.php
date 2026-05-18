<?php

namespace App\Dto\Admin\Product;

readonly class BulkProductActionDto
{
    /**
     * @param int[] $productIds
     */
    public function __construct(
        public array $productIds,
        public string $action,
        public ?int $discountPercentage = null,
        public ?string $discountStartDate = null,
        public ?string $discountEndDate = null,
        public ?bool $isPublished = null,
    ) {
    }
}
