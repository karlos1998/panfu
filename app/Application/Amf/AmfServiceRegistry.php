<?php

namespace App\Application\Amf;

use App\Application\Amf\Services\ActionAmfService;
use App\Application\Amf\Services\BeSmarterAmfService;
use App\Application\Amf\Services\BollyAmfService;
use App\Application\Amf\Services\BuddyFilterAmfService;
use App\Application\Amf\Services\BuddyListAmfService;
use App\Application\Amf\Services\ConnectionAmfService;
use App\Application\Amf\Services\GameAmfService;
use App\Application\Amf\Services\LanguageAmfService;
use App\Application\Amf\Services\PetAmfService;
use App\Application\Amf\Services\PinboardAmfService;
use App\Application\Amf\Services\PlayerAmfService;
use App\Application\Amf\Services\ProfileAmfService;
use App\Application\Amf\Services\RegistrationAmfService;
use App\Application\Amf\Services\SocialHighscoreAmfService;
use App\Application\Amf\Services\StickerAmfService;
use App\Application\Amf\Services\TivolaAmfService;
use App\Infrastructure\Amf\AmfException;
use Illuminate\Contracts\Container\Container;

final class AmfServiceRegistry
{
    /** @var array<string, array{class:class-string,methods:list<string>}> */
    private const SERVICES = [
        'amfActionService' => [
            'class' => ActionAmfService::class,
            'methods' => ['getLastDoneActionToday', 'performAction'],
        ],
        'amfBeSmarterService' => [
            'class' => BeSmarterAmfService::class,
            'methods' => ['loadBestResult'],
        ],
        'amfBollyService' => [
            'class' => BollyAmfService::class,
            'methods' => ['purchaseBolly', 'removeBolly', 'updateBolly'],
        ],
        'amfBuddyFilterService' => [
            'class' => BuddyFilterAmfService::class,
            'methods' => ['listFilteredBuddies', 'addFilteredBuddy', 'removeFilteredBuddy'],
        ],
        'amfBuddyListService' => [
            'class' => BuddyListAmfService::class,
            'methods' => ['getBuddyList', 'getCompleteBuddyList', 'changeBestFriend'],
        ],
        'amfConnectionService' => [
            'class' => ConnectionAmfService::class,
            'methods' => ['doLogin', 'doLoginSession', 'doLogout', 'doRegister', 'setEmailAddress', 'setBirthday', 'checkUserName', 'checkEmailAddress', 'ping'],
        ],
        'amfGameService' => [
            'class' => GameAmfService::class,
            'methods' => ['setHighScore', 'finishMinigame', 'getHighScoreLists'],
        ],
        'amfLanguageService' => [
            'class' => LanguageAmfService::class,
            'methods' => ['getSecureChatSnippets'],
        ],
        'amfPetService' => [
            'class' => PetAmfService::class,
            'methods' => ['buyPet', 'switchPet', 'updatePetState', 'removePet', 'feed', 'increaseHealth', 'getGameServer'],
        ],
        'amfPinboardService' => [
            'class' => PinboardAmfService::class,
            'methods' => ['addMessage', 'deleteMessage', 'loadPinboard', 'loadPinboardPaginated', 'viewPinboard', 'loadPinboardedBuddies'],
        ],
        'amfPlayerService' => [
            'class' => PlayerAmfService::class,
            'methods' => ['getStates', 'setState', 'updateTourFinished', 'addToBuddylist', 'removeFromBuddyList', 'purchaseItem', 'updateItems', 'removeItem', 'removeItems', 'getPlayerInfoList', 'getSmallPlayerInfoList', 'getPlayerCard', 'lockHome', 'getPlayerHome', 'updateFurnitures', 'updateScore', 'reportPlayer', 'updateHelperStatus', 'updatePlayerState'],
        ],
        'amfProfileService' => [
            'class' => ProfileAmfService::class,
            'methods' => ['getProfile'],
        ],
        'amfRegistrationService' => [
            'class' => RegistrationAmfService::class,
            'methods' => ['checkUserName', 'loadUsernameSuggestions', 'checkEmailAddress', 'register'],
        ],
        'amfSocialHighscoreService' => [
            'class' => SocialHighscoreAmfService::class,
            'methods' => ['getSocialHighscore'],
        ],
        'amfStickerService' => [
            'class' => StickerAmfService::class,
            'methods' => ['loadStickerDefinitions', 'loadStickers', 'addNewSticker', 'addNpcSticker'],
        ],
        'amfTivolaService' => [
            'class' => TivolaAmfService::class,
            'methods' => ['loadScore', 'updateScore'],
        ],
    ];

    public function __construct(private readonly Container $container) {}

    /** @param list<mixed> $parameters */
    public function call(string $target, array $parameters): mixed
    {
        if (! preg_match('/^(?<service>[A-Za-z][A-Za-z0-9_]*)[.\/](?<method>[A-Za-z][A-Za-z0-9_]*)$/D', $target, $matches)) {
            throw new AmfException("Unknown AMF target: {$target}");
        }

        $definition = self::SERVICES[$matches['service']] ?? null;
        if ($definition === null || ! in_array($matches['method'], $definition['methods'], true)) {
            throw new AmfException("Unknown AMF method: {$target}");
        }

        $service = $this->container->make($definition['class']);

        return $service->{$matches['method']}(...$parameters);
    }
}
