<?php

namespace App\Dto\Admin\Product;

readonly class AdminProductListQueryDto
{
    public function __construct(
        public int $page = 1,
        public int $limit = 20,
        public string $sortBy = 'id',
        public string $sortDirection = 'DESC',
        public ?string $search = null,
        public ?int $categoryId = null,
        public ?bool $isPublished = null,
    ) {
    }
}
