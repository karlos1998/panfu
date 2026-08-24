<?php

namespace Tests\Feature\Amf;

use App\Domain\Servers\GameServerClient;
use App\Infrastructure\Amf\AmfDecoder;
use App\Infrastructure\Amf\AmfEncoder;
use App\Infrastructure\Amf\AmfEnvelope;
use App\Infrastructure\Amf\AmfMessage;
use App\Infrastructure\Amf\TypedObject;
use App\Models\GameServer;
use App\Models\Item;
use App\Models\PlayerProfile;
use App\Models\PlayerReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AmfGatewayTest extends TestCase
{
    use RefreshDatabase;

    private const WEB_TICKET = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    /** @var list<array{command:string,parameters:list<int|string>}> */
    private array $gameServerCommands = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withSession([]);
        $commands = &$this->gameServerCommands;
        $this->app->instance(GameServerClient::class, new class($commands) implements GameServerClient
        {
            /** @param list<array{command:string,parameters:list<int|string>}> $commands */
            public function __construct(private array &$commands) {}

            public function send(string $command, int|string ...$parameters): bool
            {
                $this->commands[] = compact('command', 'parameters');

                return true;
            }
        });

        GameServer::query()->create([
            'id' => 1,
            'name' => 'Local Panfu',
            'player_count' => 0,
            'url' => '127.0.0.1',
            'port' => 9595,
            'goldpanda' => true,
            'secret_key' => 'test-secret',
        ]);
        foreach ([100, 1001, 103199] as $itemId) {
            Item::query()->create([
                'id' => $itemId,
                'name' => "ITEM_{$itemId}",
                'type' => $itemId === 1001 ? 1 : 0,
                'price' => 0,
                'z' => 0,
                'premium' => false,
            ]);
        }
    }

    public function test_flash_session_login_returns_the_complete_player_and_persists_session(): void
    {
        $player = User::factory()->create([
            'name' => 'Panda',
            'ticket_id' => self::WEB_TICKET,
            'coins' => 1250,
            'social_level' => 4,
            'social_score' => 25,
        ]);

        $login = $this->amf('amfConnectionService.doLoginSession', [self::WEB_TICKET]);
        $this->assertSame(0, $login->get('statusCode'));
        $result = $login->get('valueObject');
        $this->assertInstanceOf(TypedObject::class, $result);
        $this->assertSame('com.pandaland.mvc.model.vo.LoginResultVO', $result->type);
        $ticket = $result->get('ticketId');
        $this->assertIsNumeric($ticket);
        $this->assertSame((string) (int) $ticket, $player->refresh()->ticket_id);
        $this->assertSame('Panda', $result->get('playerInfo')->get('name'));
        $this->assertSame(1250, $result->get('playerInfo')->get('coins'));
        $this->assertCount(1, $result->get('gameServers'));
        $this->assertSame('testConnection', $this->gameServerCommands[0]['command']);

        $this->assertSame(0, $this->amf('amfConnectionService.ping')->get('statusCode'));
        $this->assertCount(3, $player->inventoryEntries()->get());
    }

    public function test_inventory_states_home_and_progression_work_through_amf(): void
    {
        $player = $this->loginPlayer(['coins' => 500, 'social_score' => 0]);
        $item = Item::query()->create([
            'id' => 5000, 'name' => 'BLUE_HAT', 'type' => 3, 'price' => 120, 'z' => 10, 'premium' => false,
        ]);
        $furniture = Item::query()->create([
            'id' => 6000, 'name' => 'CHAIR', 'type' => 13, 'price' => 50, 'z' => 0, 'premium' => false,
        ]);

        $purchase = $this->amf('amfPlayerService.purchaseItem', [$item->id, 'unused']);
        $this->assertSame(0, $purchase->get('statusCode'));
        $this->assertSame(380, $player->refresh()->coins);

        $this->amf('amfPlayerService.purchaseItem', [$furniture->id, 'unused']);
        $layout = new TypedObject('com.pandaland.mvc.model.vo.FurnitureDataVO', [
            'id' => $furniture->id, 'x' => 120, 'y' => 230, 'rot' => 2, 'room' => 4, 'active' => true,
        ]);
        $this->amf('amfPlayerService.updateFurnitures', [[$layout]]);
        $home = $this->amf('amfPlayerService.getPlayerHome', [$player->id])->get('valueObject');
        $furnitureData = collect($home->get('furnitureList'))->first(
            fn (TypedObject $entry): bool => $entry->get('id') === $furniture->id,
        );
        $this->assertInstanceOf(TypedObject::class, $furnitureData);
        $this->assertSame(4, $furnitureData->get('room'));
        $this->assertSame(120, $furnitureData->get('x'));

        $state = $this->amf('amfPlayerService.setState', [3, 7, 99])->get('valueObject');
        $this->assertSame(99, $state->get('stateValue'));
        $states = $this->amf('amfPlayerService.getStates', [[3]])->get('valueObject')->get('list');
        $this->assertCount(1, $states);

        $reward = $this->amf('amfActionService.performAction', [$player->id, 'played10']);
        $this->assertSame(0, $reward->get('statusCode'));
        $this->assertNotEmpty($reward->get('valueObject')->get('list'));
    }

    public function test_social_minigame_and_pokopet_flows_work_through_amf(): void
    {
        $player = $this->loginPlayer(['coins' => 1000, 'goldpanda' => 1]);
        $buddy = User::factory()->create(['name' => 'Buddy', 'social_level' => 8]);

        $this->amf('amfPlayerService.addToBuddylist', [$buddy->id]);
        $friends = $this->amf('amfBuddyListService.getCompleteBuddyList', [$player->id])
            ->get('valueObject')->get('list');
        $this->assertSame('Buddy', $friends[0]->get('name'));
        $this->assertSame('updateBuddyStatus', $this->gameServerCommands[1]['command']);

        $this->amf('amfGameService.finishMinigame', [4, 750]);
        $scores = $this->amf('amfGameService.getHighScoreLists', [4])->get('valueObject');
        $this->assertSame(750, $scores->get('overAllHighscores')[0]->get('score'));
        $social = $this->amf('amfSocialHighscoreService.getSocialHighscore', [$player->id, $buddy->id])
            ->get('valueObject')->get('list');
        $this->assertSame(750, $social[0]->get('playerScore'));

        $petResponse = $this->amf('amfPetService.buyPet', [9, 'Marieta']);
        $this->assertSame(0, $petResponse->get('statusCode'));
        $pet = $petResponse->get('valueObject');
        $this->assertSame('Marieta', $pet->name);
        $this->assertSame(940, $player->refresh()->coins);
        $this->assertSame(0, $this->amf('amfPetService.switchPet', [$pet->id])->get('statusCode'));
        $this->assertSame(5, $this->amf('amfPetService.feed', [$pet->id])->get('valueObject'));
    }

    public function test_registration_and_safe_chat_keep_the_legacy_contract(): void
    {
        $this->assertTrue($this->amf('amfRegistrationService.checkUserName', ['NewPanda'])->get('valueObject'));

        $register = new TypedObject('com.pandaland.mvc.model.vo.RegisterVO', [
            'name' => 'NewPanda', 'pw' => 'secret-password', 'emailParents' => 'parent@example.com', 'sex' => 'girl',
        ]);
        $this->assertSame(0, $this->amf('amfRegistrationService.register', [$register]));
        $this->assertDatabaseHas('users', ['name' => 'NewPanda', 'sex' => true]);

        $chat = $this->amf('amfLanguageService.getSecureChatSnippets', ['EN', 'all']);
        $this->assertSame('all', $chat->get('message'));
        $this->assertNotEmpty($chat->get('valueObject')->get('children'));
    }

    public function test_every_legacy_amf_method_is_reachable_with_compatible_arguments(): void
    {
        $player = User::factory()->create([
            'name' => 'ContractPanda',
            'password' => 'contract-password',
            'coins' => 20_000,
            'goldpanda' => 1,
            'social_level' => 30,
            'social_score' => 0,
        ]);
        $buddy = User::factory()->create(['name' => 'ContractBuddy']);
        $login = new TypedObject('com.pandaland.mvc.model.vo.LoginVO', [
            'playerName' => 'ContractPanda',
            'pw' => 'contract-password',
        ]);
        $this->amf('amfConnectionService.doLogin', [$login]);

        $this->amf('amfActionService.getLastDoneActionToday', [$player->id, 'other', 0]);
        $this->amf('amfActionService.performAction', [$player->id, 'other']);
        $this->amf('amfBeSmarterService.loadBestResult', [$player->id]);
        $this->amf('amfBuddyFilterService.listFilteredBuddies');
        $this->amf('amfBuddyListService.getCompleteBuddyList', [$player->id]);

        $register = new TypedObject('com.pandaland.mvc.model.vo.RegisterVO', [
            'name' => 'ContractNewPanda',
            'pw' => 'contract-password',
            'emailParents' => 'contract-parent@example.com',
            'sex' => 'boy',
        ]);
        $this->amf('amfConnectionService.doRegister', [$register]);
        $this->amf('amfConnectionService.setEmailAddress', [$player->id, 'new@example.com', true]);
        $this->amf('amfConnectionService.checkUserName', ['ContractAvailable']);
        $this->amf('amfConnectionService.checkEmailAddress', ['parent@example.com']);
        $this->amf('amfConnectionService.ping');

        $this->amf('amfGameService.setHighScore', [4, 100]);
        $this->amf('amfGameService.finishMinigame', [4, 200]);
        $this->amf('amfGameService.getHighScoreLists', [4]);
        $this->amf('amfLanguageService.getSecureChatSnippets', ['PL', 'all']);

        $pet = $this->amf('amfPetService.buyPet', [9, 'ContractPet'])->get('valueObject');
        $this->amf('amfPetService.switchPet', [$pet->id]);
        $this->amf('amfPetService.updatePetState', [$pet->id, 'playing']);
        $this->amf('amfPetService.feed', [$pet->id]);
        $player->pokoPets()->whereKey($pet->id)->update(['health' => 4]);
        $this->amf('amfPetService.increaseHealth');
        $this->amf('amfPetService.getGameServer');

        $this->amf('amfPlayerService.getStates', [[1]]);
        $this->amf('amfPlayerService.setState', [1, 2, 3]);
        $this->amf('amfPlayerService.updateTourFinished', [true]);
        $this->amf('amfPlayerService.addToBuddylist', [$buddy->id]);
        $this->amf('amfPlayerService.purchaseItem', [100, 'unused']);
        $this->amf('amfPlayerService.updateItems', [[], []]);
        $this->amf('amfPlayerService.removeItems', [[]]);
        $this->amf('amfPlayerService.getPlayerInfoList', [[$player->id, $buddy->id], false]);
        $this->amf('amfPlayerService.getPlayerCard', [$buddy->id]);
        $this->amf('amfPlayerService.lockHome', [false]);
        $this->amf('amfPlayerService.getPlayerHome', [$player->id]);
        $this->amf('amfPlayerService.updateFurnitures', [[]]);
        $this->amf('amfPlayerService.updateScore', [$player->refresh()->coins]);

        $this->amf('amfProfileService.getProfile', [$player->id, true]);
        $this->amf('amfRegistrationService.checkUserName', ['AnotherAvailable']);
        $this->amf('amfRegistrationService.loadUsernameSuggestions', ['Suggestion']);
        $this->amf('amfRegistrationService.checkEmailAddress', ['parent@example.com']);
        $this->amf('amfSocialHighscoreService.getSocialHighscore', [$player->id, $buddy->id]);
        $this->amf('amfPetService.removePet', [$pet->id]);

        $this->assertDatabaseMissing('pokopets', ['id' => $pet->id]);
    }

    public function test_profile_highscore_be_smarter_request_returns_without_stalling(): void
    {
        $player = $this->loginPlayer();

        $empty = $this->amf('amfBeSmarterService.loadBestResult', [$player->id]);
        $this->assertSame(0, $empty->get('statusCode'));
        $this->assertNull($empty->get('valueObject'));

        $this->amf('amfGameService.setHighScore', [51, 1234]);
        $result = $this->amf('amfBeSmarterService.loadBestResult', [$player->id]);

        $this->assertSame(1234, $result->get('valueObject')->points);
        $this->assertSame($player->name, $result->get('valueObject')->playerName);
    }

    public function test_social_profile_and_account_settings_are_persisted(): void
    {
        $player = $this->loginPlayer(['email' => 'old@example.com']);
        $buddy = User::factory()->create(['name' => 'BestBuddy']);
        $blocked = User::factory()->create(['name' => 'BlockedPanda']);

        $this->assertSame(0, $this->amf('amfPlayerService.addToBuddylist', [$buddy->id])->get('statusCode'));
        $this->assertSame(0, $this->amf('amfBuddyListService.changeBestFriend', [0, $buddy->id])->get('statusCode'));
        $buddies = $this->amf('amfBuddyListService.getBuddyList', [[$buddy->id]])->get('valueObject')->get('list');
        $this->assertTrue($buddies[0]->get('bestfriend'));

        $filter = $this->amf('amfBuddyFilterService.addFilteredBuddy', [$player->id, $blocked->id, 1]);
        $this->assertSame($blocked->id, $filter->get('valueObject')->get('buddy2'));
        $filters = $this->amf('amfBuddyFilterService.listFilteredBuddies')->get('valueObject')->get('list');
        $this->assertCount(1, $filters);
        $this->assertSame(0, $this->amf('amfBuddyFilterService.removeFilteredBuddy', [$player->id, $blocked->id])->get('statusCode'));
        $this->assertCount(0, $this->amf('amfBuddyFilterService.listFilteredBuddies')->get('valueObject')->get('list'));

        $this->assertSame(0, $this->amf('amfPlayerService.lockHome', [true])->get('statusCode'));
        $this->assertTrue($this->amf('amfPlayerService.getPlayerHome', [$player->id])->get('valueObject')->get('locked'));
        $this->assertSame(0, $this->amf('amfPlayerService.updateHelperStatus', [true])->get('statusCode'));
        $this->assertSame(0, $this->amf('amfPlayerService.updatePlayerState', [$player->id, 'Ready to play'])->get('statusCode'));

        PlayerProfile::query()->create(['user_id' => $player->id, 'motto' => 'Hello Panfu']);
        $profile = $this->amf('amfProfileService.getProfile', [$player->id, true])->get('valueObject');
        $this->assertSame('BestBuddy', $profile->get('bestFriend'));
        $this->assertSame('Hello Panfu', $profile->get('motto'));

        $this->assertSame(0, $this->amf('amfConnectionService.setEmailAddress', [$player->id, 'new@example.com', true])->get('statusCode'));
        $birthday = new TypedObject('com.pandaland.mvc.model.vo.DateVO', [
            'date' => now()->subYears(12)->startOfDay()->getTimestampMs(),
        ]);
        $this->assertSame(0, $this->amf('amfConnectionService.setBirthday', [$birthday])->get('statusCode'));
        $this->assertSame('new@example.com', $player->refresh()->email);
        $this->assertTrue($player->email_verified_at !== null);
        $this->assertTrue($player->home_locked);
        $this->assertTrue($player->helper_status);
        $this->assertSame('Ready to play', $player->player_state);
        $this->assertSame($buddy->id, $player->best_friend_id);
        $this->assertNotNull($player->birthday);

        $this->assertSame(0, $this->amf('amfPlayerService.reportPlayer', [$blocked->id, 'spam'])->get('statusCode'));
        $this->assertDatabaseHas(PlayerReport::class, [
            'reporter_id' => $player->id,
            'reported_id' => $blocked->id,
            'reason' => 'spam',
        ]);

        $this->assertSame(0, $this->amf('amfPlayerService.removeFromBuddyList', [$buddy->id])->get('statusCode'));
        $this->assertNull($player->refresh()->best_friend_id);
        $this->assertCount(0, $this->amf('amfBuddyListService.getCompleteBuddyList', [$player->id])->get('valueObject')->get('list'));

        $this->assertSame(0, $this->amf('amfConnectionService.doLogout')->get('statusCode'));
        $this->assertSame(1, $this->amf('amfConnectionService.ping')->get('statusCode'));
    }

    /** @param array<string, mixed> $attributes */
    private function loginPlayer(array $attributes = []): User
    {
        $player = User::factory()->create(array_replace([
            'ticket_id' => self::WEB_TICKET,
            'coins' => 1000,
            'social_level' => 1,
            'social_score' => 0,
        ], $attributes));
        $this->amf('amfConnectionService.doLoginSession', [self::WEB_TICKET]);

        return $player;
    }

    /** @param list<mixed> $arguments */
    private function amf(string $target, array $arguments = []): mixed
    {
        $payload = (new AmfEncoder)->encode(new AmfEnvelope(3, [
            new AmfMessage($target, '/1', $arguments),
        ]));

        $response = $this->call(
            method: 'POST',
            uri: '/InformationServer/',
            server: ['CONTENT_TYPE' => 'application/x-amf'],
            content: $payload,
        );
        $response->assertOk()->assertHeader('Content-Type', 'application/x-amf');

        $envelope = (new AmfDecoder)->decode((string) $response->getContent());
        $this->assertSame('/1/onResult', $envelope->messages[0]->target);

        return $envelope->messages[0]->data;
    }
}
