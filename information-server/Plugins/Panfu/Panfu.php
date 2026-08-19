<?php

/**
 * This file is part of openPanfu, a project that imitates the Flex remoting
 * and gameservers of Panfu.
 *
 * @category Utility
 * @author Altro50 <altro50@msn.com>
 */

session_start();

class Panfu
{
    private static $wordFilter = [];
    private static $levelDefinitions = null;

    /**
     * Sets and returns a session ticket for the user.
     * @author Altro50 <altro50@msn.com>
     * @return SecurityChatItemVO[]
     */
    public static function generateSafeChat()
    {
        require_once AMFPHP_ROOTPATH . '/Services/Vo/SecurityChatItemVO.php';
        $data = json_decode(file_get_contents(__DIR__ . '/safechatall.json'));
        $snippets = array();
        $i = 0;
        foreach($data as $entry) {
            $snippets[$i] = Self::traverseChildren($entry);
            $i++;
        }
        return $snippets;
    }

    /**
     * Returns children
     * @author Altro50 <altro50@msn.com>
     * @return SecurityChatItemVO[]
     */
    private static function traverseChildren($safeChatEntry)
    {
        $valueObject = new SecurityChatItemVO();
        $valueObject->label = $safeChatEntry->label . " ";
        foreach($safeChatEntry->children as $child) {
            array_push($valueObject->children, Self::traverseChildren($child));
        }
        return $valueObject;
    }
    
    /**
     * Sets and returns a session ticket for the user.
     * @author Altro50 <altro50@msn.com>
     * @return int
     */
    public static function generateSessionId()
    {
        // The Flash client and Java gameserver exchange the ticket as a signed
        // 32-bit integer. A textual ticket (for example, "OPS_...") is coerced
        // to 0 by the client and makes every gameserver login fail.
        $sessionId = random_int(100000000, 2147483647);
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare("UPDATE users SET ticket_id = :ticket WHERE id = :id");
        $stmt->bindParam(':ticket', $sessionId);
        $stmt->bindParam(':id', $_SESSION["id"]);
        $stmt->execute();
        return $sessionId;
    }

    /**
     * Ran every 10 minutes (Triggered by client).
     * @author Altro50 <altro50@msn.com>
     * @return ListVO Rewards for playing so long.
     */
    public static function played10()
    {
        require_once AMFPHP_ROOTPATH . "/Services/Vo/ListVO.php";
        require_once AMFPHP_ROOTPATH . "/Services/Vo/RewardVO.php";

        $listVo = new ListVO();
        $listVo->list = [];
        $levelDefinitions = Panfu::getLevelDefinitions();

        $userData = Panfu::getUserDataById($_SESSION['id']);
        $currentLevel = (int)($userData['social_level'] ?? 1);
        $maxLevel = Panfu::configuredMaxLevel();

        if($currentLevel >= $maxLevel) {
            return $listVo;
        }

        $level = Panfu::getLevel($currentLevel);

        if($level !== null) {
            $newScore = (int)($userData['social_score'] ?? 0) + Panfu::socialScoreIncrementForLevel($currentLevel);
            $levelUp = new RewardVO();
            $levelUp->type = "sp";
            
            if($newScore >= 100) {
                // Yay, the user leveled!

                $newScore = $newScore - 100;
                $newLevel = min($currentLevel + 1, $maxLevel);

                if($newLevel >= $maxLevel) {
                    $newScore = 0;
                }

                $levelUp->levelStatus = 1;
                
                $levelUp->number = $newScore;
                array_push($listVo->list, $levelUp);

                // Now we push the level rewards, what do they get for leveling?
                $nextLevel = Panfu::getLevel($newLevel);
                if($nextLevel !== null && isset($nextLevel->rewards) && is_array($nextLevel->rewards)) {
                    foreach($nextLevel->rewards as $reward) {
                        $toPush = new RewardVO();
                        $toPush->type = $reward->type;
                        switch($reward->type) {
                            case "item":
                                Panfu::addItemToUser((int)$reward->value);
                                $toPush->item = Panfu::getItemVo((int)$reward->value);
                                $toPush->item->active = false;
                                $toPush->item->bought = true;
                                break;
                            case "score":
                                $toPush->number = (int)$reward->value;
                                break;
                            default:
                                Console::log("played10 > unknown reward type " . $reward->type . "! (No handling code)");
                                break;
                        }
                        array_push($listVo->list, $toPush);
                    }
                }

                // Set their level to their new level.
                Panfu::setSocialLevel($_SESSION['id'], $newLevel);
            } else {
                // Huh? What's going on??
                // Why is this here twice?!
                $levelUp->number = min(99, $newScore);
                array_push($listVo->list, $levelUp);

                // Well, you see, the game will completely deny any items (with an error)
                // if you don't send the levelUp first.

                // btw, thank you satoshi for telling me this.
            }

            // Set their score to their new score.
            Panfu::setSocialScore($_SESSION['id'], min(99, $newScore));

        } else {
            Console::log("Missing level definition: " . $currentLevel);
        }

        return $listVo;
    }

    public static function played10CooldownSeconds()
    {
        return max(1, Panfu::envInt('PANFU_LEVEL_TICK_SECONDS', 600) - 20);
    }

    private static function getLevelDefinitions()
    {
        if(Panfu::$levelDefinitions == null) {
            Panfu::$levelDefinitions = json_decode(file_get_contents(__DIR__ . '/levels.json'));
        }

        return Panfu::$levelDefinitions;
    }

    private static function configuredMaxLevel()
    {
        $levelDefinitions = Panfu::getLevelDefinitions();

        return min((int)$levelDefinitions->maxLevel, Panfu::envInt('PANFU_LEVEL_MAX', (int)$levelDefinitions->maxLevel));
    }

    private static function socialScoreIncrementForLevel($level)
    {
        $baseMinutes = max(1, Panfu::envFloat('PANFU_LEVEL_BASE_MINUTES', 10));
        $growthRate = max(0, Panfu::envFloat('PANFU_LEVEL_GROWTH_RATE', 0.10));
        $tickMinutes = max(1, Panfu::envInt('PANFU_LEVEL_TICK_SECONDS', 600)) / 60;
        $requiredMinutes = $baseMinutes * pow(1 + $growthRate, max(0, $level - 1));

        return max(1, (int)round(100 * ($tickMinutes / $requiredMinutes)));
    }

    private static function envInt($key, $default)
    {
        $value = getenv($key);

        return is_numeric($value) ? (int)$value : $default;
    }

    private static function envFloat($key, $default)
    {
        $value = getenv($key);

        return is_numeric($value) ? (float)$value : $default;
    }

