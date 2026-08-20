<?php

namespace App\Infrastructure\Panfu\Repositories;

use App\Domain\Panfu\Repositories\LandingPageRepository;

class StaticLandingPageRepository implements LandingPageRepository
{
    public function getHomePage(string $locale): array
    {
        $page = $this->pages()[$locale] ?? $this->pages()['pl'];
        $page['assets'] = $this->assets();

        return $page;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function pages(): array
    {
        return [
            'pl' => [
                'meta' => [
                    'title' => 'Odkryj wirtualny świat pand - Panfu.me',
                    'description' => 'Rozmawiaj z przyjaciółmi, udekoruj domek na drzewie, adoptuj zwierzęta, przeżywaj przygody i graj w minigry.',
                ],
                'navigation' => [
                    ['key' => 'home', 'label' => 'Strona główna', 'route' => 'home', 'active' => true],
                    ['key' => 'blog', 'label' => 'Blog', 'route' => 'blog.index'],
                    ['key' => 'language', 'label' => 'Język', 'href' => '#'],
                    ['key' => 'register', 'label' => 'Rejestracja', 'route' => 'register'],
                    ['key' => 'login', 'label' => 'Zaloguj się', 'route' => 'login'],
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
                        'href' => '/blog',
                    ],
                ],
                'about' => [
                    'title' => 'Czym jest Panfu.me?',
                    'intro' => 'Panfu.me przywraca klasyczny wirtualny świat pand.',
                    'points' => [
                        '100% darmowe',
                        'Regularne aktualizacje',
                        'Bezpieczny i moderowany czat',
                        'Ciągle rosnąca społeczność',
                        'Najbardziej popularny fanowski serwer Panfu od 2016 roku',
                    ],
                    'button' => [
                        'label' => 'Dowiedz się więcej',
                        'route' => 'team',
                    ],
                ],
                'footer' => [
                    'copyright' => '© 2016-2026 Panfu.me. Wszystkie prawa zastrzeżone.',
                    'disclaimer' => 'Panfu.me nie jest powiązane ani wspierane przez Goodbeans GmbH.',
                    'links' => [
                        ['label' => 'Preferencje plików cookie', 'href' => '#'],
                        ['label' => 'Zespół Panfu', 'route' => 'team'],
                        ['label' => 'Oloko', 'href' => '#'],
                        ['label' => 'Status', 'href' => '#'],
                    ],
                    'legalLinks' => [
                        ['label' => 'Imprint', 'href' => '#'],
                        ['label' => 'Privacy Policy', 'href' => '#'],
                        ['label' => 'Terms of Service', 'href' => '#'],
                    ],
                ],
                'account' => [
                    'label' => 'Moje konto',
                    'settings' => 'Ustawienia konta',
                    'logout' => 'Wyloguj się',
                    'greeting' => 'Witaj, :name!',
                ],
            ],
            'en' => [
                'meta' => [
                    'title' => 'Discover the virtual panda world - Panfu.me',
                    'description' => 'Chat with friends, decorate your tree house, adopt pets, explore adventures and play minigames.',
                ],
                'navigation' => [
                    ['key' => 'home', 'label' => 'Home', 'route' => 'home', 'active' => true],
                    ['key' => 'blog', 'label' => 'Blog', 'route' => 'blog.index'],
                    ['key' => 'language', 'label' => 'Language', 'href' => '#'],
                    ['key' => 'register', 'label' => 'Registration', 'route' => 'register'],
                    ['key' => 'login', 'label' => 'Log in', 'route' => 'login'],
                ],
                'hero' => [
                    'playersOnline' => (int) config('panfu.homepage.players_online'),
                    'features' => [
                        ['icon' => 'games', 'text' => 'Discover and try great games'],
                        ['icon' => 'friends', 'text' => 'Meet friends and chat safely'],
                        ['icon' => 'style', 'text' => 'Style your panda and have fun'],
                        ['icon' => 'pets', 'text' => 'Adopt cute pets and care for them'],
                    ],
                    'cta' => [
                        'label' => 'Register now!',
                        'route' => 'register',
                    ],
                ],
                'news' => [
                    'eyebrow' => 'What is new?',
                    'title' => 'Panfu 2025 year recap',
                    'excerpt' => 'Hello Pandas! The year is coming to an end and it was a rather calm one for Panfu. Time went by very quickly and we did not manage to finish every goal we had planned. M...',
                    'button' => [
                        'label' => 'Go to the blog',
                        'href' => '/blog',
                    ],
                ],
                'about' => [
                    'title' => 'What is Panfu.me?',
                    'intro' => 'Panfu.me brings back the classic virtual world of pandas.',
                    'points' => [
                        '100% free',
                        'Regular updates',
                        'Safe and moderated chat',
                        'A growing community',
                        'The most popular Panfu fan server since 2016',
                    ],
                    'button' => [
                        'label' => 'Learn more',
                        'route' => 'team',
                    ],
                ],
                'footer' => [
                    'copyright' => '© 2016-2026 Panfu.me. All rights reserved.',
                    'disclaimer' => 'Panfu.me is not affiliated with or endorsed by Goodbeans GmbH.',
                    'links' => [
                        ['label' => 'Cookie preferences', 'href' => '#'],
                        ['label' => 'Panfu team', 'route' => 'team'],
                        ['label' => 'Oloko', 'href' => '#'],
                        ['label' => 'Status', 'href' => '#'],
                    ],
                    'legalLinks' => [
                        ['label' => 'Imprint', 'href' => '#'],
                        ['label' => 'Privacy Policy', 'href' => '#'],
                        ['label' => 'Terms of Service', 'href' => '#'],
                    ],
                ],
                'account' => [
                    'label' => 'My account',
                    'settings' => 'Account settings',
                    'logout' => 'Log out',
                    'greeting' => 'Hello, :name!',
                ],
            ],
            'de' => [
                'meta' => [
                    'title' => 'Entdecke die virtuelle Panda-Welt - Panfu.me',
                    'description' => 'Chatte mit Freunden, richte dein Baumhaus ein, adoptiere Haustiere, erlebe Abenteuer und spiele Minispiele.',
                ],
                'navigation' => [
                    ['key' => 'home', 'label' => 'Startseite', 'route' => 'home', 'active' => true],
                    ['key' => 'blog', 'label' => 'Blog', 'route' => 'blog.index'],
                    ['key' => 'language', 'label' => 'Sprache', 'href' => '#'],
                    ['key' => 'register', 'label' => 'Registrieren', 'route' => 'register'],
                    ['key' => 'login', 'label' => 'Einloggen', 'route' => 'login'],
                ],
                'hero' => [
                    'playersOnline' => (int) config('panfu.homepage.players_online'),
                    'features' => [
                        ['icon' => 'games', 'text' => 'Entdecke tolle Spiele und probiere sie aus'],
                        ['icon' => 'friends', 'text' => 'Triff Freunde und chatte sicher'],
                        ['icon' => 'style', 'text' => 'Style deinen Panda und hab Spaß'],
                        ['icon' => 'pets', 'text' => 'Adoptiere süße Tiere und kümmere dich um sie'],
                    ],
                    'cta' => [
                        'label' => 'Jetzt registrieren!',
                        'route' => 'register',
                    ],
                ],
                'news' => [
                    'eyebrow' => 'Was ist neu?',
                    'title' => 'Panfu Jahresrückblick 2025',
                    'excerpt' => 'Hallo Pandas! Das Jahr neigt sich dem Ende zu und es war für Panfu eher ruhig. Die Zeit verging sehr schnell und wir konnten nicht alle Ziele umsetzen, die wir uns vorgenommen hatten. M...',
                    'button' => [
                        'label' => 'Zum Blog',
                        'href' => '/blog',
                    ],
                ],
                'about' => [
                    'title' => 'Was ist Panfu.me?',
                    'intro' => 'Panfu.me bringt die klassische virtuelle Panda-Welt zurück.',
                    'points' => [
                        '100% kostenlos',
                        'Regelmäßige Updates',
                        'Sicherer und moderierter Chat',
                        'Eine stetig wachsende Community',
                        'Der beliebteste Panfu-Fanserver seit 2016',
                    ],
                    'button' => [
                        'label' => 'Mehr erfahren',
                        'route' => 'team',
                    ],
                ],
                'footer' => [
                    'copyright' => '© 2016-2026 Panfu.me. Alle Rechte vorbehalten.',
                    'disclaimer' => 'Panfu.me ist weder mit Goodbeans GmbH verbunden noch wird es von Goodbeans GmbH unterstützt.',
                    'links' => [
                        ['label' => 'Cookie-Einstellungen', 'href' => '#'],
                        ['label' => 'Panfu-Team', 'route' => 'team'],
                        ['label' => 'Oloko', 'href' => '#'],
                        ['label' => 'Status', 'href' => '#'],
                    ],
                    'legalLinks' => [
                        ['label' => 'Impressum', 'href' => '#'],
                        ['label' => 'Datenschutzerklärung', 'href' => '#'],
                        ['label' => 'Nutzungsbedingungen', 'href' => '#'],
                    ],
                ],
                'account' => [
                    'label' => 'Mein Konto',
                    'settings' => 'Kontoeinstellungen',
                    'logout' => 'Ausloggen',
                    'greeting' => 'Hallo, :name!',
                ],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function assets(): array
    {
        return [
            'logo' => 'panfu-logo-BkIF66dU.svg',
            'heroVideo' => 'home-C5LnHByY.webm',
            'heroVideoSafari' => 'home-C5LnHByY-hevc.mov',
            'grasslands' => 'grasslands-1200x630-URVD6tIa.png',
            'homeBoard' => 'home-board-D2ETOOE4.png',
            'headerIsland' => 'header-island-DHGB7_8-.svg',
        ];
    }
}
