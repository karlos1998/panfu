<?php

namespace Tests\Feature\Admin;

use App\Models\ChatMessage;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminChatHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('inertia.testing.ensure_pages_exist', false);
    }

    public function test_regular_users_cannot_access_chat_history(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.chat.index'))
            ->assertForbidden();
    }

    public function test_admin_can_filter_chat_history_by_nickname_and_room(): void
    {
        $admin = User::factory()->admin()->create();
        $alice = User::factory()->create(['name' => 'AlicePanda']);
        $bob = User::factory()->create(['name' => 'BobPanda']);

        $this->message($alice, 'Wiadomość w zamku', 13);
        $this->message($alice, 'Wiadomość w dżungli', 12);
        $this->message($bob, 'Inny gracz w zamku', 13);

        $this->actingAs($admin)
            ->get(route('admin.chat.index', ['nickname' => 'Alice', 'room' => 'public:13']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Chat/Index')
                ->where('filters.nickname', 'Alice')
                ->where('filters.room', 'public:13')
                ->has('messages.data', 1)
                ->where('messages.data.0.playerName', 'AlicePanda')
                ->where('messages.data.0.message', 'Wiadomość w zamku')
                ->where('messages.data.0.room.label', '#13 Castle')
                ->where('messages.data.0.room.adminUrl', '/admin/rooms/public/castle')
                ->has('rooms'));
    }

    public function test_chat_history_is_paginated_with_newest_messages_first(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['name' => 'RozmownaPanda']);

        foreach (range(1, 31) as $number) {
            $this->message($user, "Wiadomość {$number}", 13, now()->addSeconds($number));
        }

        $this->actingAs($admin)
            ->get(route('admin.chat.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('messages.data', 30)
                ->where('messages.total', 31)
                ->where('messages.last_page', 2)
                ->where('messages.data.0.message', 'Wiadomość 31'));
    }

    public function test_admin_can_filter_messages_sent_in_player_homes(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['name' => 'DomowaPanda']);

        ChatMessage::query()->create([
            'user_id' => $user->id,
            'player_name' => $user->name,
            'room_id' => $user->id,
            'is_home' => true,
            'message' => 'Wiadomość z domku',
        ]);
        $this->message($user, 'Wiadomość publiczna', 13);

        $this->actingAs($admin)
            ->get(route('admin.chat.index', ['room' => 'home']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('messages.data', 1)
                ->where('messages.data.0.message', 'Wiadomość z domku')
                ->where('messages.data.0.room.type', 'home')
                ->where('messages.data.0.room.label', "Domek gracza #{$user->id}")
                ->where('messages.data.0.room.adminUrl', "/admin/rooms/homes/{$user->id}"));
    }

    public function test_user_details_contain_only_that_users_paginated_chat_history(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['name' => 'HistoriaPandy']);
        $other = User::factory()->create();

        foreach (range(1, 21) as $number) {
            $this->message($user, "Własna wiadomość {$number}", 13, now()->addSeconds($number));
        }
        $this->message($other, 'Cudza wiadomość', 13, now()->addMinute());

        $this->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('chatMessages.data', 20)
                ->where('chatMessages.total', 21)
                ->where('chatMessages.last_page', 2)
                ->where('chatMessages.data.0.message', 'Własna wiadomość 21'));
    }

    private function message(User $user, string $message, int $roomId, ?DateTimeInterface $createdAt = null): ChatMessage
    {
        return ChatMessage::query()->create([
            'user_id' => $user->id,
            'player_name' => $user->name,
            'room_id' => $roomId,
            'is_home' => false,
            'message' => $message,
            'created_at' => $createdAt ?? now(),
        ]);
    }
}
