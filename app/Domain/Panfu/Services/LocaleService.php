<?php

namespace App\Domain\Panfu\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

class LocaleService
{
    public function detect(Request $request): string
    {
        $cookieLocale = $this->normalize($request->cookie($this->cookieName()));

        if ($cookieLocale !== null) {
            return $cookieLocale;
        }

        $preferredLocale = $this->normalize(
            $request->getPreferredLanguage($this->supportedLocaleCodes()),
        );

        return $preferredLocale ?? $this->fallbackLocale();
    }

    public function apply(string $locale): string
    {
        $locale = $this->normalize($locale) ?? $this->fallbackLocale();

        App::setLocale($locale);

        return $locale;
    }

    public function current(): string
    {
        return $this->normalize(App::currentLocale()) ?? $this->fallbackLocale();
    }

    public function currentLanguageId(): string
    {
        return $this->languageId($this->current());
    }

    public function languageId(string $locale): string
    {
        $locale = $this->normalize($locale) ?? $this->fallbackLocale();

        return (string) config("panfu.localization.supported.{$locale}.id");
    }

    public function normalize(mixed $locale): ?string
    {
        if (! is_string($locale) || $locale === '') {
            return null;
        }

        $normalized = strtolower(str_replace('_', '-', $locale));
        $normalized = explode('-', $normalized, 2)[0];

        return in_array($normalized, $this->supportedLocaleCodes(), true)
            ? $normalized
            : null;
    }

    /**
     * @return array<int, array{code: string, id: string, label: string, active: bool, href: string}>
     */
    public function languageLinks(): array
    {
        $current = $this->current();

        return array_map(
            fn (string $locale): array => [
                'code' => $locale,
                'id' => $this->languageId($locale),
                'label' => $this->label($locale),
                'active' => $locale === $current,
                'href' => route('panfu.language', ['locale' => $locale], absolute: false),
            ],
            $this->supportedLocaleCodes(),
        );
    }

    /**
     * @return array<int, array{label: string, href: string, active: bool}>
     */
    public function navigationLinks(): array
    {
        return array_map(
            fn (array $language): array => [
                'label' => $language['label'],
                'href' => $language['href'],
                'active' => $language['active'],
            ],
            $this->languageLinks(),
        );
    }

    /**
     * @return array<int, string>
     */
    public function supportedLocaleCodes(): array
    {
        return array_keys((array) config('panfu.localization.supported', []));
    }

    public function makeCookie(string $locale): SymfonyCookie
    {
        return Cookie::make(
            $this->cookieName(),
            $this->normalize($locale) ?? $this->fallbackLocale(),
            60 * 24 * 365,
            '/',
            null,
            null,
            true,
            false,
            'lax',
        );
    }

    public function cookieName(): string
    {
        return (string) config('panfu.localization.cookie', 'panfu_locale');
    }

    private function label(string $locale): string
    {
        $locale = $this->normalize($locale) ?? $this->fallbackLocale();

        return (string) config("panfu.localization.supported.{$locale}.label");
    }

    private function fallbackLocale(): string
    {
        return $this->normalize(config('panfu.localization.fallback')) ?? 'en';
    }
}
