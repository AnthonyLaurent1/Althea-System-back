<?php
namespace App\Dto;

class UpdateItemQuantityDto
{
    /**
     * @param array<int, array{itemId:int, quantity:int}> $items
     */
    public function __construct(
        public array $items
    ) {}
}
