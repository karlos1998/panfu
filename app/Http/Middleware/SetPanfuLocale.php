<?php

namespace App\Http\Middleware;

use App\Domain\Panfu\Services\LocaleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPanfuLocale
{
    public function __construct(private readonly LocaleService $locales) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->locales->apply($this->locales->detect($request));

        return $next($request);
    }
}
