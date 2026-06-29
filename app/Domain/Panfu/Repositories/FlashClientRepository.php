<?php

namespace App\Domain\Panfu\Repositories;

interface FlashClientRepository
{
    /**
     * @return array<string, mixed>
     */
    public function getClient(): array;
}
