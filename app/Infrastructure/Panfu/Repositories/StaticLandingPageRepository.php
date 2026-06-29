<?php

namespace App\Infrastructure\Panfu\Repositories;

use App\Domain\Panfu\Repositories\LandingPageRepository;

class StaticLandingPageRepository implements LandingPageRepository
{
    public function getHomePage(): array
    {
        return [
            'meta' => [
                'title' => 'Discover the virtual world of pandas - Panfu.me',
                'description' => 'Chat with friends, decorate your treehouse, adopt pets, go on adventures and play minigames.',
            ],
            'navigation' => [
                ['label' => 'Home', 'route' => 'home', 'active' => true],
                ['label' => 'Blog', 'href' => '#blog'],
                [
                    'label' => 'Language',
                    'href' => '#',
                    'children' => [
                        ['label' => 'Deutsch', 'href' => '#'],
                        ['label' => 'English', 'href' => '#', 'active' => true],
                        ['label' => 'Polski', 'href' => '#'],
                    ],
                ],
                ['label' => 'Registration', 'route' => 'register'],
                ['label' => 'Login', 'route' => 'login'],
            ],
            'hero' => [
                'playersOnline' => (int) config('panfu.homepage.players_online'),
                'features' => [
                    ['icon' => 'games', 'text' => 'Discover & try out great games'],
                    ['icon' => 'friends', 'text' => 'Meet friends & chat safely'],
                    ['icon' => 'style', 'text' => 'Style your panda & have fun'],
                    ['icon' => 'pets', 'text' => 'Adopt cute pets & care for them'],
                ],
                'cta' => [
                    'label' => 'Register now!',
                    'route' => 'register',
                ],
            ],
            'news' => [
                'eyebrow' => "What's new?",
                'title' => 'Panfu Year in Review 2025',
                'excerpt' => 'Hello Pandas! The year is almost over, and overall it has been rather quiet for Panfu. Time passed quickly, and we were not able to implement all the goals we had set for ourselves...',
                'button' => [
                    'label' => 'Go to the blog',
                    'href' => '#blog',
                ],
            ],
            'about' => [
                'title' => 'What is Panfu.me?',
                'intro' => 'Panfu.me brings back the classic virtual world of pandas.',
                'points' => [
                    '100% free',
                    'Regular updates',
                    'Safe & moderated chat',
                    'Constantly growing community',
                    'The most popular fan-made Panfu server since 2016',
                ],
                'button' => [
                    'label' => 'Learn more',
                    'href' => '#',
                ],
            ],
            'footer' => [
                'copyright' => '© 2016-2026 Panfu.me. All rights reserved.',
                'disclaimer' => 'Panfu.me is not affiliated with or endorsed by Goodbeans GmbH.',
                'links' => [
                    ['label' => 'Cookie Preferences', 'href' => '#'],
                    ['label' => 'Panfu Team', 'href' => '#'],
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
