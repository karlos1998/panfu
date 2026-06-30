<?php

namespace App\Http\Middleware;

use App\Domain\Panfu\Services\LandingPageService;
use App\Domain\Panfu\Services\LocaleService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $locales = app(LocaleService::class);

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'panfu' => [
                'locale' => [
                    'current' => $locales->current(),
                    'languageId' => $locales->currentLanguageId(),
                    'languages' => $locales->languageLinks(),
                ],
                'chrome' => app(LandingPageService::class)->getChrome(),
            ],
        ];
    }
}
