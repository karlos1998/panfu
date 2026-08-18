<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::query()->whereNotNull('email_verified_at')->first() ?? User::query()->first();

        if (! $author) {
            return;
        }

        $announcements = BlogCategory::query()->where('slug', 'announcements')->firstOrFail();
        $community = BlogCategory::query()->where('slug', 'community')->firstOrFail();

        $recap = BlogPost::query()->updateOrCreate(
            ['slug' => 'podsumowanie-roku-panfu-2025'],
            [
                'author_id' => $author->id,
                'blog_category_id' => $announcements->id,
                'title' => 'Podsumowanie roku Panfu 2025',
                'body' => "**Witajcie, Pandy!**\n\nRok dobiega końca i choć był spokojniejszy, w Panfu sporo się wydarzyło. Uporządkowaliśmy stronę, grę i panel administracyjny, a przed nami kolejne przygody.\n\n## Najważniejsze zmiany\n\n- szybsze ładowanie świata i katalogu,\n- odświeżone ustawienia konta,\n- lepsze narzędzia do opieki nad społecznością,\n- powrót regularnych informacji na blogu.\n\n## Ostatnie słowa\n\nDziękujemy wszystkim Pandom, które wracają na wyspę i tworzą jej społeczność. Do zobaczenia w grze!",
                'published_at' => now()->subDays(4),
            ],
        );

        BlogPost::query()->updateOrCreate(
            ['slug' => 'blog-wraca-na-wyspe'],
            [
                'author_id' => $author->id,
                'blog_category_id' => $community->id,
                'title' => 'Blog wraca na wyspę!',
                'body' => "Cześć, Pandziaki!\n\nOd teraz znajdziecie tutaj **ogłoszenia, konkursy, poradniki i aktualizacje Panfu**. Każda zalogowana panda może też dołączyć do rozmowy w komentarzach.\n\n> Zaglądajcie regularnie — szykujemy następne wiadomości.",
                'published_at' => now()->subDay(),
            ],
        );

        $recap->comments()->firstOrCreate(
            ['user_id' => $author->id, 'body' => 'Miło zobaczyć blog z powrotem!'],
            ['author_name' => $author->name, 'approved_at' => now()],
        );
    }
}