    /**
     * Gets a level from the level definitions.
     * @author Altro50 <altro50@msn.com>
     * @param int $level The level.
     * @return object level definition.
     */
    public static function getLevel($level)
    {
        $levelDefinitions = Panfu::getLevelDefinitions();

        foreach($levelDefinitions->levels as $levelObj) {
            if($levelObj->level == $level) {
                return $levelObj;
            }
        }

        return null;
    }

    /**
     * Sets a user's social level.
     * @author Altro50 <altro50@msn.com>
     * @param int $userId User id to update.
     * @param int $level the new social level.
     * @return Void
     */
    public static function setSocialLevel($userId, $level)
    {
        $pdo = Database::getPDO();
        $update = $pdo->prepare("UPDATE users SET social_level = :social_level WHERE id = :id");
        $update->bindParam(":social_level", $level);
        $update->bindParam(":id", $userId);
        $update->execute();
    }
    /**
     * Sets a user's social score.
     * @author Altro50 <altro50@msn.com>
     * @param int $userId User id to update.
     * @param int $score the new social score.
     * @return Void
     */
    public static function setSocialScore($userId, $score)
    {
        $pdo = Database::getPDO();
        $update = $pdo->prepare("UPDATE users SET social_score = :social_score WHERE id = :id");
        $update->bindParam(":social_score", $score);
        $update->bindParam(":id", $userId);
        $update->execute();
    }

    /**
     * Returns a playerInfoVo for the specified user.
     * @author Altro50 <altro50@msn.com>
     * @param int $userId User id to get PlayerInfo for.
     * @return PlayerInfoVO
     */
    public static function getPlayerInfoForId($userId)
    {
        require_once AMFPHP_ROOTPATH . "/Services/Vo/PlayerInfoVO.php";
        try {
            $userData = Panfu::getUserDataById($userId);
            $playerInfo = new PlayerInfoVO();
            $playerInfo->id = (int)$userData['id'];
            $playerInfo->name = $userData['name'];
            $playerInfo->coins = (int)($userData['coins'] ?? 0);
            $playerInfo->isSheriff = (int)($userData['sheriff'] ?? 0);
            $playerInfo->isPremium = (boolean)($userData['goldpanda'] > 0);
            $playerInfo->sex = ($userData['sex'] == 1 ? 'girl' : 'boy');
            $playerInfo->helperStatus = false; // obsolete, if the account is older than 2012, this will be set to false anyways.
            $playerInfo->isTourFinished = true; // TODO: implement tour
            $playerInfo->membershipStatus = (int)($userData['goldpanda'] ?? 0);
            $playerInfo->currentGameServer = (int)($userData['current_gameserver'] ?? 0);
            $playerInfo->socialLevel = (int)($userData['social_level'] ?? 0);
            $playerInfo->socialScore = (int)($userData['social_score'] ?? 0);
            $playerInfo->activeInventory = Panfu::getInventory($userData['id'], true);
            $playerInfo->inactiveInventory = Panfu::getInventory($userData['id'], false);
            $playerInfo->buddies = Panfu::getBuddiesForUserId($userId);
            $playerInfo->pokoPets = Panfu::getPokoPets($userId);
            $playerInfo->pokoPetsWithNoHealth = Panfu::getPokoPetIdsWithNoHealth($userId);

            // Let's calculate the days since register.
            $now = time();
            $difference = $now - strtotime($userData['created_at']);
            $playerInfo->daysOnPanfu = round($difference / (60 * 60 * 24));
            return $playerInfo;
        } catch(Exception $e) {
            Console::log("Error getting PlayerInfoVO \o/", $e);
            return null;
        }
    }

    /**
     * Catalogue rules recovered from the Pokopets catalogue artwork and the
     * level requirements in conf/config.xml.
     *
     * @return array|null
     */
    public static function getPokoPetDefinition($type)
    {
        $definitions = [
            1 => ['name' => 'Helmet', 'price' => 4500, 'level' => 0, 'premium' => true, 'voucher' => false],
            2 => ['name' => 'Stella', 'price' => 8000, 'level' => 0, 'premium' => true, 'voucher' => false],
            3 => ['name' => 'Soque', 'price' => 1200, 'level' => 20, 'premium' => true, 'voucher' => false],
            4 => ['name' => 'Cuddle', 'price' => 7500, 'level' => 20, 'premium' => true, 'voucher' => false],
            5 => ['name' => 'Woody', 'price' => 0, 'level' => 0, 'premium' => false, 'voucher' => true],
            6 => ['name' => 'Bugsy', 'price' => 60, 'level' => 1, 'premium' => true, 'voucher' => false],
            7 => ['name' => 'Tork', 'price' => 5500, 'level' => 25, 'premium' => true, 'voucher' => false],
            9 => ['name' => 'Marieta', 'price' => 60, 'level' => 0, 'premium' => false, 'voucher' => false],
        ];

        $type = (int)$type;
        return isset($definitions[$type]) ? $definitions[$type] : null;
    }

    /**
     * Returns every Pokopet owned by a player.
     *
     * The Flash client intentionally receives anonymous objects here. Although
     * it registers PokoPetVO, it never registers the nested
     * PokoPetPropertiesVO alias. Sending a typed PokoPetVO would therefore
     * leave its strongly typed `properties` field null in Ruffle. The client
     * already contains a compatibility mapper which converts anonymous pet
     * objects (and their properties) into the correct ActionScript VOs.
     *
     * @return stdClass[]
     */
    public static function getPokoPets($userId)
    {
        $pdo = Database::getPDO();
        $statement = $pdo->prepare("SELECT * FROM pokopets WHERE user_id = :userId ORDER BY selected DESC, id ASC");
        $statement->bindValue(':userId', (int)$userId, PDO::PARAM_INT);
        $statement->execute();

        $pets = [];
        foreach($statement->fetchAll() as $row) {
            $pets[] = Panfu::getPokoPetVoFromRow($row);
        }

        return $pets;
    }

