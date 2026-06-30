<?php

namespace App\Domain\Panfu\Repositories;

interface LandingPageRepository
{
    /**
     * @return array<string, mixed>
     */
    public function getHomePage(string $locale): array;
}
