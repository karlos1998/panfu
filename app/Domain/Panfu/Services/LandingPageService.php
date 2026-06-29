<?php

namespace App\Domain\Panfu\Services;

use App\Domain\Panfu\Repositories\LandingPageRepository;
use Illuminate\Support\Facades\Route;

class LandingPageService
{
    public function __construct(private readonly LandingPageRepository $landingPages) {}

    /**
     * @return array<string, mixed>
     */
    public function getHomePage(): array
    {
        $page = $this->landingPages->getHomePage();

        $page['navigation'] = array_map(
            fn (array $item): array => $this->withHref($item),
            $page['navigation'],
        );

        $page['hero']['cta'] = $this->withHref($page['hero']['cta']);
        $page['about']['button'] = $this->withHref($page['about']['button']);
        $page['footer']['links'] = array_map(
            fn (array $item): array => $this->withHref($item),
            $page['footer']['links'],
        );
        $page['footer']['legalLinks'] = array_map(
            fn (array $item): array => $this->withHref($item),
            $page['footer']['legalLinks'],
        );
        $page['assets'] = $this->assetUrls($page['assets']);

        return $page;
    }

    /**
     * @param  array<string, string>  $assets
     * @return array<string, string>
     */
    private function assetUrls(array $assets): array
    {
        $basePath = trim((string) config('panfu.assets.base_path'), '/');

        return array_map(
            fn (string $asset): string => asset($basePath.'/'.$asset),
            $assets,
        );
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function withHref(array $item): array
    {
        if (isset($item['route']) && Route::has($item['route'])) {
            $item['href'] = route($item['route'], absolute: false);
        }

        if (isset($item['children']) && is_array($item['children'])) {
            $item['children'] = array_map(
                fn (array $child): array => $this->withHref($child),
                $item['children'],
            );
        }

        unset($item['route']);

        return $item;
    }
}
