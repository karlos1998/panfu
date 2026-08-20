<?php

namespace App\Domain\Panfu\Services;

use App\Domain\Panfu\Repositories\LandingPageRepository;
use App\Domain\Servers\GameServerService;
use Illuminate\Support\Facades\Route;

class LandingPageService
{
    public function __construct(
        private readonly LandingPageRepository $landingPages,
        private readonly LocaleService $locales,
        private readonly GameServerService $gameServers,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getHomePage(): array
    {
        $page = $this->landingPages->getHomePage($this->locales->current());

        $page['navigation'] = $this->navigationFrom($page);
        $page['hero']['playersOnline'] = $this->gameServers->onlinePlayerCount();

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
     * @return array<string, mixed>
     */
    public function getChrome(): array
    {
        $page = $this->landingPages->getHomePage($this->locales->current());

        return [
            'navigation' => $this->navigationFrom($page),
            'footer' => [
                ...$page['footer'],
                'links' => array_map(
                    fn (array $item): array => $this->withHref($item),
                    $page['footer']['links'],
                ),
                'legalLinks' => array_map(
                    fn (array $item): array => $this->withHref($item),
                    $page['footer']['legalLinks'],
                ),
            ],
            'account' => $page['account'],
        ];
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
        if (($item['key'] ?? null) === 'language') {
            $item['children'] = $this->locales->navigationLinks();
        }

        if (isset($item['route']) && Route::has($item['route'])) {
            $item['href'] = route($item['route'], absolute: false);
        }

        if (isset($item['children']) && is_array($item['children'])) {
            $item['children'] = array_map(
                fn (array $child): array => $this->withHref($child),
                $item['children'],
            );
        }

        unset($item['key'], $item['route']);

        return $item;
    }

    /**
     * @param  array<string, mixed>  $page
     * @return array<int, array<string, mixed>>
     */
    private function navigationFrom(array $page): array
    {
        return array_map(
            fn (array $item): array => $this->withHref($item),
            $page['navigation'],
        );
    }
}
