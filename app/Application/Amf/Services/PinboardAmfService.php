<?php

namespace App\Application\Amf\Services;

use App\Application\Amf\AmfResponseFactory;
use App\Application\Amf\PlayerSession;
use App\Application\Amf\ValueObjectFactory;
use App\Domain\Servers\GameServerClient;
use App\Infrastructure\Amf\TypedObject;
use App\Models\PinboardMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class PinboardAmfService
{
    public function __construct(
        private readonly AmfResponseFactory $responses,
        private readonly ValueObjectFactory $valueObjects,
        private readonly PlayerSession $session,
        private readonly GameServerClient $gameServer,
    ) {}

    public function addMessage(TypedObject $message): TypedObject
    {
        $sender = $this->session->player();
        $content = trim(strip_tags((string) $message->get('content', '')));
        $receivers = array_slice(array_values(array_unique(array_filter(
            array_map('intval', (array) $message->get('receivers', [])),
            fn (int $id): bool => $id > 0,
        ))), 0, 20);
        $validReceivers = User::query()->whereKey($receivers)->pluck('id')->map(fn ($id): int => (int) $id)->all();
        if ($sender === null || $content === '' || mb_strlen($content) > 500 || $validReceivers === []) {
            return $this->responses->make(1);
        }

        $typeId = max(0, min(65535, (int) $message->get('typeId', 0)));
        $parentId = max(0, (int) $message->get('parentMessageId', 0)) ?: null;
        $created = DB::transaction(function () use ($sender, $validReceivers, $typeId, $content, $parentId): PinboardMessage {
            $first = null;
            foreach ($validReceivers as $receiverId) {
                $entry = PinboardMessage::query()->create([
                    'sender_id' => $sender->getKey(),
                    'receiver_id' => $receiverId,
                    'parent_message_id' => $parentId,
                    'type_id' => $typeId,
                    'content' => $content,
                ]);
                $first ??= $entry;
            }

            return $first;
        });

        foreach ($validReceivers as $receiverId) {
            $this->gameServer->send('newPinboardMessage', $receiverId);
        }

        return $this->responses->make(valueObject: $this->valueObjects->make('AddedMessage', [
            'createdMessageVO' => $this->messageValueObject($created->load('sender')),
            'receivers' => $validReceivers,
        ]));
    }

    public function deleteMessage(int $messageId): TypedObject
    {
        $player = $this->session->player();
        if ($player === null) {
            return $this->responses->make(1);
        }

        $updated = PinboardMessage::query()
            ->whereKey($messageId)
            ->where('receiver_id', $player->getKey())
            ->where('deleted', false)
            ->update(['deleted' => true]);

        return $updated > 0
            ? $this->responses->make(valueObject: $messageId)
            : $this->responses->make(1);
    }

    public function loadPinboard(int $playerId): TypedObject
    {
        return $this->loadPinboardPaginated($playerId, 0, 100);
    }

    public function loadPinboardPaginated(int $playerId, int $offset, int $limit): TypedObject
    {
        if ($this->session->player() === null || ! User::query()->whereKey($playerId)->exists()) {
            return $this->responses->make(1);
        }

        $offset = max(0, $offset);
        $limit = max(1, min(100, $limit));
        $query = PinboardMessage::query()->where('receiver_id', $playerId)->where('deleted', false);
        $count = (clone $query)->count();
        $messages = $query->with('sender')->latest('id')->offset($offset)->limit($limit)->get()
            ->map(fn (PinboardMessage $message): TypedObject => $this->messageValueObject($message))
            ->all();

        return $this->responses->make(valueObject: $this->valueObjects->make('Pinboard', [
            'undeletedMessagesCount' => $count,
            'messages' => $messages,
            'offset' => $offset,
            'limit' => $limit,
        ]));
    }

    public function viewPinboard(): TypedObject
    {
        $player = $this->session->player();
        if ($player === null) {
            return $this->responses->make(1);
        }
        PinboardMessage::query()
            ->where('receiver_id', $player->getKey())
            ->where('read', false)
            ->update(['read' => true]);

        return $this->responses->make();
    }

    public function loadPinboardedBuddies(int $typeId): TypedObject
    {
        $player = $this->session->player();
        if ($player === null) {
            return $this->responses->make(1, valueObject: []);
        }

        $receiverIds = PinboardMessage::query()
            ->where('sender_id', $player->getKey())
            ->where('type_id', $typeId)
            ->where('deleted', false)
            ->distinct()
            ->pluck('receiver_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        return $this->responses->make(valueObject: $receiverIds);
    }

    private function messageValueObject(PinboardMessage $message): TypedObject
    {
        return $this->valueObjects->make('Message', [
            'sender' => $this->valueObjects->make('Sender', [
                'senderId' => (int) $message->sender_id,
                'senderName' => (string) $message->sender?->name,
            ]),
            'messageId' => (int) $message->getKey(),
            'read' => (bool) $message->read,
            'createdAt' => $this->valueObjects->make('Date', ['date' => $message->created_at->getTimestampMs()]),
            'replied' => PinboardMessage::query()->where('parent_message_id', $message->getKey())->exists(),
            'typeId' => (int) $message->type_id,
            'content' => (string) $message->content,
            'parentMessageId' => $message->parent_message_id === null ? -1 : (int) $message->parent_message_id,
        ]);
    }
}
