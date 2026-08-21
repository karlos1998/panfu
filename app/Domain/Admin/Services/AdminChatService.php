<?php

namespace App\Domain\Admin\Services;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AdminChatService
{
    /** @var Collection<int, array<string, mixed>>|null */
    private ?Collection $roomCatalog = null;

    public function __construct(private readonly AdminPublicRoomService $rooms) {}

    /**
     * @param  array{nickname: string, room: string}  $filters
     * @return array<string, mixed>
     */
    public function paginatedMessages(array $filters): array
    {
        $query = ChatMessage::query()->with('user');

        $this->applyFilters($query, $filters);

        /** @var LengthAwarePaginator<int, ChatMessage> $messages */
        $messages = $query
            ->latest('created_at')
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return [
            'messages' => $messages->through(fn (ChatMessage $message): array => $this->summarize($message)),
            'filters' => $filters,
            'rooms' => [
                ['value' => 'home', 'label' => 'Domki graczy'],
                ...array_map(fn (array $room): array => [
                    'value' => $room['value'],
                    'label' => "#{$room['number']} {$room['label']}",
                ], $this->rooms->roomOptions()),
            ],
        ];
    }

    /** @return LengthAwarePaginator<int, array<string, mixed>> */
    public function messagesForUser(User $user): LengthAwarePaginator
    {
        /** @var LengthAwarePaginator<int, ChatMessage> $messages */
        $messages = $user->chatMessages()
            ->with('user')
            ->latest('created_at')
            ->latest('id')
            ->paginate(20, ['*'], 'chat_page')
            ->withQueryString();

        return $messages->through(fn (ChatMessage $message): array => $this->summarize($message));
    }

    /**
     * @param  Builder<ChatMessage>  $query
     * @param  array{nickname: string, room: string}  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $query->when($filters['nickname'] !== '', function (Builder $query) use ($filters): void {
            $nickname = $filters['nickname'];
            $query->where(function (Builder $query) use ($nickname): void {
                $query->where('player_name', 'like', "%{$nickname}%")
                    ->orWhereHas('user', fn (Builder $query) => $query->where('name', 'like', "%{$nickname}%"));
            });
        });

        if ($filters['room'] === 'home') {
            $query->where('is_home', true);

            return;
        }

        if (str_starts_with($filters['room'], 'public:')) {
            $query
                ->where('is_home', false)
                ->where('room_id', (int) substr($filters['room'], strlen('public:')));
        }
    }

    /** @return array<string, mixed> */
    private function summarize(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'userId' => $message->user_id,
            'playerName' => $message->player_name,
            'message' => $message->message,
            'room' => $this->summarizeRoom($message),
            'createdAt' => $message->created_at?->toIso8601String(),
        ];
    }

    /** @return array{type: string, id: int, label: string, adminUrl: string|null} */
    private function summarizeRoom(ChatMessage $message): array
    {
        if ($message->is_home) {
            return [
                'type' => 'home',
                'id' => $message->room_id,
                'label' => "Domek gracza #{$message->room_id}",
                'adminUrl' => "/admin/rooms/homes/{$message->room_id}",
            ];
        }

        $room = $this->roomCatalog()->get($message->room_id);

        return [
            'type' => 'public',
            'id' => $message->room_id,
            'label' => $room === null ? "Pokój #{$message->room_id}" : "#{$message->room_id} {$room['label']}",
            'adminUrl' => $room === null ? null : "/admin/rooms/public/{$room['id']}",
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    private function roomCatalog(): Collection
    {
        return $this->roomCatalog ??= collect($this->rooms->roomOptions())->keyBy('number');
    }
}
