<?php

declare(strict_types = 1);

namespace core\game\quest;

use core\game\item\types\custom\Token;
use core\game\item\types\Rarity;
use core\game\item\types\vanilla\Fireworks;
use core\level\entity\types\FireworksRocket;
use core\player\NexusPlayer;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\Location;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;

abstract class Quest {

    const DAMAGE = 0;
    const MINE = 1;
    const KILL = 2;
    const PLACE = 3;
    const PRESTIGE_PICKAXE = 4;
    const DAMAGE_BY_METEOR = 5;
    const MINE_METEORITE = 6;
    const FILL_SATCHEL = 7;
    const COINFLIP_WIN = 8;
    const COINFLIP_LOSE = 9;
    const BUY_LOTTERY = 10;
    const KOTH_CAPTURE = 11;
    const MOMENTUM = 12;
    const MINE_FLARE = 13;
    const APPLY_ENERGY = 14;
    const SATCHEL_LEVEL_UP = 15;
    const USE_ENCHANT_ORB = 16;
    const FIND_SHARDS = 17;
    const TINKER_EQUIPMENT = 18;
    const EARN_ENERGY = 19;

    const RARITY_TO_TOKENS = [
        Rarity::SIMPLE => 1,
        Rarity::UNCOMMON => 2,
        Rarity::ELITE => 4,
        Rarity::ULTIMATE => 6,
        Rarity::LEGENDARY => 8,
        Rarity::GODLY => 12
    ];

    /** @var string */
    protected $name;

    /** @var string */
    protected $description;

    /** @var string */
    protected $rarity;

    /** @var int */
    protected $eventType;

    /** @var callable */
    protected $callable;

    /** @var int */
    protected $targetValue;

    /**
     * Quest constructor.
     *
     * @param string $name
     * @param string $description
     * @param string $rarity
     * @param int $eventType
     * @param int $targetValue
     * @param callable $callable
     */
    public function __construct(string $name, string $description, string $rarity, int $eventType, int $targetValue, callable $callable) {
        $this->name = $name;
        $this->description = $description;
        $this->rarity = $rarity;
        $this->eventType = $eventType;
        $this->targetValue = $targetValue;
        $this->callable = $callable;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription(): string {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getRarity(): string {
        return $this->rarity;
    }

    /**
     * @return int
     */
    public function getEventType(): int {
        return $this->eventType;
    }

    /**
     * @return int
     */
    public function getTargetValue(): int {
        return $this->targetValue;
    }

    /**
     * @return callable
     */
    public function getCallable(): callable {
        return $this->callable;
    }

    /**
     * @param NexusPlayer $player
     */
    public function celebrate(NexusPlayer $player): void {
        $pos = $player->getPosition();
        /** @var Fireworks $fw */
        $fw = ItemFactory::getInstance()->get(ItemIds::FIREWORKS);
        $fw->addExplosion(Fireworks::TYPE_STAR, Fireworks::COLOR_WHITE, Fireworks::COLOR_GREEN, true, true);
        $fw->setFlightDuration(1);
        $yaw = lcg_value() * 360;
        $pitch = 90;
        $entity = new FireworksRocket(new Location($pos->x, $pos->y, $pos->z, $pos->getWorld(), $yaw, $pitch), $fw);
        if($entity instanceof FireworksRocket) {
            $entity->spawnToAll();
        }
        $player->sendTitleTo(TextFormat::BOLD . Rarity::RARITY_TO_COLOR_MAP[$this->getRarity()] . $this->getName(), TextFormat::WHITE . "You've completed this quest!");
        $item = (new Token())->toItem()->setCount(self::RARITY_TO_TOKENS[$this->rarity]);
        if($player->getInventory()->canAddItem($item)) {
            $player->getInventory()->addItem($item);
        }
    }
}