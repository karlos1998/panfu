<?php

namespace App\Application\Amf;

use App\Infrastructure\Amf\TypedObject;
use InvalidArgumentException;

final class ValueObjectFactory
{
    /** @param array<string, mixed> $properties */
    public function make(string $name, array $properties = []): TypedObject
    {
        $definition = self::DEFINITIONS[$name] ?? throw new InvalidArgumentException("Unknown AMF value object: {$name}");

        return new TypedObject($definition['type'], array_replace($definition['defaults'], $properties));
    }

    /** @var array<string, array{type:string, defaults:array<string, mixed>}> */
    private const DEFINITIONS = [
        'AmfResponse' => [
            'type' => 'com.pandaland.mvc.model.vo.AmfResponse',
            'defaults' => ['message' => '', 'statusCode' => 0, 'valueObject' => null],
        ],
        'Feedback' => [
            'type' => 'com.pandaland.mvc.model.vo.FeedbackVO',
            'defaults' => ['status' => 0, 'message' => ','],
        ],
        'List' => [
            'type' => 'com.pandaland.mvc.model.vo.ListVO',
            'defaults' => ['list' => null],
        ],
        'LoginResult' => [
            'type' => 'com.pandaland.mvc.model.vo.LoginResultVO',
            'defaults' => [
                'gameplayPanfu' => 0, 'playerInfo' => null, 'partnerTracking' => null,
                'ticketId' => 0, 'showTour' => null, 'showNewsletterScreen' => 0,
                'promoMessageKey' => '', 'gameServers' => null, 'date' => null,
                'loginCount' => 0, 'blockedUser' => null, 'membershipStatus' => 0,
                'email' => '', 'hungryPokoPets' => null, 'goldPandaDay' => null,
                'promoMembership' => null, 'unreadMessagesCount' => 0,
                'undeletedMessagesCount' => 0,
            ],
        ],
        'PartnerTracking' => [
            'type' => 'com.pandaland.informationserver.api.vo.PartnerTrackingVO',
            'defaults' => ['triggerId' => '', 'source' => ''],
        ],
        'PlayerInfo' => [
            'type' => 'com.pandaland.mvc.model.vo.PlayerInfoVO',
            'defaults' => [
                'bollies' => [], 'inactiveInventory' => [], 'activeInventory' => [], 'buddies' => [],
                'isTourFinished' => null, 'isSheriff' => 0, 'id' => 0, 'name' => '', 'age' => 0,
                'sex' => '', 'birthday' => null, 'coins' => 0, 'chatId' => '0', 'isPremium' => true,
                'isGuest' => false, 'currentGameServer' => 0, 'socialLevel' => 0, 'socialScore' => 0,
                'mood' => 400, 'lastLogin' => null, 'signupDate' => null, 'membershipStatus' => 0,
                'musicCollection' => null, 'pokoPets' => null, 'pokoPetsWithNoHealth' => null,
                'daysOnPanfu' => 0, 'helperStatus' => null, 'lastSeenACGlobal' => 0, 'state' => '',
            ],
        ],
        'GameServer' => [
            'type' => 'com.pandaland.mvc.model.vo.GameServerVO',
            'defaults' => [
                'id' => 0, 'name' => '', 'playercount' => 0, 'url' => '', 'port' => 0,
                'ageFrom' => 0, 'ageTo' => 0, 'premiumonly' => false, 'availableFor' => 0,
            ],
        ],
        'Item' => [
            'type' => 'com.pandaland.mvc.model.vo.ItemVO',
            'defaults' => [
                'id' => 0, 'name' => '', 'type' => '00', 'price' => 0, 'zettSort' => 0,
                'premium' => false, 'bought' => false, 'active' => false, 'movementType' => 0,
            ],
        ],
        'FurnitureData' => [
            'type' => 'com.pandaland.mvc.model.vo.FurnitureDataVO',
            'defaults' => [
                'parameters' => null, 'x' => 0, 'y' => 0, 'rot' => 0, 'uid' => null,
                'id' => null, 'type' => null, 'active' => null, 'premium' => null,
                'bought' => null, 'room' => null, 'roomID' => null,
            ],
        ],
        'SmallPlayerInfo' => [
            'type' => 'com.pandaland.mvc.model.vo.SmallPlayerInfoVO',
            'defaults' => ['playerId' => 0, 'playerName' => '', 'currentGameServer' => 0],
        ],
        'Buddy' => [
            'type' => 'com.pandaland.mvc.model.vo.BuddyVO',
            'defaults' => [
                'id' => 0, 'name' => '', 'premium' => null, 'bestfriend' => null,
                'currentGameServer' => 0, 'socialLevel' => 0,
            ],
        ],
        'BuddyFilter' => [
            'type' => 'com.pandaland.mvc.model.vo.BuddyFilterVO',
            'defaults' => ['buddy1' => -1, 'buddy2' => -1, 'level' => 1],
        ],
        'Bolly' => [
            'type' => 'com.pandaland.mvc.model.vo.BollyVO',
            'defaults' => [
                'id' => 0, 'name' => '', 'type' => '', 'price' => 0, 'state' => 'normal',
                'activity' => 'bollyNormal', 'health' => 100, 'rest' => 100, 'energy' => 100,
                'x' => 0, 'y' => 0, 'z' => 0, 'colour' => 0, 'style' => 'normal',
            ],
        ],
        'Date' => [
            'type' => 'com.pandaland.mvc.model.vo.DateVO',
            'defaults' => ['date' => null],
        ],
        'Reward' => [
            'type' => 'com.pandaland.mvc.model.vo.RewardVO',
            'defaults' => ['levelStatus' => 0, 'type' => '', 'item' => null, 'number' => 0],
        ],
        'State' => [
            'type' => 'com.pandaland.mvc.model.vo.StateVO',
            'defaults' => [
                'playerId' => 0, 'cathegoryId' => 0, 'nameId' => 0,
                'stateValue' => 0, 'lastChanged' => null,
            ],
        ],
        'Inventory' => [
            'type' => 'com.pandaland.informationserver.api.vo.InventoryVO',
            'defaults' => ['inactiveItems' => null, 'activeItems' => null],
        ],
        'HomeData' => [
            'type' => 'com.pandaland.mvc.model.vo.HomeDataVO',
            'defaults' => [
                'id' => 0, 'playerID' => 0, 'locked' => false, 'furnitureList' => null,
                'trackList' => null, 'pets' => null, 'pokoPets' => null, 'bollies' => null,
            ],
        ],
        'UserActionDaily' => [
            'type' => 'com.pandaland.mvc.model.vo.UserActionDailyVO',
            'defaults' => [
                'playerId' => 0, 'actionId' => 0, 'doneToday' => 0, 'time' => null,
                'lastDoneActionTime' => null, 'doneInTime' => 0,
            ],
        ],
        'SecurityChatItem' => [
            'type' => 'com.pandaland.mvc.model.vo.SecurityChatItemVO',
            'defaults' => ['children' => [], 'label' => ''],
        ],
        'GameHighScores' => [
            'type' => 'com.pandaland.features.highscores.vo.GameHighScoresVO',
            'defaults' => ['id' => 0, 'dailyHighscores' => null, 'weeklyHighscores' => null, 'overAllHighscores' => null],
        ],
        'HighScoreEntry' => [
            'type' => 'com.pandaland.features.highscores.vo.HighScoreEntryVO',
            'defaults' => ['ranking' => 0, 'playerID' => '', 'score' => 0, 'playerName' => ''],
        ],
        'Profile' => [
            'type' => 'com.pandaland.mvc.model.vo.ProfileVO',
            'defaults' => [
                'id' => 0, 'lastBlocked' => 0, 'bestFriend' => '', 'movie' => '', 'movieChecked' => false,
                'color' => '', 'colorChecked' => false, 'hobby' => '', 'hobbyChecked' => false,
                'book' => '', 'bookChecked' => false, 'song' => '', 'songChecked' => false,
                'band' => '', 'bandChecked' => false, 'schoolSubject' => '', 'schoolSubjectChecked' => false,
                'sport' => '', 'sportChecked' => false, 'animal' => '', 'animalChecked' => false,
                'relStatus' => '', 'relStatusChecked' => false, 'motto' => '', 'mottoChecked' => false,
                'bestChar' => '', 'bestCharChecked' => false, 'worstChar' => '', 'worstCharChecked' => false,
                'likeMost' => '', 'likeMostChecked' => false, 'likeLeast' => '', 'likeLeastChecked' => false,
            ],
        ],
        'SocialHighscore' => [
            'type' => 'com.pandaland.mvc.model.vo.SocialHighscoreVO',
            'defaults' => ['gameID' => 0, 'playerID' => -1, 'otherPlayerID' => -1, 'playerScore' => 0, 'otherPlayerScore' => 0],
        ],
        'Sender' => [
            'type' => 'com.pandaland.informationserver.features.pinboard.vo.SenderVO',
            'defaults' => ['senderId' => 0, 'senderName' => ''],
        ],
        'Message' => [
            'type' => 'com.pandaland.informationserver.features.pinboard.vo.MessageVO',
            'defaults' => [
                'sender' => null, 'messageId' => 0, 'read' => false, 'createdAt' => null,
                'replied' => false, 'typeId' => 0, 'content' => '', 'parentMessageId' => -1,
            ],
        ],
        'AddedMessage' => [
            'type' => 'com.pandaland.informationserver.features.pinboard.vo.AddedMessageVO',
            'defaults' => ['createdMessageVO' => null, 'receivers' => []],
        ],
        'Pinboard' => [
            'type' => 'com.pandaland.informationserver.features.pinboard.vo.PinboardVO',
            'defaults' => ['undeletedMessagesCount' => 0, 'messages' => [], 'offset' => 0, 'limit' => 0],
        ],
        'Sticker' => [
            'type' => 'com.pandaland.informationserver.features.stickers.vo.StickerVO',
            'defaults' => ['definitionId' => 0, 'amount' => 0],
        ],
        'StickerRestrictions' => [
            'type' => 'com.pandaland.informationserver.features.stickers.vo.StickerRestrictionsVO',
            'defaults' => ['minLevel' => 0, 'coins' => 0, 'premium' => false],
        ],
        'StickerDefinition' => [
            'type' => 'com.pandaland.informationserver.features.stickers.vo.StickerDefinitionVO',
            'defaults' => ['id' => 0, 'points' => 0, 'restrictions' => null],
        ],
        'NewSticker' => [
            'type' => 'com.pandaland.informationserver.features.stickers.vo.NewStickerVO',
            'defaults' => ['receiverId' => 0, 'definitionId' => 0, 'content' => ''],
        ],
        'TivolaScore' => [
            'type' => 'com.pandaland.informationserver.features.tivola.TivolaScoreVO',
            'defaults' => ['math' => 0, 'english' => 0, 'german' => 0, 'concentration' => 0, 'slot' => 0],
        ],
    ];
}
