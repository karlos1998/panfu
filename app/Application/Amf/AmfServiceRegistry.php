<?php

namespace App\Application\Amf;

use App\Application\Amf\Services\ActionAmfService;
use App\Application\Amf\Services\BuddyFilterAmfService;
use App\Application\Amf\Services\BuddyListAmfService;
use App\Application\Amf\Services\ConnectionAmfService;
use App\Application\Amf\Services\GameAmfService;
use App\Application\Amf\Services\LanguageAmfService;
use App\Application\Amf\Services\PetAmfService;
use App\Application\Amf\Services\PlayerAmfService;
use App\Application\Amf\Services\ProfileAmfService;
use App\Application\Amf\Services\RegistrationAmfService;
use App\Application\Amf\Services\SocialHighscoreAmfService;
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
        'amfBuddyFilterService' => [
            'class' => BuddyFilterAmfService::class,
            'methods' => ['listFilteredBuddies'],
        ],
        'amfBuddyListService' => [
            'class' => BuddyListAmfService::class,
            'methods' => ['getCompleteBuddyList'],
        ],
        'amfConnectionService' => [
            'class' => ConnectionAmfService::class,
            'methods' => ['doLogin', 'doLoginSession', 'doRegister', 'setEmailAddress', 'checkUserName', 'checkEmailAddress', 'ping'],
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
        'amfPlayerService' => [
            'class' => PlayerAmfService::class,
            'methods' => ['getStates', 'setState', 'updateTourFinished', 'addToBuddylist', 'purchaseItem', 'updateItems', 'removeItems', 'getPlayerInfoList', 'getPlayerCard', 'lockHome', 'getPlayerHome', 'updateFurnitures', 'updateScore'],
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
