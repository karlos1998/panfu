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
                'description' => 'Rozmawiaj z przyjaciółmi, udekoruj domek na drzewie, adoptuj zwierzęta, przeżywaj przygody i graj w minigry.',
            ],
            'navigation' => [
                ['label' => 'Strona główna', 'route' => 'home', 'active' => true],
                ['label' => 'Blog', 'href' => '#blog'],
                [
                    'label' => 'Język',
                    'href' => '#',
                    'children' => [
                        ['label' => 'Deutsch', 'href' => '#'],
                        ['label' => 'English', 'href' => '#'],
                        ['label' => 'Polski', 'href' => '#', 'active' => true],
                    ],
                ],
                ['label' => 'Rejestracja', 'route' => 'register'],
                ['label' => 'Zaloguj się', 'route' => 'login'],
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
                'excerpt' => 'Witajcie, Pandy! Rok dobiega końca i ogólnie był on raczej spokojny dla Panfu. Czas minął bardzo szybko i nie udało nam się zrealizować wszystkich celów, które sobie założyliśmy. M...',
                'button' => [
                    'label' => 'Przejdź do bloga',
                    'href' => '#blog',
                ],
            ],
            'about' => [
                'title' => 'Czym jest Panfu.me?',
                'intro' => 'Panfu.me brings back the classic virtual world of pandas.',
                'points' => [
                    '100% darmowe',
                    'Regularne aktualizacje',
                    'Bezpieczny i moderowany czat',
                    'Ciągle rosnąca społeczność',
                    'Najbardziej popularny fanowski serwer Panfu od 2016 roku',
                ],
                'button' => [
                    'label' => 'Dowiedz się więcej',
                    'href' => '#',
                ],
            ],
            'footer' => [
                'copyright' => '© 2016-2026 Panfu.me. Wszystkie prawa zastrzeżone.',
                'disclaimer' => 'Panfu.me nie jest powiązane ani wspierane przez Goodbeans GmbH.',
                'links' => [
                    ['label' => 'Preferencje plików cookie', 'href' => '#'],
                    ['label' => 'Zespół Panfu', 'href' => '#'],
                    ['label' => 'Oloko', 'href' => '#'],
                    ['label' => 'Status', 'href' => '#'],
                ],
                'legalLinks' => [
                    ['label' => 'Imprint', 'href' => '#'],
                    ['label' => 'Privacy Policy', 'href' => '#'],
                    ['label' => 'Terms of Service', 'href' => '#'],
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
