<?php

namespace App\Infrastructure\Panfu\Repositories;

use App\Domain\Panfu\Repositories\LandingPageRepository;

class StaticLandingPageRepository implements LandingPageRepository
{
    public function getHomePage(): array
    {
        return [
            'meta' => [
                'title' => 'Odkryj wirtualny świat pand - Panfu.me',
                'description' => 'Rozmawiaj ze znajomymi, dekoruj swoją własną chatkę na drzewie, adoptuj zwierzęta, wyruszaj na przygody i graj w minigry.',
            ],
            'navigation' => [
                ['label' => 'Strona główna', 'route' => 'home'],
                ['label' => 'Blog', 'href' => '#blog'],
                ['label' => 'Język', 'href' => '#language'],
                ['label' => 'Rejestracja', 'route' => 'register', 'variant' => 'primary'],
                ['label' => 'Zaloguj się', 'route' => 'login', 'variant' => 'secondary'],
            ],
            'hero' => [
                'playersOnline' => (int) config('panfu.homepage.players_online'),
                'features' => [
                    ['icon' => 'games', 'text' => 'Odkrywaj i wypróbuj świetne gry'],
                    ['icon' => 'friends', 'text' => 'Spotykaj się z przyjaciółmi i rozmawiaj bezpiecznie'],
                    ['icon' => 'style', 'text' => 'Stylizuj swoją pandę i baw się dobrze'],
                    ['icon' => 'pets', 'text' => 'Adoptuj urocze zwierzęta i dbaj o nie'],
                ],
                'cta' => [
                    'label' => 'Zarejestruj się teraz!',
                    'route' => 'register',
                ],
            ],
            'news' => [
                'eyebrow' => 'Co nowego?',
                'title' => 'Podsumowanie roku Panfu 2025',
                'excerpt' => 'Witajcie, Pandy! Rok dobiega końca i ogólnie był on raczej spokojny dla Panfu. Czas minął bardzo szybko i nie udało nam się zrealizować wszystkich celów, które sobie założyliśmy.',
                'button' => [
                    'label' => 'Przejdź do bloga',
                    'href' => '#blog',
                ],
            ],
            'about' => [
                'title' => 'Czym jest Panfu.me?',
                'intro' => 'Panfu.me przywraca klasyczny wirtualny świat pand: rozmowy, minigry, przygody, chatki na drzewie i wspólną zabawę w bezpiecznej społeczności.',
                'points' => [
                    '100% darmowe',
                    'Regularne aktualizacje',
                    'Bezpieczny i moderowany czat',
                    'Ciągle rosnąca społeczność',
                    'Najbardziej popularny fanowski serwer Panfu od 2016 roku',
                ],
            ],
            'footer' => [
                'copyright' => 'Panfu local preservation project',
                'links' => [
                    ['label' => 'Strona główna', 'route' => 'home'],
                    ['label' => 'Gra', 'route' => 'play'],
                    ['label' => 'Blog', 'href' => '#blog'],
                ],
            ],
            'assets' => [
                'logo' => 'panfu-logo-BkIF66dU.svg',
                'heroVideo' => 'home-C5LnHByY.webm',
                'grasslands' => 'grasslands-1200x630-URVD6tIa.png',
                'homeBoard' => 'home-board-D2ETOOE4.png',
                'headerIsland' => 'header-island-DHGB7_8-.svg',
            ],
        ];
    }
}
