<?php

namespace App\Application\Amf\Services;

use App\Application\Amf\AmfResponseFactory;
use App\Application\Amf\ValueObjectFactory;
use App\Infrastructure\Amf\TypedObject;
use App\Models\PlayerProfile;
use App\Models\User;

final class ProfileAmfService
{
    public function __construct(
        private readonly AmfResponseFactory $responses,
        private readonly ValueObjectFactory $valueObjects,
    ) {}

    public function getProfile(int $id, bool $premium = false): TypedObject
    {
        $player = User::query()->find($id);
        if ($player === null) {
            return $this->responses->make(1);
        }

        $profile = PlayerProfile::query()->firstOrCreate(['user_id' => $player->getKey()]);
        $bestFriend = $player->best_friend_id === null
            ? null
            : User::query()->find($player->best_friend_id);

        return $this->responses->make(valueObject: $this->valueObjects->make('Profile', [
            'id' => $id,
            'lastBlocked' => (int) $profile->last_blocked,
            'bestFriend' => (string) ($bestFriend?->name ?? ''),
            'movie' => (string) $profile->movie,
            'movieChecked' => (bool) $profile->movie_checked,
            'color' => (string) $profile->color,
            'colorChecked' => (bool) $profile->color_checked,
            'hobby' => (string) $profile->hobby,
            'hobbyChecked' => (bool) $profile->hobby_checked,
            'book' => (string) $profile->book,
            'bookChecked' => (bool) $profile->book_checked,
            'song' => (string) $profile->song,
            'songChecked' => (bool) $profile->song_checked,
            'band' => (string) $profile->band,
            'bandChecked' => (bool) $profile->band_checked,
            'schoolSubject' => (string) $profile->school_subject,
            'schoolSubjectChecked' => (bool) $profile->school_subject_checked,
            'sport' => (string) $profile->sport,
            'sportChecked' => (bool) $profile->sport_checked,
            'animal' => (string) $profile->animal,
            'animalChecked' => (bool) $profile->animal_checked,
            'relStatus' => (string) $profile->rel_status,
            'relStatusChecked' => (bool) $profile->rel_status_checked,
            'motto' => (string) $profile->motto,
            'mottoChecked' => (bool) $profile->motto_checked,
            'bestChar' => (string) $profile->best_char,
            'bestCharChecked' => (bool) $profile->best_char_checked,
            'worstChar' => (string) $profile->worst_char,
            'worstCharChecked' => (bool) $profile->worst_char_checked,
            'likeMost' => (string) $profile->like_most,
            'likeMostChecked' => (bool) $profile->like_most_checked,
            'likeLeast' => (string) $profile->like_least,
            'likeLeastChecked' => (bool) $profile->like_least_checked,
        ]));
    }
}
