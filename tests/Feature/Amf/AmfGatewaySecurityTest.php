<?php

namespace Tests\Feature\Amf;

use App\Domain\Servers\GameServerClient;
use App\Infrastructure\Amf\AmfDecoder;
use App\Infrastructure\Amf\AmfEncoder;
use App\Infrastructure\Amf\AmfEnvelope;
use App\Infrastructure\Amf\AmfMessage;
use App\Infrastructure\Amf\TypedObject;
use App\Models\GameHighScore;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\PokoPet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AmfGatewaySecurityTest extends TestCase
{
    use RefreshDatabase;

    private const WEB_TICKET = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withSession([]);
        $this->app->instance(GameServerClient::class, new class implements GameServerClient
        {
            public function send(string $command, int|string ...$parameters): bool
            {
                return true;
            }
        });
    }

    public function test_gateway_rejects_wrong_content_types_malformed_payloads_and_oversized_payloads(): void
    {
        $this->call('POST', '/InformationServer/', content: 'not-amf')
            ->assertUnsupportedMediaType();

        $this->rawAmf('not-amf')->assertBadRequest();

        config(['panfu.amf.max_payload_bytes' => 8]);
        $this->rawAmf(str_repeat('x', 9))->assertStatus(413);
    }

    public function test_only_the_compatibility_gateway_paths_are_routable(): void
    {
        $payload = $this->payload('amfConnectionService.ping');

        $this->rawAmf($payload)->assertOk();
        $this->call(
            method: 'POST',
            uri: '/InformationServer/gateway/amf',
            server: ['CONTENT_TYPE' => 'application/x-amf'],
            content: $payload,
        )->assertOk();
        $this->call(
            method: 'POST',
            uri: '/InformationServer/unexpected/path',
            server: ['CONTENT_TYPE' => 'application/x-amf'],
            content: $payload,
        )->assertNotFound();
    }

    public function test_successful_responses_are_binary_non_cacheable_and_nosniff(): void
    {
        $response = $this->rawAmf($this->payload('amfConnectionService.ping'));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/x-amf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_unknown_or_prefixed_targets_are_rejected_without_leaking_exception_details(): void
    {
        foreach (['evil.amfConnectionService.ping', 'amfConnectionService.notExposed'] as $target) {
            $message = $this->responseMessage($this->rawAmf($this->payload($target)));

            $this->assertSame('/security/onStatus', $message->target);
            $this->assertSame('Client.Request', $message->data->faultCode);
            $this->assertSame('The AMF request could not be processed.', $message->data->faultString);
            $this->assertStringNotContainsString($target, $message->data->faultString);
        }
    }

    public function test_repeated_invalid_logins_are_throttled(): void
    {
        config([
            'panfu.amf.login_attempts_per_minute' => 2,
            'panfu.amf.login_attempts_per_ip' => 10,
        ]);
        $login = new TypedObject('com.pandaland.mvc.model.vo.LoginVO', [
            'playerName' => 'RateLimitedPanda',
            'pw' => 'wrong-password',
        ]);

        $this->assertSame('', $this->amf('amfConnectionService.doLogin', [$login], '203.0.113.10')->get('message'));
        $this->assertSame('', $this->amf('amfConnectionService.doLogin', [$login], '203.0.113.10')->get('message'));
        $this->assertSame(
            'Too many login attempts.',
            $this->amf('amfConnectionService.doLogin', [$login], '203.0.113.10')->get('message'),
        );
    }

    public function test_gateway_has_a_separate_per_ip_request_limit(): void
    {
        config(['panfu.amf.requests_per_minute' => 2]);
        $payload = $this->payload('amfConnectionService.ping');

        $this->rawAmf($payload, '203.0.113.20')->assertOk();
        $this->rawAmf($payload, '203.0.113.20')->assertOk();
        $this->rawAmf($payload, '203.0.113.20')->assertTooManyRequests();
    }

    public function test_buddy_lists_require_the_owner_session(): void
    {
        $owner = User::factory()->create(['ticket_id' => self::WEB_TICKET]);
        $other = User::factory()->create();

        $guest = $this->amf('amfBuddyListService.getCompleteBuddyList', [$owner->id]);
        $this->assertSame(1, $guest->get('statusCode'));

        $this->amf('amfConnectionService.doLoginSession', [self::WEB_TICKET]);
        $crossPlayer = $this->amf('amfBuddyListService.getCompleteBuddyList', [$other->id]);
        $this->assertSame(1, $crossPlayer->get('statusCode'));

        $own = $this->amf('amfBuddyListService.getCompleteBuddyList', [$owner->id]);
        $this->assertSame(0, $own->get('statusCode'));
    }

    public function test_players_cannot_modify_another_players_pet_or_non_furniture_inventory_entry(): void
    {
        $player = $this->loginPlayer();
        $other = User::factory()->create();
        $otherPet = PokoPet::query()->create([
            'user_id' => $other->id,
            'type' => 9,
            'name' => 'Protected',
            'selected' => false,
            'state' => 'idle',
            'health' => 5,
            'max_health' => 5,
            'speed' => 1,
            'agility' => 1,
            'power' => 1,
            'experience' => 0,
            'level' => 1,
            'abilities' => [],
        ]);
        $wearable = Item::query()->create([
            'id' => 9001,
            'name' => 'NOT_FURNITURE',
            'type' => 3,
            'price' => 0,
            'z' => 0,
            'premium' => false,
        ]);
        $entry = Inventory::query()->create([
            'user_id' => $player->id,
            'item_id' => $wearable->id,
            'active' => false,
            'bought' => true,
            'x' => 0,
            'y' => 0,
            'rot' => 0,
            'room' => 0,
        ]);

        $this->assertSame(3, $this->amf('amfPetService.removePet', [$otherPet->id])->get('statusCode'));
        $this->assertDatabaseHas('pokopets', ['id' => $otherPet->id]);

        $layout = new TypedObject('com.pandaland.mvc.model.vo.FurnitureDataVO', [
            'id' => $wearable->id,
            'x' => 999,
            'y' => 999,
            'rot' => 5,
            'room' => 2,
            'active' => true,
        ]);
        $this->amf('amfPlayerService.updateFurnitures', [[$layout]]);
        $this->assertSame(0, $entry->refresh()->x);
        $this->assertFalse($entry->active);
    }

    public function test_premium_purchases_and_untrusted_scores_are_validated_server_side(): void
    {
        $player = $this->loginPlayer(['coins' => 1000, 'goldpanda' => 0]);
        $premium = Item::query()->create([
            'id' => 9002,
            'name' => 'PREMIUM_ITEM',
            'type' => 3,
            'price' => 50,
            'z' => 0,
            'premium' => true,
        ]);

        $purchase = $this->amf('amfPlayerService.purchaseItem', [$premium->id, 'ignored']);
        $this->assertSame(5, $purchase->get('statusCode'));
        $this->assertSame(1000, $player->refresh()->coins);

        $this->assertSame(1, $this->amf('amfPlayerService.updateScore', [999])->get('statusCode'));
        $this->assertSame(1, $this->amf('amfPlayerService.updateScore', [11_001])->get('statusCode'));
        $this->assertSame(0, $this->amf('amfPlayerService.updateScore', [2000])->get('statusCode'));
        $this->assertSame(2000, $player->refresh()->coins);

        $this->assertSame(1, $this->amf('amfGameService.finishMinigame', [4, -1])->get('statusCode'));
        $this->assertSame(1, $this->amf('amfGameService.finishMinigame', [4, 2_000_000_001])->get('statusCode'));
        $this->assertFalse(GameHighScore::query()->where('user_id', $player->id)->exists());
    }

    public function test_coin_balance_updates_are_rate_limited_per_player(): void
    {
        config(['panfu.amf.coin_updates_per_minute' => 2]);
        $player = $this->loginPlayer(['coins' => 1000]);

        $this->assertSame(0, $this->amf('amfPlayerService.updateScore', [1100])->get('statusCode'));
        $this->assertSame(0, $this->amf('amfPlayerService.updateScore', [1200])->get('statusCode'));
        $this->assertSame(1, $this->amf('amfPlayerService.updateScore', [1300])->get('statusCode'));
        $this->assertSame(1200, $player->refresh()->coins);
    }

    public function test_registration_rejects_weak_passwords(): void
    {
        $register = new TypedObject('com.pandaland.mvc.model.vo.RegisterVO', [
            'name' => 'WeakPasswordPanda',
            'pw' => 'short',
            'emailParents' => 'parent@example.com',
            'sex' => 'boy',
        ]);

        $result = $this->amf('amfRegistrationService.register', [$register]);
        $this->assertInstanceOf(TypedObject::class, $result);
        $this->assertSame(1, $result->get('statusCode'));
        $this->assertDatabaseMissing('users', ['name' => 'WeakPasswordPanda']);
    }

    public function test_registration_is_rate_limited_and_blocked_names_do_not_stall_suggestions(): void
    {
        config(['panfu.amf.registrations_per_minute' => 2]);
        $invalid = new TypedObject('com.pandaland.mvc.model.vo.RegisterVO', [
            'name' => 'AttemptedPanda',
            'pw' => 'short',
            'emailParents' => 'parent@example.com',
            'sex' => 'boy',
        ]);

        $this->amf('amfRegistrationService.register', [$invalid], '203.0.113.30');
        $this->amf('amfRegistrationService.register', [$invalid], '203.0.113.30');
        $limited = $this->amf('amfRegistrationService.register', [$invalid], '203.0.113.30');
        $this->assertSame('Too many registration attempts.', $limited->get('message'));

        $suggestions = $this->amf('amfRegistrationService.loadUsernameSuggestions', ['analsex']);
        $this->assertSame(0, $suggestions->get('statusCode'));
        $this->assertMatchesRegularExpression('/^Panda\d+$/', $suggestions->get('valueObject')[0]);
    }

    /** @param array<string, mixed> $attributes */
    private function loginPlayer(array $attributes = []): User
    {
        $player = User::factory()->create(array_replace([
            'ticket_id' => self::WEB_TICKET,
            'coins' => 1000,
            'goldpanda' => 1,
        ], $attributes));
        $this->amf('amfConnectionService.doLoginSession', [self::WEB_TICKET]);

        return $player;
    }

    /** @param list<mixed> $arguments */
    private function amf(string $target, array $arguments = [], string $ip = '127.0.0.1'): mixed
    {
        return $this->responseMessage($this->rawAmf($this->payload($target, $arguments), $ip))->data;
    }

    /** @param list<mixed> $arguments */
    private function payload(string $target, array $arguments = []): string
    {
        return (new AmfEncoder)->encode(new AmfEnvelope(3, [
            new AmfMessage($target, '/security', $arguments),
        ]));
    }

    private function rawAmf(string $payload, string $ip = '127.0.0.1'): TestResponse
    {
        return $this->call(
            method: 'POST',
            uri: '/InformationServer/',
            server: ['CONTENT_TYPE' => 'application/x-amf', 'REMOTE_ADDR' => $ip],
            content: $payload,
        );
    }

    private function responseMessage(TestResponse $response): AmfMessage
    {
        $response->assertOk();

        return (new AmfDecoder)->decode((string) $response->getContent())->messages[0];
    }
}
