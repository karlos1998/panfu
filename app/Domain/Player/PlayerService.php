<?php

namespace App\Domain\Player;

use App\Application\Amf\ValueObjectFactory;
use App\Domain\Inventory\InventoryService;
use App\Domain\Pets\PetService;
use App\Domain\Social\SocialService;
use App\Infrastructure\Amf\TypedObject;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Throwable;

final class PlayerService
{
    /** @var list<string>|null */
    private ?array $blockedWords = null;

    public function __construct(
        private readonly ValueObjectFactory $valueObjects,
        private readonly InventoryService $inventory,
        private readonly SocialService $social,
        private readonly PetService $pets,
    ) {}

    public function authenticate(string $name, string $password): ?User
    {
        $player = User::query()->where('name', $name)->first();

        return $player !== null && Hash::check($password, $player->password) ? $player : null;
    }

    public function authenticateTicket(string $ticket): ?User
    {
        if (strlen($ticket) < 5) {
            return null;
        }

        return User::query()->where('ticket_id', $ticket)->first();
    }

    public function issueGameTicket(User $player): int
    {
        $ticket = random_int(100000000, 2147483647);
        $player->forceFill(['ticket_id' => (string) $ticket])->save();

        return $ticket;
    }

    public function ensureStarterInventory(User $player): void
    {
        foreach (config('panfu.player.starter_inventory', []) as $entry) {
            $this->inventory->add(
                $player,
                (int) ($entry['item_id'] ?? 0),
                (bool) ($entry['active'] ?? false),
            );
        }
    }

    public function register(TypedObject $data): ?User
    {
        if ($data->type !== 'com.pandaland.mvc.model.vo.RegisterVO') {
            return null;
        }

        $name = (string) $data->get('name', '');
        $email = (string) $data->get('emailParents', '');
        if (! $this->nameAvailable($name) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        try {
            return User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => (string) $data->get('pw', ''),
                'sex' => in_array($data->get('sex'), ['girl', 'FEMALE'], true),
            ]);
        } catch (Throwable) {
            return null;
        }
    }

    public function nameAvailable(string $name): bool
    {
        return $this->nameAcceptable($name) && ! User::query()->where('name', $name)->exists();
    }

    public function nameAcceptable(string $name): bool
    {
        if (! preg_match('/^[A-Za-z0-9_]{3,25}$/', $name)) {
            return false;
        }

        $normalized = strtolower(str_replace(['_', '-'], '', $this->undoLeet($name)));
        foreach ($this->blockedWords() as $word) {
            if ($word !== '' && ! str_starts_with($word, '#') && str_contains($normalized, strtolower($word))) {
                return false;
            }
        }

        return true;
    }

    public function info(User $player): TypedObject
    {
        return $this->valueObjects->make('PlayerInfo', [
            'id' => (int) $player->getKey(),
            'name' => (string) $player->name,
            'coins' => (int) ($player->coins ?? 0),
            'isSheriff' => (int) ($player->sheriff ?? 0),
            'isPremium' => (int) $player->goldpanda > 0,
            'sex' => $player->sex ? 'girl' : 'boy',
            'helperStatus' => false,
            'isTourFinished' => true,
            'membershipStatus' => (int) ($player->goldpanda ?? 0),
            'currentGameServer' => (int) ($player->current_gameserver ?? 0),
            'socialLevel' => (int) ($player->social_level ?? 0),
            'socialScore' => (int) ($player->social_score ?? 0),
            'activeInventory' => $this->inventory->itemsFor($player, true),
            'inactiveInventory' => $this->inventory->itemsFor($player, false),
            'buddies' => $this->social->smallFriendsFor($player),
            'pokoPets' => $this->pets->forPlayer($player),
            'pokoPetsWithNoHealth' => $this->pets->withoutHealth($player),
            'daysOnPanfu' => (int) round($player->created_at->diffInSeconds(now()) / 86400),
        ]);
    }

    /** @return list<string> */
    private function blockedWords(): array
    {
        if ($this->blockedWords === null) {
            $contents = file_get_contents(resource_path('data/game/word-filter.txt')) ?: '';
            $this->blockedWords = explode("\n", str_replace("\r", '', $contents));
        }

        return $this->blockedWords;
    }

    private function undoLeet(string $text): string
    {
        return strtr($text, ['0' => 'o', '1' => 'l', '2' => 'z', '3' => 'e', '4' => 'a', '5' => 's', '6' => 'b', '7' => 't', '8' => 'b', '9' => 'p']);
    }
}
