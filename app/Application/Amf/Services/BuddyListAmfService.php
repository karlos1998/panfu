<?php

namespace App\Application\Amf\Services;

use App\Application\Amf\AmfResponseFactory;
use App\Application\Amf\ValueObjectFactory;
use App\Domain\Social\SocialService;
use App\Infrastructure\Amf\TypedObject;
use App\Models\User;

final class BuddyListAmfService
{
    public function __construct(
        private readonly AmfResponseFactory $responses,
        private readonly ValueObjectFactory $valueObjects,
        private readonly SocialService $social,
    ) {}

    public function getCompleteBuddyList(int $userId): TypedObject
    {
        $player = User::query()->find($userId);

        return $this->responses->make(valueObject: $this->valueObjects->make('List', [
            'list' => $player === null ? [] : $this->social->friendsFor($player),
        ]));
    }
}