    /**
     * Returns Pokopet ids which should trigger the low-health login warning.
     *
     * @return int[]
     */
    public static function getPokoPetIdsWithNoHealth($userId)
    {
        $pdo = Database::getPDO();
        $statement = $pdo->prepare("SELECT id FROM pokopets WHERE user_id = :userId AND health <= 0 ORDER BY id ASC");
        $statement->bindValue(':userId', (int)$userId, PDO::PARAM_INT);
        $statement->execute();

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * Hydrates the anonymous object shape expected by the Flash client's
     * PokoPet compatibility mapper.
     *
     * @return stdClass
     */
    public static function getPokoPetVoFromRow($row)
    {
        require_once AMFPHP_ROOTPATH . "/Services/Vo/DateVO.php";

        $pet = new stdClass();
        $pet->id = (int)$row['id'];
        $pet->name = (string)$row['name'];
        $pet->type = (string)$row['type'];
        $pet->selected = (bool)$row['selected'];
        $pet->x = (int)$row['x'];
        $pet->y = (int)$row['y'];
        $pet->state = (string)$row['state'];

        $abilities = json_decode((string)($row['abilities'] ?? ''), true);
        $pet->abilities = is_array($abilities) ? array_values(array_map('intval', $abilities)) : [];

        $pet->properties = new stdClass();
        $pet->properties->health = (int)$row['health'];
        $pet->properties->maxHealth = (int)$row['max_health'];
        $pet->properties->speed = (int)$row['speed'];
        $pet->properties->agility = (int)$row['agility'];
        $pet->properties->power = (int)$row['power'];
        $pet->properties->experience = (int)$row['experience'];
        $pet->properties->level = (int)$row['level'];

        if(!empty($row['last_fed'])) {
            $pet->lastFed = new DateVO();
            $pet->lastFed->date = strtotime($row['last_fed']) * 1000;
        }

        return $pet;
    }

    /**
     * Atomically purchases a Pokopet and returns a service-ready result.
     *
     * @return array{statusCode:int,message:string,pet:?stdClass}
     */
    public static function buyPokoPet($userId, $type, $name, $voucherReserved = false)
    {
        $type = (int)$type;
        $definition = Panfu::getPokoPetDefinition($type);
        if($definition === null) {
            return ['statusCode' => 3, 'message' => 'Unknown Pokopet type.', 'pet' => null];
        }

        $name = trim((string)$name);
        if($name === '') {
            $name = $definition['name'];
        }
        $name = mb_substr($name, 0, 50);

        $pdo = Database::getPDO();
        try {
            $pdo->beginTransaction();

            $userStatement = $pdo->prepare("SELECT coins, goldpanda, social_level FROM users WHERE id = :userId FOR UPDATE");
            $userStatement->bindValue(':userId', (int)$userId, PDO::PARAM_INT);
            $userStatement->execute();
            $user = $userStatement->fetch();
            if(!$user) {
                $pdo->rollBack();
                return ['statusCode' => 1, 'message' => 'Player not found.', 'pet' => null];
            }

            $duplicate = $pdo->prepare("SELECT id FROM pokopets WHERE user_id = :userId AND type = :type LIMIT 1 FOR UPDATE");
            $duplicate->bindValue(':userId', (int)$userId, PDO::PARAM_INT);
            $duplicate->bindValue(':type', $type, PDO::PARAM_INT);
            $duplicate->execute();
            if($duplicate->fetch()) {
                $pdo->rollBack();
                return ['statusCode' => 10, 'message' => 'Pokopet already owned.', 'pet' => null];
            }

            if($definition['premium'] && (int)$user['goldpanda'] <= 0) {
                $pdo->rollBack();
                return ['statusCode' => 5, 'message' => 'A Gold Panda membership is required.', 'pet' => null];
            }

            if((int)$user['social_level'] < $definition['level']) {
                $pdo->rollBack();
                return ['statusCode' => 6, 'message' => 'The required Panda level has not been reached.', 'pet' => null];
            }

            if($definition['voucher']) {
                $voucher = $pdo->prepare("SELECT id FROM inventories WHERE user_id = :userId AND item_id = 101830 LIMIT 1 FOR UPDATE");
                $voucher->bindValue(':userId', (int)$userId, PDO::PARAM_INT);
                $voucher->execute();
                $voucherRow = $voucher->fetch();
                if(!$voucherReserved || !$voucherRow) {
                    $pdo->rollBack();
                    return ['statusCode' => 13, 'message' => 'A valid Pokopet voucher is required.', 'pet' => null];
                }

                $removeVoucher = $pdo->prepare("DELETE FROM inventories WHERE id = :id");
                $removeVoucher->bindValue(':id', (int)$voucherRow['id'], PDO::PARAM_INT);
                $removeVoucher->execute();
            } elseif((int)$user['coins'] < $definition['price']) {
                $pdo->rollBack();
                return ['statusCode' => 412, 'message' => 'Not enough coins.', 'pet' => null];
            } elseif($definition['price'] > 0) {
                $deduct = $pdo->prepare("UPDATE users SET coins = coins - :price, updated_at = :updatedAt WHERE id = :userId");
                $deduct->bindValue(':price', $definition['price'], PDO::PARAM_INT);
                $deduct->bindValue(':updatedAt', gmdate('Y-m-d H:i:s'));
                $deduct->bindValue(':userId', (int)$userId, PDO::PARAM_INT);
                $deduct->execute();
            }

            $now = gmdate('Y-m-d H:i:s');
            $insert = $pdo->prepare(
                "INSERT INTO pokopets (user_id, type, name, selected, state, health, max_health, speed, agility, power, experience, level, abilities, created_at, updated_at)
                 VALUES (:userId, :type, :name, 0, 'idle', 5, 5, 1, 1, 1, 0, 1, :abilities, :createdAt, :updatedAt)"
            );
            $insert->bindValue(':userId', (int)$userId, PDO::PARAM_INT);
            $insert->bindValue(':type', $type, PDO::PARAM_INT);
            $insert->bindValue(':name', $name);
            $insert->bindValue(':abilities', '[]');
            $insert->bindValue(':createdAt', $now);
            $insert->bindValue(':updatedAt', $now);
            $insert->execute();
            $petId = (int)$pdo->lastInsertId();

            $petStatement = $pdo->prepare("SELECT * FROM pokopets WHERE id = :id");
            $petStatement->bindValue(':id', $petId, PDO::PARAM_INT);
            $petStatement->execute();
            $pet = Panfu::getPokoPetVoFromRow($petStatement->fetch());

            $pdo->commit();
            return ['statusCode' => 0, 'message' => 'Pokopet added.', 'pet' => $pet];
        } catch(Throwable $error) {
            if($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Console::log('Could not purchase Pokopet: ' . $error->getMessage());
            return ['statusCode' => 2, 'message' => 'Could not save the Pokopet.', 'pet' => null];
        }
    }

    /** @return stdClass|null */
    public static function updatePokoPetState($userId, $petId, $state)
    {
        $allowedStates = ['normal', 'idle', 'sleeping', 'playing', 'eating', 'walking', 'denying', 'decrease', 'rescue', 'tricking'];
        $state = (string)$state;
        if(!in_array($state, $allowedStates, true)) {
            return null;
        }

        $pdo = Database::getPDO();
        $update = $pdo->prepare("UPDATE pokopets SET state = :state, updated_at = :updatedAt WHERE id = :id AND user_id = :userId");
        $update->bindValue(':state', $state);
        $update->bindValue(':updatedAt', gmdate('Y-m-d H:i:s'));
        $update->bindValue(':id', (int)$petId, PDO::PARAM_INT);
        $update->bindValue(':userId', (int)$userId, PDO::PARAM_INT);
        $update->execute();

        return Panfu::getPokoPetById($userId, $petId);
    }

    /** @return stdClass|null */
    public static function getPokoPetById($userId, $petId)
    {
        $pdo = Database::getPDO();
        $statement = $pdo->prepare("SELECT * FROM pokopets WHERE id = :id AND user_id = :userId LIMIT 1");
        $statement->bindValue(':id', (int)$petId, PDO::PARAM_INT);
        $statement->bindValue(':userId', (int)$userId, PDO::PARAM_INT);
        $statement->execute();
        $row = $statement->fetch();

        return $row ? Panfu::getPokoPetVoFromRow($row) : null;
    }

    public static function switchPokoPet($userId, $petId)
    {
        $pdo = Database::getPDO();
        try {
            $pdo->beginTransaction();
            $clear = $pdo->prepare("UPDATE pokopets SET selected = 0, state = CASE WHEN state = 'walking' THEN 'idle' ELSE state END WHERE user_id = :userId");
            $clear->bindValue(':userId', (int)$userId, PDO::PARAM_INT);
            $clear->execute();

            if((int)$petId >= 0) {
                $select = $pdo->prepare("UPDATE pokopets SET selected = 1, state = 'walking', updated_at = :updatedAt WHERE id = :id AND user_id = :userId");
                $select->bindValue(':updatedAt', gmdate('Y-m-d H:i:s'));
                $select->bindValue(':id', (int)$petId, PDO::PARAM_INT);
                $select->bindValue(':userId', (int)$userId, PDO::PARAM_INT);
                $select->execute();
                if($select->rowCount() === 0) {
                    $pdo->rollBack();
                    return false;
                }
            }

            $pdo->commit();
            return true;
        } catch(Throwable $error) {
            if($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Console::log('Could not switch Pokopet: ' . $error->getMessage());
            return false;
        }
    }

    public static function removePokoPet($userId, $petId)
    {
        $pdo = Database::getPDO();
        $delete = $pdo->prepare("DELETE FROM pokopets WHERE id = :id AND user_id = :userId");
        $delete->bindValue(':id', (int)$petId, PDO::PARAM_INT);
        $delete->bindValue(':userId', (int)$userId, PDO::PARAM_INT);
        $delete->execute();

        return $delete->rowCount() > 0;
    }

    /** @return int|null */
    public static function feedPokoPet($userId, $petId)
    {
        $pdo = Database::getPDO();
        $now = gmdate('Y-m-d H:i:s');
        $update = $pdo->prepare("UPDATE pokopets SET health = max_health, last_fed = :lastFed, state = 'eating', updated_at = :updatedAt WHERE id = :id AND user_id = :userId");
        $update->bindValue(':lastFed', $now);
        $update->bindValue(':updatedAt', $now);
        $update->bindValue(':id', (int)$petId, PDO::PARAM_INT);
        $update->bindValue(':userId', (int)$userId, PDO::PARAM_INT);
        $update->execute();

        $pet = Panfu::getPokoPetById($userId, $petId);
        return $pet ? (int)$pet->properties->health : null;
    }

    /** @return stdClass|null */
    public static function increaseSelectedPokoPetHealth($userId)
    {
        $pdo = Database::getPDO();
        $update = $pdo->prepare("UPDATE pokopets SET health = health + 1, updated_at = :updatedAt WHERE user_id = :userId AND selected = 1 AND health < max_health");
        $update->bindValue(':updatedAt', gmdate('Y-m-d H:i:s'));
        $update->bindValue(':userId', (int)$userId, PDO::PARAM_INT);
        $update->execute();
        if($update->rowCount() === 0) {
            return null;
        }

        $statement = $pdo->prepare("SELECT * FROM pokopets WHERE user_id = :userId AND selected = 1 LIMIT 1");
        $statement->bindValue(':userId', (int)$userId, PDO::PARAM_INT);
        $statement->execute();
        $row = $statement->fetch();

        return $row ? Panfu::getPokoPetVoFromRow($row) : null;
    }

    /**
     * Gets the piece of furniture from the database as a FurnitureDataVO
     * @author Altro50 <altro50@msn.com>
     * @param int $itemId
     * @return FurnitureDataVO
     */
    public static function getFurnitureVo($itemId, $inventoryEntry = null)
    {
        require_once AMFPHP_ROOTPATH . "/Services/Vo/FurnitureDataVO.php";
        $response = new FurnitureDataVO();
        $item = Panfu::getItem($itemId);
        if(!$item) {
            return null;
        }
        $response->uid = isset($inventoryEntry['id']) ? (int)$inventoryEntry['id'] : (int)$itemId;
        $response->id = $itemId;
        $response->type = $item['type'];
        $response->active = isset($inventoryEntry['active']) ? (bool)$inventoryEntry['active'] : false;
        $response->premium = true;
        $response->bought = isset($inventoryEntry['bought']) ? (bool)$inventoryEntry['bought'] : true;
        $response->x = isset($inventoryEntry['x']) ? (int)$inventoryEntry['x'] : 0;
        $response->y = isset($inventoryEntry['y']) ? (int)$inventoryEntry['y'] : 0;
        $response->rot = isset($inventoryEntry['rot']) ? (int)$inventoryEntry['rot'] : 0;
        $response->room = isset($inventoryEntry['room']) ? (int)$inventoryEntry['room'] : 0;
        // Keep the old property for compatibility with older Flash clients.
        $response->roomID = $response->room;
        return $response;
    }

    public static function getFurniture($userId)
    {
        $pdo = Database::getPDO();
        $statement = $pdo->prepare("SELECT * FROM inventories WHERE user_id = :id");
        $statement->bindParam(":id", $userId, PDO::PARAM_INT);
        $statement->execute();
        $items = [];
        if($statement->rowCount() > 0) {
            $i = 0;
            foreach ($statement as $inventoryEntry) {
                $item = Panfu::getItem($inventoryEntry['item_id']);
                if($item && Panfu::isFurniture($item['type'])) {
                    $furniture = Panfu::getFurnitureVo($inventoryEntry['item_id'], $inventoryEntry);
                    if(!$furniture) {
                        continue;
                    }
                    $items[$i] = $furniture;
                    $i++;
                }
            }
        }
        return $items;
    }

    /**
     * Returns the gameservers on db as GameServerVOs
     * @author Altro50 <altro50@msn.com>
     * @return GameServerVO[]
     */
    public static function getGameServers()
    {
        require_once AMFPHP_ROOTPATH . "/Services/Vo/GameServerVO.php";
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare("SELECT * FROM gameservers");
        $stmt->execute();
        $servers = $stmt->fetchAll();

        $gameServers = array();
        $i = 0;
        foreach ($servers as $gs) {
            $gameServers[$i] = new GameServerVO();
            $gameServers[$i]->id = $gs['id'];
            $gameServers[$i]->name = $gs['name'];
            $gameServers[$i]->url = $gs['url'];
            $gameServers[$i]->port = $gs['port'];
            $gameServers[$i]->playercount = $gs['player_count'];
            $i++;
        }
        return $gameServers;
    }

    public static function getGameServerKey($id)
    {
        $pdo = Database::getPDO();
        $statement = $pdo->prepare("SELECT secret_key FROM gameservers WHERE id=:id");
        $statement->bindParam(":id", $id, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetch()["secret_key"];
    }

    /**
     * Log-in the user in the loginVO data.
     * @author Altro50 <altro50@msn.com>
     * @param loginVO $loginVO Login data
     * @return boolean
     */

    public static function loginUserWithVo($loginVO)
    {
        if(isset($loginVO->_explicitType) && $loginVO->_explicitType == "com.pandaland.mvc.model.vo.LoginVO") {
            $username = $loginVO->playerName;
            $password = $loginVO->pw;
            // Make sure the username has been taken.
            if(!Panfu::usernameNotTaken($username)) {
                $userData = Panfu::getUserDataByUsername($username);
                if(password_verify($password, $userData['password'])) {
                    $_SESSION["id"] = $userData['id'];
                    return true;
                }
            }
            return false;
        }
        return false;
    }

    /**
     * Log-in the user with a session id.
     * @author Christiaan Bultena <christiaanbultena49@gmail.com>
     * @author Altro50 <altro50@msn.com>
     * @param ticketId
     * @return boolean
     */
    public static function doLoginSession($ticketId)
    {
        if($ticketId === null || $ticketId === "" || strlen($ticketId) < 5) {
            return false;
        }

        $pdo = Database::getPDO();
        $stmt = $pdo->prepare("Select id from users where ticket_id = :ticket");
        $stmt->bindParam(":ticket", $ticketId);
        $stmt->execute();
        if ($stmt->rowCount() == 1) {
            $userId = $stmt->fetch()["id"];
            $_SESSION["id"] = $userId;
            GSCommunicator::checkConnection();
            return true;
        } else {
            return false;
        }
    }

    /**
     * Register a user with the data provided in the registerVO
     * @author Altro50 <altro50@msn.com>
     * @param registerVO $registerVO Registration Data
     * @return boolean
     */
    public static function registerUserWithVo($registerVO)
    {
        if(isset($registerVO->_explicitType)) {
            if ($registerVO->_explicitType == "com.pandaland.mvc.model.vo.RegisterVO") {
                $name = (string)$registerVO->name;
                $password = (string)password_hash($registerVO->pw, PASSWORD_BCRYPT);
                $email = (string)$registerVO->emailParents;
                $sex = (int)($registerVO->sex == "girl" || $registerVO->sex == "FEMALE");

                if(Panfu::usernameAcceptable($name) && Panfu::usernameNotTaken($name)) {
                    $pdo = Database::getPDO();
                    $insert = $pdo->prepare("INSERT INTO users (name, password, email, sex) VALUES (:name, :password, :email,:sex)");
                    $insert->bindParam(":name", $name);
                    $insert->bindParam(":password", $password);
                    $insert->bindParam(":email", $email);
                    $insert->bindParam(":sex", $sex);
                    $result = $insert->execute();
                    return true;
                }
                return false;
            }
            return false;
        }
        return false;
    }

    /**
     * Checks if the username has not yet been taken.
     * @author Altro50 <altro50@msn.com>
     * @param String $username Username to check
     * @return boolean
     */
    public static function usernameNotTaken($username)
    {
        $pdo = Database::getPDO();
        $checkStmt = $pdo->prepare("SELECT * FROM users WHERE name = :name");
        $checkStmt->bindParam(":name", $username, PDO::PARAM_INT);
        $checkStmt->execute();
        if ($checkStmt->rowCount() == 0) {
            return true;
        }
        return false;
    }

    /**
     * Checks if the username is acceptable (no invalid characters, bad words)
     * @author Altro50 <altro50@msn.com>
     * @param String $username Username to check
     * @return boolean
     */
    public static function usernameAcceptable($username)
    {
        if (preg_match('/^[A-Za-z0-9_]{3,25}$/', $username)) {
            // Let's get rid of some characters
            $username = str_replace("_", "", $username);
            $username = str_replace("-", "", $username);
            $username = Panfu::undoLeet($username);

            // Load the wordfilter first
            if (sizeof(Panfu::$wordFilter) === 0) {
                Panfu::$wordFilter = explode("\n", str_replace("\r", "", file_get_contents(__DIR__ . "/wordfilter.txt")));
            }

            foreach(Panfu::$wordFilter as $forbiddenWord) {
                if(substr( $forbiddenWord, 0, 1 ) == "#") {
                    continue;
                }
                if(strpos($username, $forbiddenWord) !== false) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Checks if the user is currently logged in and if the session is still valid.
     * @author Altro50 <altro50@msn.com>
     * @return boolean
     */
    public static function isLoggedIn()
    {
        if (isset($_SESSION["id"])) {
            $pdo = Database::getPDO();
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = :id");
            $stmt->bindParam(':id', $_SESSION["id"]);
            $stmt->execute();
            $row = $stmt->fetch();
            if ($row) {
                return true;
            } else {
                // User suddenly removed from the DB.
                session_destroy();
                session_start();
                return false;
            }
        }
        return false;
    }

    /**
     * Adds two players to eachother's friendslist
     * @author Altro50 <altro50@msn.com>
     * @param int $buddy1
     * @param int $buddy2
     * @return void
     */
    public static function addBuddies($buddy1, $buddy2)
    {
        Panfu::setRelationBetweenPlayers($buddy1, $buddy2, 1);
        Panfu::setRelationBetweenPlayers($buddy2, $buddy1, 1);
    }

    /**
     * Removes players from eachother's friendslist
     * @author Altro50 <altro50@msn.com>
     * @param int $buddy1
     * @param int $buddy2
     * @return void
     */
    public static function removeBuddies($buddy1, $buddy2)
    {
        Panfu::setRelationBetweenPlayers($buddy1, $buddy2, 0);
        Panfu::setRelationBetweenPlayers($buddy2, $buddy1, 0);
    }

    /**
     * Ignore a user with userId
     * @author Altro50 <altro50@msn.com>
     * @param int $playerId
     * @return void
     */
    public static function ignorePlayer($playerId)
    {
        Panfu::setRelationBetweenPlayers($_SESSION['id'], $playerId, 2);
    }

    /**
     * Changes or inserts a relation between two users.
     * @author Altro50 <altro50@msn.com>
     * @param int $player1
     * @param int $player2
     * @param int $relation
     * @return void
     */
    public static function setRelationBetweenPlayers($player1, $player2, $relation)
    {
        $pdo = Database::getPDO();
        
        if(!Panfu::hasRelation($player1, $player2)) {
            $insert = $pdo->prepare("INSERT INTO relations (player1, player2, relation_type) VALUES (:player1, :player2, :relation_type)");
            $insert->bindParam(":player1", $player1);
            $insert->bindParam(":player2", $player2);
            $insert->bindParam(":relation_type", $relation);
            $insert->execute();
        } else {
            $update = $pdo->prepare("UPDATE relations SET relation_type = :relation_type WHERE player1 = :player1 AND player2 = :player2");
            $update->bindParam(":relation_type", $relation);
            $update->bindParam(":player1", $player1);
            $update->bindParam(":player2", $player2);
            $update->execute();
        }

        GSCommunicator::communicate("updateBuddyStatus", $player1, $player2, $relation);
    }

    /**
     * Checks if a relation between two users exists
     * @author Altro50 <altro50@msn.com>
     * @param int $player1
     * @param int $player2
     * @return boolean wether a relation exists
     */
    public static function hasRelation($player1, $player2)
    {
        $pdo = Database::getPDO();
        $statement = $pdo->prepare("SELECT id FROM relations WHERE player1 = :player1 AND player2 = :player2");
        $statement->bindParam(":player1", $player1, PDO::PARAM_INT);
        $statement->bindParam(":player2", $player2, PDO::PARAM_INT);
        $statement->execute();
        return ($statement->rowCount() > 0);
    }

    /**
     * Gets users on the user's relation list with a specific relation type.
     * @author Altro50 <altro50@msn.com>
     * @param int $player1
     * @param int $relation
     * @return int[] Players on the user's relation list with the specified relation type.
     */
    public static function getPlayersWithRelation($player1, $relation)
    {
        $pdo = Database::getPDO();
        $statement = $pdo->prepare("SELECT * FROM relations WHERE player1 = :player1 AND relation_type = :relation_type");
        $statement->bindParam(":player1", $player1, PDO::PARAM_INT);
        $statement->bindParam(":relation_type", $relation);
        $statement->execute();
        $relations = $statement->fetchAll();
        $players = [];
        $i = 0;
        foreach($relations as $relation) {
            // player2 will always be someone you have a relation with.
            $players[$i] = $relation['player2'];
            $i++;
        }
        return $players;
    }

    /**
     * Gets a list of all buddies on the user's relation list.
     * @author Altro50 <altro50@msn.com>
     * @param int $userId
     * @return SmallPlayerInfoVO[] Buddies
     */
    public static function getBuddiesForUserId($userId)
    {
        require_once AMFPHP_ROOTPATH . "/Services/Vo/SmallPlayerInfoVO.php";
        $buddies = Panfu::getPlayersWithRelation($userId, 1);
        $buddyArray = [];
        $i = 0;
        foreach($buddies as $buddyId) {
            $data = Panfu::getUserDataById($buddyId);
            $buddyArray[$i] = new SmallPlayerInfoVO();
            $buddyArray[$i]->playerId = $buddyId;
            $buddyArray[$i]->playerName = $data["name"];
            if($data["current_gameserver"] != null && $data["current_gameserver"] != 0)
                $buddyArray[$i]->currentGameServer = $data["current_gameserver"];

            $i++;
        }
        return $buddyArray;
    }

    /**
     * Gets a list of all buddies on the user's relation list as BuddyVo objects.
     * @author Altro50 <altro50@msn.com>
     * @param int $userId
     * @return SmallPlayerInfoVO[] Buddies
     */
    public static function getBuddiesVoForUserId($userId)
    {
        require_once AMFPHP_ROOTPATH . "/Services/Vo/BuddyVO.php";
        $buddies = Panfu::getPlayersWithRelation($userId, 1);
        $buddyArray = [];
        $i = 0;
        foreach($buddies as $buddyId) {
            $data = Panfu::getUserDataById($buddyId);
            $buddyArray[$i] = new BuddyVO();
            $buddyArray[$i]->id = $buddyId;
            $buddyArray[$i]->name = $data["name"];
            $buddyArray[$i]->premium = $data["goldpanda"];
            $buddyArray[$i]->bestfriend = false;
            if($data["current_gameserver"] != null && $data["current_gameserver"] != 0)
                $buddyArray[$i]->currentGameServer = $data["current_gameserver"];
            $buddyArray[$i]->socialLevel = $data["social_level"];
            $i++;
        }
        return $buddyArray;
    }

    /**
     * Returns the users table row for a id.
     * @param int $id The user id to look for.
     * @author Altro50 <altro50@msn.com>
     * @return array the row from the database.
     */
    public static function getUserDataById($id)
    {
        $pdo = Database::getPDO();
        $userStatement = $pdo->prepare("SELECT * FROM users WHERE id = :id");
        $userStatement->bindParam(":id", $id, PDO::PARAM_INT);
        $userStatement->execute();
        $userData = $userStatement->fetch();
        return $userData;
    }

    /**
     * Returns the users table row for a username.
     * @param String $username The username to look for.
     * @author Altro50 <altro50@msn.com>
     * @return array the row from the database.
     */
    public static function getUserDataByUsername($username)
    {
        if(!Panfu::usernameNotTaken($username)) {
            $pdo = Database::getPDO();
            $userStatement = $pdo->prepare("SELECT * FROM users WHERE name = :name");
            $userStatement->bindParam(":name", $username, PDO::PARAM_INT);
            $userStatement->execute();
            $userData = $userStatement->fetch();
            return $userData;
        } else {
            return null;
        }
    }

    /**
     * Returns an array filled with StateVOs
     * @author Altro50 <altro50@msn.com>
     * @param int[] $stateIds Ids of the states to get a stateVO of.
     * @return StateVO[]
     */
    public static function getStates($stateIds)
    {
        require_once AMFPHP_ROOTPATH . "/Services/Vo/StateVO.php";
        $states = array();
        $pdo = Database::getPDO();
        $statement = $pdo->prepare("SELECT * FROM states WHERE user_id = :id");
        $statement->bindParam(":id", $_SESSION['id']);
        $statement->execute();
        $i = 0;
        if($statement->rowCount() > 0) {
            foreach($statement as $state) {
                if(in_array($state['category'], $stateIds)) {
                    $states[$i] = new StateVO();
                    $states[$i]->playerId = $_SESSION['id'];
                    $states[$i]->cathegoryId = $state['category'];
                    $states[$i]->nameId = $state['name'];
                    $states[$i]->stateValue = $state['value'];
                    $states[$i]->lastChanged = $state['last_changed'] * 100000000;
                    $i++;
                }
            }
        }
        return $states;
    }

    /**
     * Sets a state on DB for the user
     * @author Altro50 <altro50@msn.com>
     * @param int $category
     * @param int $name
     * @param int $value
     * @return StateVO
     */
    public static function setState($category, $name, $value)
    {
        require_once AMFPHP_ROOTPATH . "/Services/Vo/StateVO.php";
        $pdo = Database::getPDO();
        $timestamp = round(microtime(true));
        if(Panfu::stateExists($category, $name)) {
            $update = $pdo->prepare("UPDATE states SET value = :value, last_changed = :lastChanged WHERE user_id = :playerId AND category = :category AND name = :name");
            $update->bindParam(":value", $value);
            $update->bindParam(":lastChanged", $timestamp);
            $update->bindParam(":playerId", $_SESSION["id"]);
            $update->bindParam(":category", $category);
            $update->bindParam(":name", $name);
            $update->execute();
        } else {
            $insert = $pdo->prepare("INSERT INTO states (value,last_changed,user_id,category,name) VALUES (:value, :lastChanged, :playerId, :category, :name)");
            $insert->bindParam(":value", $value);
            $insert->bindParam(":lastChanged", $timestamp);
            $insert->bindParam(":playerId", $_SESSION["id"]);
            $insert->bindParam(":category", $category);
            $insert->bindParam(":name", $name);
            $insert->execute();
        }
        $state = new StateVO();
        $state->playerId = $_SESSION['id'];
        $state->nameId = $name;
        $state->stateValue = $value;
        $state->cathegoryId = $category;
        $state->lastChanged = $timestamp * 100000000;
        return $state;
    }

    /**
     * Checks if a state exists
     * @author Altro50 <altro50@msn.com>
     * @param int $category
     * @param int $name
     * @return Boolean
     */
    public static function stateExists($category, $name)
    {
        $pdo = Database::getPDO();
        $statement = $pdo->prepare("SELECT * FROM states WHERE user_id = :id AND category = :category AND name = :name");
        $statement->bindParam(":id", $_SESSION['id'], PDO::PARAM_INT);
        $statement->bindParam(":category", $category, PDO::PARAM_INT);
        $statement->bindParam(":name", $name, PDO::PARAM_INT);
        $statement->execute();
        return ($statement->rowCount() > 0);
    }

    /**
     * Checks if the current user can afford something.
     * @author Altro50 <altro50@msn.com>
     * @param int $price
     * @return boolean
     */
    public static function canAfford($price)
    {
        $currentUser = Panfu::getUserDataById($_SESSION['id']);
        if($currentUser['coins'] >= $price) {
            return true;
        }
        return false;
    }    
    
    /**
    * Updates the user's coin count.
    * @author Altro50 <altro50@msn.com>
    * @param int $coins
    * @return void
    */
   public static function updateCoins($coins)
   {
        $pdo = Database::getPDO();
        $update = $pdo->prepare("UPDATE users SET coins = :coins WHERE id = :userId");
        $update->bindParam(":coins", $coins);
        $update->bindParam(":userId", $_SESSION['id']);
        $update->execute();
   }

    /**
     * Deducts an certain amount coins from the currently logged in user.
     * @author Altro50 <altro50@msn.com>
     * @param int $coins
     * @return void
     */
    public static function deductCoins($coins)
    {
        if(Panfu::canAfford($coins)) {
            $pdo = Database::getPDO();
            $update = $pdo->prepare("UPDATE users SET coins = coins - :toDeduct WHERE id = :userId");
            $update->bindParam(":toDeduct", $coins);
            $update->bindParam(":userId", $_SESSION['id']);
            $update->execute();
        }
    }

    /**
     * Game ids displayed by the player-card "My highscore" tab.
     *
     * @return int[]
     */
    public static function getSocialHighscoreGameIds()
    {
        return [4, 5, 6, 7, 10, 11, 12, 13, 15, 16, 17, 18, 19, 20, 21, 23, 24, 25, 26, 27, 28, 29, 31, 32, 33, 34, 35, 36, 37, 38, 40, 41, 42, 44, 45, 46, 47, 48, 49, 50, 51, 52, 55, 56];
    }

    /**
     * Stores a player's best score for a minigame.
     *
     * @param int $userId
     * @param int $gameId
     * @param int $score
     * @return void
     */
    public static function recordGameHighScore($userId, $gameId, $score)
    {
        $userId = (int) $userId;
        $gameId = (int) $gameId;
        $score = max(0, (int) $score);

        if($userId <= 0 || $gameId <= 0) {
            return;
        }

        try {
            $pdo = Database::getPDO();
            $statement = $pdo->prepare("
                INSERT INTO game_high_scores (user_id, game_id, score, created_at, updated_at)
                VALUES (:user_id, :game_id, :score, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    updated_at = IF(VALUES(score) > score, VALUES(updated_at), updated_at),
                    score = GREATEST(score, VALUES(score))
            ");
            $statement->bindParam(":user_id", $userId, PDO::PARAM_INT);
            $statement->bindParam(":game_id", $gameId, PDO::PARAM_INT);
            $statement->bindParam(":score", $score, PDO::PARAM_INT);
            $statement->execute();
        } catch(Exception $exception) {
            Console::log($exception->getMessage());
        }
    }

    /**
     * Returns a map of game id to best score for one player.
     *
     * @param int $userId
     * @return array<int, int>
     */
    public static function getGameHighScoresForUser($userId)
    {
        $scores = [];
        $userId = (int) $userId;

        if($userId <= 0) {
            return $scores;
        }

        try {
            $pdo = Database::getPDO();
            $statement = $pdo->prepare("SELECT game_id, score FROM game_high_scores WHERE user_id = :user_id");
            $statement->bindParam(":user_id", $userId, PDO::PARAM_INT);
            $statement->execute();

            foreach($statement as $row) {
                $scores[(int) $row['game_id']] = (int) $row['score'];
            }
        } catch(Exception $exception) {
            Console::log($exception->getMessage());
        }

        return $scores;
    }

    /**
     * Returns ranked highscore rows for a game.
     *
     * @param int $gameId
     * @param string|null $since
     * @param int $limit
     * @return array<int, array<string, mixed>>
     */
    public static function getGameHighScoreRows($gameId, $since = null, $limit = 5)
    {
        $gameId = (int) $gameId;
        $limit = max(1, min(20, (int) $limit));

        if($gameId <= 0) {
            return [];
        }

        try {
            $pdo = Database::getPDO();
            $where = "WHERE highscores.game_id = :game_id";
            $params = [
                ":game_id" => $gameId,
            ];

            if($since !== null) {
                $where .= " AND highscores.updated_at >= :updated_since";
                $params[":updated_since"] = date('Y-m-d H:i:s', strtotime($since));
            }

            $statement = $pdo->prepare("
                SELECT highscores.user_id, highscores.score, users.name
                FROM game_high_scores AS highscores
                INNER JOIN users ON users.id = highscores.user_id
                {$where}
                ORDER BY highscores.score DESC, highscores.updated_at ASC, highscores.user_id ASC
                LIMIT :limit
            ");

            foreach($params as $key => $value) {
                $statement->bindValue($key, $value, $key === ":game_id" ? PDO::PARAM_INT : PDO::PARAM_STR);
            }

            $statement->bindValue(":limit", $limit, PDO::PARAM_INT);
            $statement->execute();

            return $statement->fetchAll();
        } catch(Exception $exception) {
            Console::log($exception->getMessage());
        }

        return [];
    }

    /**
     * Adds item to a users inventory.
     * @author Altro50 <altro50@msn.com>
     * @param int $itemId
     * @param boolean $active
     * @return void
     */
    public static function addItemToUser($itemId, $active = false)
    {
        $pdo = Database::getPDO();
        $insert = $pdo->prepare("INSERT INTO inventories (user_id, item_id, active, bought) VALUE (:userId, :itemId, :active, true)");
        $insert->bindParam(":userId", $_SESSION['id'], PDO::PARAM_INT);
        $insert->bindParam(":itemId", $itemId, PDO::PARAM_INT);
        $insert->bindParam(":active", $active, PDO::PARAM_INT);
        $result = $insert->execute();
        if(!$result) {
            Console::log($pdo->errorInfo());
        }
    }

    /**
     * Gets the item row from the database
     * @author Altro50 <altro50@msn.com>
     * @param int $itemId
     * @return array the row from the database
     */
    public static function getItem($itemId)
    {
        $pdo = Database::getPDO();
        $itemStatement = $pdo->prepare("SELECT * FROM items WHERE id = :id");
        $itemStatement->bindParam(":id", $itemId, PDO::PARAM_INT);
        $itemStatement->execute();
        $itemData = $itemStatement->fetch();
        if(!$itemData) {
            return null;
        }
        if($itemData["type"] < 10) {
            $itemData["type"] = "0" . (string)$itemData["type"];
        }
        return $itemData;
    }

    /**
     * Gets the item from the database as a itemVo
     * @author Altro50 <altro50@msn.com>
     * @param int $itemId
     * @return ItemVO
     */
    public static function getItemVo($itemId)
    {
        require_once AMFPHP_ROOTPATH . "/Services/Vo/ItemVO.php";
        $response = new ItemVO();
        $item = Panfu::getItem($itemId);
        $response->id = $item['id'];
        $response->name = $item['name'];
        $response->type = $item['type'];
        $response->price = $item['price'];
        $response->zettSort = $item['z'];
        $response->premium = $item['premium'];
        $response->bought = true;
        return $response;
    }

    /**
     * Checks if a item id exists
     * @author Altro50 <altro50@msn.com>
     * @param Int $itemId
     * @return boolean
     */
    public static function itemExists($itemId)
    {
        $pdo = Database::getPDO();
        $itemStatement = $pdo->prepare("SELECT * FROM items WHERE id = :id");
        $itemStatement->bindParam(":id", $itemId, PDO::PARAM_INT);
        $itemStatement->execute();
        if ($itemStatement->rowCount() == 0) {
            return false;
        }
        return true;
    }

    /**
     * Removes an item from the user's inventory.
     * @author Altro50 <altro50@msn.com>
     * @param Int $itemId
     * @return void
     */
    public static function removeFromInventory($itemId)
    {
        if(Panfu::hasItem($itemId)) {
            $pdo = Database::getPDO();
            $removeStatement = $pdo->prepare("DELETE FROM inventories WHERE user_id = :userId AND item_id = :itemId");
            $removeStatement->bindParam(":userId", $_SESSION['id'], PDO::PARAM_INT);
            $removeStatement->bindParam(":itemId", $itemId, PDO::PARAM_INT);
            $removeStatement->execute();
        }
    }

    /**
     * Checks if the current user has a certain item.
     * @author Altro50 <altro50@msn.com>
     * @param Int $itemId
     * @return boolean
     */
    public static function hasItem($itemId)
    {
        $pdo = Database::getPDO();
        $itemStatement = $pdo->prepare("SELECT id FROM inventories WHERE user_id = :userId AND item_id = :itemId");
        $itemStatement->bindParam(":userId", $_SESSION['id'], PDO::PARAM_INT);
        $itemStatement->bindParam(":itemId", $itemId, PDO::PARAM_INT);
        $result = $itemStatement->execute();
        if(!$result) {
            Console::log($pdo->errorInfo());
        } else {

        }
        if ($itemStatement->rowCount() == 0) {
            return false;
        }
        return true;
    }

    /**
     * Gets the inventory for a user.
     * @author Altro50 <altro50@msn.com>
     * @param Int $userId
     * @param Boolean $active
     * @return ItemVO[]
     */
    public static function getInventory($userId, $active = false)
    {
        $pdo = Database::getPDO();
        $items = array();
        $i = 0;
        $statement = $pdo->prepare("SELECT * FROM inventories WHERE user_id = :id AND active = :active");
        $statement->bindParam(":id", $userId, PDO::PARAM_INT);
        $statement->bindParam(":active", $active, PDO::PARAM_INT);

        $statement->execute();
        if($statement->rowCount() > 0) {
            foreach ($statement as $inventoryEntry) {
                $items[$i] = Panfu::getItemVo($inventoryEntry['item_id']);
                $items[$i]->active = $inventoryEntry['active'];
                $i++;
            }
        }
        return $items;
    }

    /**
     * Checks if the item type is a piece of furniture.
     * @author Altro50 <altro50@msn.com>
     * @param Int $itemType the type to check.
     * @return Boolean True if furniture.
     */
    public static function isFurniture($itemType)
    {
        return ($itemType == "13" || $itemType == "17" || $itemType == "14" || $itemType == "00" || $itemType == "50");
    }

    /**
     * Often when coming up with usernames, users might try evading the word censor
     * by using something known as "1337 speak", this converts leet to normal text.
     * @author Altro50 <altro50@msn.com>
     * @param String $text The text to replace leet speak in.
     * @return String $text without leet speak
     */
    public static function undoLeet($text)
    {
        $text = str_split($text);
        $leet_replace = array();
        $leet_replace[0] = "o";
        $leet_replace[1] = "l";
        $leet_replace[2] = "z";
        $leet_replace[3] = "e";
        $leet_replace[4] = "a";
        $leet_replace[5] = "s";
        $leet_replace[6] = "b";
        $leet_replace[7] = "t";
        $leet_replace[8] = "b";
        $leet_replace[9] = "p";
        $changedText = "";
        foreach($text as $letter) {
            if(is_numeric($letter))
                $changedText .= str_ireplace(array_keys($leet_replace), array_values($leet_replace), $letter);
            else
                $changedText .= $letter;
        }
        return $changedText;
    }
}
