<?php

namespace Tests\Feature\Amf;

use App\Domain\Servers\GameServerClient;
use App\Infrastructure\Amf\AmfDecoder;
use App\Infrastructure\Amf\AmfEncoder;
use App\Infrastructure\Amf\AmfEnvelope;
use App\Infrastructure\Amf\AmfMessage;
use App\Infrastructure\Amf\TypedObject;
use App\Models\GameServer;
use App\Models\GoldPackageCode;
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
        foreach ([100, 1001, 20001, 103019, 103199] as $itemId) {
            Item::query()->create([
                'id' => $itemId,
                'name' => $itemId === 20001 ? 'Blue Bolly' : "ITEM_{$itemId}",
                'type' => $itemId === 1001 ? 1 : 0,
                'price' => $itemId === 20001 ? 2500 : 0,
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

    public function test_all_legacy_quest_state_flows_persist_from_beginning_to_end(): void
    {
        $this->loginPlayer();

        $flows = [
            'Till Death' => [[0, 0, 1], [0, 0, 20], [0, 0, 32]],
            'Swimming' => [[1, 0, 1], [1, 1, 1], [1, 2, 1]],
            'My Son Is A Pirate' => [[2, 0, 10], [2, 0, 100], [2, 0, 240]],
            'Horse' => [[3, 0, 10], [3, 0, 40], [3, 0, 60]],
            'Surfer' => [[4, 0, 10], [4, 0, 50], [4, 0, 85]],
            'Wooby' => [[9, 0, 0], [9, 0, 50], [9, 0, 99]],
            'The Lost Lover' => [[15, 0, 0], [15, 0, 50], [15, 0, 95]],
            'Big Foot' => [[25, 0, 0], [25, 0, 20], [25, 0, 35]],
            'Mysterious Call' => [[28, 0, 0], [28, 0, 50], [28, 0, 100]],
            'New Pets' => [[30, 0, 0], [30, 0, 50], [30, 0, 100]],
            'Transition' => [[62, 0, 0], [62, 0, 40], [62, 0, 100]],
            "Let's Go There" => [[67, 0, 0], [67, 0, 2500], [67, 0, 4999]],
            'Tutorial 1' => [[72, 0, 0], [72, 0, 100], [72, 0, 165]],
            'Tutorial 2' => [[73, 0, 0], [73, 0, 500], [73, 0, 1000]],
            'Small Talk' => [[83, 0, 0], [83, 0, 1]],
            'Unicorn' => [[95, 0, 0], [95, 0, 100], [95, 0, 200]],
        ];

        foreach ($flows as $quest => $states) {
            foreach ($states as [$category, $name, $value]) {
                $response = $this->amf('amfPlayerService.setState', [$category, $name, $value]);
                $this->assertSame(0, $response->get('statusCode'), "{$quest} state update failed");
            }

            [$category, $name, $value] = $states[array_key_last($states)];
            $storedStates = $this->amf('amfPlayerService.getStates', [[$category]])
                ->get('valueObject')->get('list');
            $stored = collect($storedStates)->first(
                fn (TypedObject $state): bool => $state->get('nameId') === $name,
            );

            $this->assertInstanceOf(TypedObject::class, $stored, "{$quest} final state was not returned");
            $this->assertSame($value, $stored->get('stateValue'), "{$quest} final state was not persisted");
        }
    }

    public function test_profile_achievements_jukebox_and_transition_reward_keep_the_legacy_contract(): void
    {
        $this->loginPlayer();
        $profilePlayer = User::factory()->create();
        $profilePlayer->states()->create(['category' => 10001, 'name' => 0, 'value' => 3, 'last_changed' => time()]);
        $profilePlayer->states()->create(['category' => 10002, 'name' => 1, 'value' => 99, 'last_changed' => time()]);

        $states = $this->amf('amfPlayerService.getProfileStates', [$profilePlayer->id, 10001, 10042, 0])
            ->get('valueObject')->get('list');
        $this->assertCount(1, $states);
        $this->assertSame(10001, $states[0]->get('cathegoryId'));
        $this->assertSame(3, $states[0]->get('stateValue'));

        $this->assertSame(0, $this->amf('amfPlayerService.activateItem', [0, true])->get('statusCode'));

        $reward = $this->amf('amfPlayerService.collectItem', [103019, false, true]);
        $this->assertSame(0, $reward->get('statusCode'));
        $this->assertSame(103019, $reward->get('valueObject')->get('id'));
        $this->assertDatabaseHas('inventories', ['item_id' => 103019]);

        $blocked = $this->amf('amfPlayerService.collectItem', [100, false, true]);
        $this->assertSame(1, $blocked->get('statusCode'));
    }

    public function test_rare_item_machine_can_grant_its_premium_daily_reward_to_a_regular_player(): void
    {
        $player = $this->loginPlayer(['goldpanda' => 0]);
        Item::query()->create([
            'id' => 103952,
            'name' => 'CARROUSEL',
            'type' => 13,
            'price' => 0,
            'z' => 0,
            'premium' => true,
        ]);

        $blocked = $this->amf('amfPlayerService.purchaseItem', [103952, 'invalid-quest-check']);
        $this->assertSame(5, $blocked->get('statusCode'));
        $this->assertDatabaseMissing('inventories', ['user_id' => $player->id, 'item_id' => 103952]);

        $reward = $this->amf('amfPlayerService.purchaseItem', [
            103952,
            'fcf6afdaf438a831a278970cce46a7fc',
        ]);

        $this->assertSame(0, $reward->get('statusCode'));
        $this->assertDatabaseHas('inventories', ['user_id' => $player->id, 'item_id' => 103952]);
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
        $bolly = $this->amf('amfBollyService.purchaseBolly', [20001])->get('valueObject');
        $this->amf('amfBollyService.updateBolly', [$bolly]);
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

        $message = new TypedObject('com.pandaland.informationserver.features.pinboard.vo.NewMessageVO', [
            'typeId' => 1,
            'content' => 'Hello',
            'parentMessageId' => -1,
            'receivers' => [$buddy->id],
        ]);
        $messageId = $this->amf('amfPinboardService.addMessage', [$message])
            ->get('valueObject')->get('createdMessageVO')->get('messageId');
        $this->amf('amfPinboardService.loadPinboard', [$buddy->id]);
        $this->amf('amfPinboardService.loadPinboardPaginated', [$buddy->id, 0, 10]);
        $this->amf('amfPinboardService.loadPinboardedBuddies', [1]);
        $this->amf('amfStickerService.loadStickerDefinitions');
        $this->amf('amfStickerService.loadStickers', [$player->id]);
        $npcSticker = new TypedObject('com.pandaland.informationserver.features.stickers.vo.NewStickerVO', [
            'receiverId' => $player->id, 'definitionId' => 1, 'content' => '1|1',
        ]);
        $this->amf('amfStickerService.addNpcSticker', [$npcSticker]);
        $this->amf('amfTivolaService.loadScore');
        $this->amf('amfTivolaService.updateScore', ['math', 100]);

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
        $this->amf('amfBollyService.removeBolly', [20001]);

        $this->assertDatabaseMissing('pokopets', ['id' => $pet->id]);
        $this->assertDatabaseHas('pinboard_messages', ['id' => $messageId, 'receiver_id' => $buddy->id]);
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

    public function test_be_smarter_result_details_and_monthly_leader_are_persisted(): void
    {
        $player = $this->loginPlayer(['name' => 'Smarty']);

        $result = $this->amf('amfBeSmarterService.putScore', [800, 8, 2, 54_000, str_repeat('a', 32)]);
        $this->assertSame(0, $result->get('statusCode'));
        $this->assertSame(8, $result->get('valueObject')->correctAnswers);
        $this->assertSame(2, $result->get('valueObject')->falseAnswers);
        $this->assertSame(54_000, $result->get('valueObject')->time);

        $profile = $this->amf('amfBeSmarterService.loadBestResult', [$player->id])->get('valueObject');
        $this->assertSame(800, $profile->points);
        $this->assertSame(8, $profile->correctAnswers);

        $leader = $this->amf('amfBeSmarterService.loadLeadingPlayer')->get('valueObject');
        $this->assertSame('Smarty', $leader->playerName);
        $this->assertSame(54_000, $leader->time);

        $invalid = $this->amf('amfBeSmarterService.putScore', [1000, 1, 0, 1, str_repeat('b', 32)]);
        $this->assertSame(1, $invalid->get('statusCode'));
        $this->assertSame(800, $player->gameHighScores()->where('game_id', 51)->value('score'));
    }

    public function test_world_event_container_is_shared_and_capped(): void
    {
        $this->loginPlayer();

        $empty = $this->amf('amfWorldEventService.loadContainer', [17])->get('valueObject');
        $this->assertSame(0, $empty->value);
        $this->assertSame(1000, $empty->maxValue);

        $updated = $this->amf('amfWorldEventService.increaseContainerValue', [17])->get('valueObject');
        $this->assertSame(1, $updated->value);
        $this->assertSame(1000, $updated->maxValue);
        $this->assertDatabaseHas('world_event_containers', ['id' => 17, 'value' => 1]);
    }

    public function test_gold_package_activation_requires_an_unused_server_issued_code(): void
    {
        $player = $this->loginPlayer(['goldpanda' => 0]);
        GoldPackageCode::query()->create(['code_hash' => hash('sha256', 'PANFUGOLD2026')]);

        $invalid = $this->amf('amfActivateGoldPackageService.activateGoldPackage', ['wrong-code']);
        $this->assertSame(1, $invalid->get('statusCode'));
        $this->assertSame(0, $player->refresh()->goldpanda);

        $activated = $this->amf('amfActivateGoldPackageService.activateGoldPackage', ['panfu-gold-2026']);
        $this->assertSame(0, $activated->get('statusCode'));
        $this->assertSame(1, $player->refresh()->goldpanda);
        $this->assertDatabaseHas('gold_package_codes', ['redeemed_by' => $player->id]);

        $again = $this->amf('amfActivateGoldPackageService.activateGoldPackage', ['PANFUGOLD2026']);
        $this->assertSame(1, $again->get('statusCode'));
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

    public function test_pinboard_stickers_and_tivola_are_complete_persistent_flows(): void
    {
        $player = $this->loginPlayer(['coins' => 100]);
        $receiver = User::factory()->create(['name' => 'Receiver']);

        $newMessage = new TypedObject('com.pandaland.informationserver.features.pinboard.vo.NewMessageVO', [
            'typeId' => 2,
            'content' => 'Party at my treehouse',
            'parentMessageId' => -1,
            'receivers' => [$receiver->id],
        ]);
        $added = $this->amf('amfPinboardService.addMessage', [$newMessage])->get('valueObject');
        $message = $added->get('createdMessageVO');
        $this->assertSame($player->name, $message->get('sender')->get('senderName'));
        $this->assertSame([$receiver->id], $added->get('receivers'));
        $this->assertSame('newPinboardMessage', $this->gameServerCommands[1]['command']);

        $pinboard = $this->amf('amfPinboardService.loadPinboardPaginated', [$receiver->id, 0, 10])->get('valueObject');
        $this->assertSame(1, $pinboard->get('undeletedMessagesCount'));
        $this->assertSame('Party at my treehouse', $pinboard->get('messages')[0]->get('content'));
        $this->assertSame([$receiver->id], $this->amf('amfPinboardService.loadPinboardedBuddies', [2])->get('valueObject'));

        $definitions = $this->amf('amfStickerService.loadStickerDefinitions')->get('valueObject');
        $this->assertCount(31, $definitions);
        $sticker = new TypedObject('com.pandaland.informationserver.features.stickers.vo.NewStickerVO', [
            'receiverId' => $receiver->id, 'definitionId' => 3, 'content' => $player->id.'|3',
        ]);
        $this->assertSame(0, $this->amf('amfStickerService.addNewSticker', [$sticker])->get('statusCode'));
        $this->assertSame(90, $player->refresh()->coins);
        $stickers = $this->amf('amfStickerService.loadStickers', [$receiver->id])->get('valueObject');
        $this->assertSame(3, $stickers[0]->get('definitionId'));
        $this->assertSame(1, $stickers[0]->get('amount'));

        $this->assertSame(0, $this->amf('amfTivolaService.updateScore', ['math', 450])->get('statusCode'));
        $this->amf('amfTivolaService.updateScore', ['math', 200]);
        $score = $this->amf('amfTivolaService.loadScore')->get('valueObject');
        $this->assertSame(450, $score->get('math'));
        $this->assertSame(0, $score->get('english'));

        $receiver->forceFill(['ticket_id' => str_repeat('b', 64)])->save();
        $this->withSession([]);
        $login = $this->amf('amfConnectionService.doLoginSession', [str_repeat('b', 64)])->get('valueObject');
        $this->assertSame(1, $login->get('unreadMessagesCount'));
        $this->assertSame(0, $this->amf('amfPinboardService.viewPinboard')->get('statusCode'));
        $this->assertSame(0, $this->amf('amfPinboardService.deleteMessage', [$message->get('messageId')])->get('statusCode'));
        $this->assertSame(0, $this->amf('amfPinboardService.loadPinboard', [$receiver->id])->get('valueObject')->get('undeletedMessagesCount'));
    }

    public function test_bolly_purchase_update_player_info_home_and_removal_flow(): void
    {
        $player = $this->loginPlayer(['coins' => 5000, 'goldpanda' => 1]);

        $purchase = $this->amf('amfBollyService.purchaseBolly', [20001]);
        $this->assertSame(0, $purchase->get('statusCode'));
        $bolly = $purchase->get('valueObject');
        $this->assertSame(20001, $bolly->get('id'));
        $this->assertSame('Blue Bolly', $bolly->get('name'));
        $this->assertSame(2500, $bolly->get('price'));
        $this->assertSame(2500, $player->refresh()->coins);
        $this->assertSame(10, $this->amf('amfBollyService.purchaseBolly', [20001])->get('statusCode'));

        $bolly->set('state', 'walking');
        $bolly->set('activity', 'bollyFlying');
        $bolly->set('energy', 72);
        $updated = $this->amf('amfBollyService.updateBolly', [$bolly])->get('valueObject');
        $this->assertSame('walking', $updated->get('state'));
        $this->assertSame(72, $updated->get('energy'));

        $card = $this->amf('amfPlayerService.getPlayerCard', [$player->id])->get('valueObject');
        $this->assertSame(20001, $card->get('bollies')[0]->get('id'));
        $home = $this->amf('amfPlayerService.getPlayerHome', [$player->id])->get('valueObject');
        $this->assertSame('walking', $home->get('bollies')[0]->get('state'));

        $this->assertSame(0, $this->amf('amfBollyService.removeBolly', [20001])->get('statusCode'));
        $this->assertDatabaseMissing('bollies', ['user_id' => $player->id, 'definition_id' => 20001]);
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
