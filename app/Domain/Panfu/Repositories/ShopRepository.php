<?php

namespace App\Domain\Panfu\Repositories;

interface ShopRepository
{
    /**
     * @return array<string, mixed>
     */
    public function getCatalogue(): array;
}
