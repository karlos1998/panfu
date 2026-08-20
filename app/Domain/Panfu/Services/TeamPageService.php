<?php

namespace App\Domain\Panfu\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TeamPageService
{
    public function __construct(
        private readonly PandaAvatarService $avatars,
        private readonly LocaleService $locales,
    ) {}

    /** @return array<string, mixed> */
    public function getPage(): array
    {
        $content = $this->content()[$this->locales->current()] ?? $this->content()['pl'];

        return [
            ...$content,
            'groups' => [
                $this->group($content['groups']['administrators'], 'administrators', $this->byRole(UserRole::Admin)),
                $this->group($content['groups']['moderators'], 'moderators', $this->byRole(UserRole::Moderator)),
                $this->group($content['groups']['sheriffs'], 'sheriffs', $this->sheriffs()),
            ],
        ];
    }

    /** @return Collection<int, User> */
    private function byRole(UserRole $role): Collection
    {
        return $this->teamQuery()
            ->where('role', $role->value)
            ->orderBy('name')
            ->get();
    }

    /** @return Collection<int, User> */
    private function sheriffs(): Collection
    {
        return $this->teamQuery()
            ->where('sheriff', true)
            ->orderBy('name')
            ->get();
    }

    private function teamQuery(): Builder
    {
        return User::query()->select(['id', 'name', 'role', 'sheriff', 'current_gameserver']);
    }

    /**
     * @param  array{title: string, description: string, emptyMessage: string, memberLabel: string}  $content
     * @param  Collection<int, User>  $users
     * @return array<string, mixed>
     */
    private function group(array $content, string $key, Collection $users): array
    {
        return [
            'key' => $key,
            'title' => $content['title'],
            'description' => $content['description'],
            'emptyMessage' => $content['emptyMessage'],
            'members' => $users->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'roleLabel' => $content['memberLabel'],
                'online' => ($user->current_gameserver ?? 0) > 0,
                'avatar' => $this->avatars->forUser($user),
            ])->values(),
        ];
    }

    /** @return array<string, array<string, mixed>> */
    private function content(): array
    {
        return [
            'pl' => [
                'meta' => [
                    'title' => 'Zespół Panfu - Panfu.me',
                    'description' => 'Poznaj administratorów, moderatorów i szeryfów społeczności Panfu.me.',
                ],
                'groups' => [
                    'administrators' => [
                        'title' => 'Administratorzy',
                        'description' => 'Zarządzają projektem i dbają o zespół.',
                        'emptyMessage' => 'Tu jeszcze nic nie ma.',
                        'memberLabel' => 'Administrator',
                    ],
                    'moderators' => [
                        'title' => 'Moderatorzy',
                        'description' => 'Moderują czat i odpowiadają na Twoje pytania.',
                        'emptyMessage' => 'Tu jeszcze nic nie ma.',
                        'memberLabel' => 'Moderator',
                    ],
                    'sheriffs' => [
                        'title' => 'Szeryfowie',
                        'description' => 'Wspierają moderatorów w ich pracy.',
                        'emptyMessage' => 'Tu jeszcze nic nie ma.',
                        'memberLabel' => 'Szeryf',
                    ],
                ],
                'about' => [
                    'title' => 'O nas',
                    'paragraphs' => [
                        '<strong>Panfu.me</strong> zostało założone przez fanów w październiku 2016 roku i stało się od tego czasu najbardziej popularnym następcą Panfu.',
                        'Nasz zespół składa się z wolontariuszy, którzy pomagają nam w moderacji i utrzymują dobrą zabawę graczy.',
                    ],
                ],
                'joining' => [
                    'title' => 'Jak zostać częścią zespołu?',
                    'paragraphs' => [
                        'Jeśli uznamy, że byłbyś dobrym dodatkiem do naszego zespołu, skontaktujemy się z Tobą.',
                    ],
                ],
            ],
            'en' => [
                'meta' => [
                    'title' => 'Panfu team - Panfu.me',
                    'description' => 'Meet the administrators, moderators and sheriffs of the Panfu.me community.',
                ],
                'groups' => [
                    'administrators' => ['title' => 'Administrators', 'description' => 'They manage the project and look after the team.', 'emptyMessage' => 'There is nothing here yet.', 'memberLabel' => 'Administrator'],
                    'moderators' => ['title' => 'Moderators', 'description' => 'They moderate the chat and answer your questions.', 'emptyMessage' => 'There is nothing here yet.', 'memberLabel' => 'Moderator'],
                    'sheriffs' => ['title' => 'Sheriffs', 'description' => 'They support the moderators in their work.', 'emptyMessage' => 'There is nothing here yet.', 'memberLabel' => 'Sheriff'],
                ],
                'about' => [
                    'title' => 'About us',
                    'paragraphs' => [
                        '<strong>Panfu.me</strong> was founded by fans in October 2016 and has since become the most popular successor to Panfu.',
                        'Our team is made up of volunteers who help us moderate and keep the game fun for everyone.',
                    ],
                ],
                'joining' => ['title' => 'How can I join the team?', 'paragraphs' => ['If we think you would be a good addition to our team, we will contact you.']],
            ],
            'de' => [
                'meta' => [
                    'title' => 'Panfu-Team - Panfu.me',
                    'description' => 'Lerne die Administratoren, Moderatoren und Sheriffs der Panfu.me-Community kennen.',
                ],
                'groups' => [
                    'administrators' => ['title' => 'Administratoren', 'description' => 'Sie verwalten das Projekt und kümmern sich um das Team.', 'emptyMessage' => 'Hier gibt es noch nichts.', 'memberLabel' => 'Administrator'],
                    'moderators' => ['title' => 'Moderatoren', 'description' => 'Sie moderieren den Chat und beantworten deine Fragen.', 'emptyMessage' => 'Hier gibt es noch nichts.', 'memberLabel' => 'Moderator'],
                    'sheriffs' => ['title' => 'Sheriffs', 'description' => 'Sie unterstützen die Moderatoren bei ihrer Arbeit.', 'emptyMessage' => 'Hier gibt es noch nichts.', 'memberLabel' => 'Sheriff'],
                ],
                'about' => [
                    'title' => 'Über uns',
                    'paragraphs' => [
                        '<strong>Panfu.me</strong> wurde im Oktober 2016 von Fans gegründet und ist seitdem der beliebteste Nachfolger von Panfu.',
                        'Unser Team besteht aus Freiwilligen, die uns bei der Moderation helfen und dafür sorgen, dass alle Spaß am Spiel haben.',
                    ],
                ],
                'joining' => ['title' => 'Wie werde ich Teil des Teams?', 'paragraphs' => ['Wenn wir glauben, dass du gut in unser Team passt, werden wir dich kontaktieren.']],
            ],
        ];
    }
}
