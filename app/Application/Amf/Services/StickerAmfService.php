<?php

namespace App\Application\Amf\Services;

use App\Application\Amf\AmfResponseFactory;
use App\Application\Amf\PlayerSession;
use App\Application\Amf\ValueObjectFactory;
use App\Infrastructure\Amf\TypedObject;
use App\Models\PlayerSticker;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class StickerAmfService
{
    private const DEFINITION_COUNT = 31;

    public function __construct(
        private readonly AmfResponseFactory $responses,
        private readonly ValueObjectFactory $valueObjects,
        private readonly PlayerSession $session,
    ) {}

    public function loadStickerDefinitions(): TypedObject
    {
        $definitions = [];
        for ($id = 1; $id <= self::DEFINITION_COUNT; $id++) {
            $definitions[] = $this->valueObjects->make('StickerDefinition', [
                'id' => $id,
                'points' => 1,
                'restrictions' => $this->valueObjects->make('StickerRestrictions', [
                    'minLevel' => 0,
                    'coins' => 10,
                    'premium' => false,
                ]),
            ]);
        }

        return $this->responses->make(valueObject: $definitions);
    }

    public function loadStickers(int $playerId): TypedObject
    {
        if ($this->session->player() === null || ! User::query()->whereKey($playerId)->exists()) {
            return $this->responses->make(1, valueObject: []);
        }

        $stickers = PlayerSticker::query()->where('user_id', $playerId)->orderBy('definition_id')->get()
            ->map(fn (PlayerSticker $sticker): TypedObject => $this->valueObjects->make('Sticker', [
                'definitionId' => (int) $sticker->definition_id,
                'amount' => (int) $sticker->amount,
            ]))
            ->all();

        return $this->responses->make(valueObject: $stickers);
    }

    public function addNewSticker(TypedObject $sticker): TypedObject
    {
        return $this->add($sticker, false);
    }

    public function addNpcSticker(TypedObject $sticker): TypedObject
    {
        return $this->add($sticker, true);
    }

    private function add(TypedObject $sticker, bool $npc): TypedObject
    {
        $sender = $this->session->player();
        $receiverId = (int) $sticker->get('receiverId', 0);
        $definitionId = (int) $sticker->get('definitionId', 0);
        $content = mb_substr(trim(strip_tags((string) $sticker->get('content', ''))), 0, 160);
        if ($sender === null || $definitionId < 1 || $definitionId > self::DEFINITION_COUNT) {
            return $this->responses->make(1);
        }
        if (! User::query()->whereKey($receiverId)->exists() || ($npc && $receiverId !== (int) $sender->getKey())) {
            return $this->responses->make(1);
        }

        $saved = DB::transaction(function () use ($sender, $receiverId, $definitionId, $npc): bool {
            $lockedSender = User::query()->lockForUpdate()->find($sender->getKey());
            if ($lockedSender === null || (! $npc && (int) $lockedSender->coins < 10)) {
                return false;
            }
            if (! $npc) {
                $lockedSender->decrement('coins', 10);
            }
            $owned = PlayerSticker::query()->lockForUpdate()->firstOrCreate([
                'user_id' => $receiverId,
                'definition_id' => $definitionId,
            ], ['amount' => 0]);
            $owned->increment('amount');

            return true;
        });
        if (! $saved) {
            return $this->responses->make(412);
        }

        return $this->responses->make(valueObject: $this->valueObjects->make('NewSticker', [
            'receiverId' => $receiverId,
            'definitionId' => $definitionId,
            'content' => $content,
        ]));
    }
}
