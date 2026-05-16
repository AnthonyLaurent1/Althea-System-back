<?php

namespace App\Dto;

readonly class CategorySimpleDto
{
    public function __construct(
        public int $id,
        public string $title,
        public ?string $pictureUrl
    ) {}
}
