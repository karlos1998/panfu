<?php

namespace App\Http\Controllers\Panfu;

use App\Domain\Panfu\Services\LocaleService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class LocaleController extends Controller
{
    public function __construct(private readonly LocaleService $locales) {}

    public function __invoke(string $locale): RedirectResponse
    {
        $locale = $this->locales->normalize($locale);

        abort_if($locale === null, 404);

        $this->locales->apply($locale);

        return Redirect::back(303)->withCookie($this->locales->makeCookie($locale));
    }
}
