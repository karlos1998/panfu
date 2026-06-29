<?php

namespace App\Infrastructure\Panfu\Repositories;

use App\Domain\Panfu\Repositories\ShopRepository;
use Illuminate\Support\Facades\File;
use JsonException;

class JsonShopRepository implements ShopRepository
{
    /**
     * @return array<string, mixed>
     */
    public function getCatalogue(): array
    {
        $path = config('panfu.shop.catalogue_path');

        if (! is_string($path) || $path === '' || ! File::exists($path)) {
            return ['items' => []];
        }

        try {
            $catalogue = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return ['items' => []];
        }

        return is_array($catalogue) ? $catalogue : ['items' => []];
    }
}
