<?php

declare(strict_types = 1);

namespace core\game\item\cluescroll;

use core\game\item\types\custom\ClueScroll;
use core\game\item\types\custom\ClueScrollCasket;
use core\game\item\types\custom\Token;
use core\game\item\types\Rarity;
use core\game\item\types\vanilla\Fireworks;
use core\level\entity\types\FireworksRocket;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\Location;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;

abstract class Challenge {

    const DAMAGE = 0;
    const MINE = 1;
    const KILL = 2;
    const PLACE = 3;
    const BUY = 4;
    const SELL = 5;
    const USE_ITEM = 6;
    const BUY_LOTTERY = 7;
    const COINFLIP_WIN = 8;
    const COINFLIP_LOSE = 9;
    const KILL_MERCHANT = 10;
    const OPEN_ITEM = 11;
    const MOMENTUM = 12;
    const APPLY_ITEM = 13;
    const LEVEL_UP_PICKAXE = 14;
    const TRADE = 15;
    const FAIL_ENCHANTMENT = 16;
    const FIND_CONTRABAND = 17;
    const TINKER_EQUIPMENT = 18;

    /** @var int */
    protected $id;

    /** @var string */
    protected $description;

    /** @var string */
    protected $rarity;

    /** @var int */
    protected $eventType;

    /** @var callable */
    protected $callable;

    /**
     * Challenge constructor.
     *
     * @param int $id
     * @param string $description
     * @param string $rarity
     * @param int $eventType
     * @param callable $callable
     */
    public function __construct(int $id, string $description, string $rarity, int $eventType, callable $callable) {
        $this->id = $id;
        $this->description = $description;
        $this->rarity = $rarity;
        $this->eventType = $eventType;
        $this->callable = $callable;
    }

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
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
        return 1;
    }

    /**
     * @return callable
     */
    public function getCallable(): callable {
        return $this->callable;
    }

    /**
     * @param NexusPlayer $player
     * @param Item $item
     * @param ClueScroll $scroll
     */
    public function celebrate(NexusPlayer $player, Item $item, ClueScroll $scroll): void {
        $chance = count($scroll->getChallenges());
        $player->getInventory()->removeItem($item);
        $rarity = $scroll->getRarity();
        $color = Rarity::RARITY_TO_COLOR_MAP[$rarity];
        if($chance < 7) {
            if($chance < mt_rand(1, 14)) {
                $scroll->completeChallenge();
                $player->getInventory()->addItem($scroll->toItem());
                $player->sendTitle($color . TextFormat::BOLD . "(!) CLUE SCROLL COMPLETE!", TextFormat::GRAY . "The next step can be found in your chat!");
                $scrollManager = Nexus::getInstance()->getGameManager()->getItemManager()->getScrollManager();
                $challenges = $scroll->getChallenges();
                $player->sendMessage(" ");
                $player->sendMessage(TextFormat::RESET . TextFormat::BOLD . $color . "(!) CLUE SCROLL COMPLETE! Next step:");
                $count = 0;
                foreach($challenges as $index => $challenge) {
                    ++$count;
                    $scrollChallenge = $scrollManager->getChallenge($challenge);
                    $format = TextFormat::RESET . TextFormat::WHITE;
                    if($index < (count($challenges) - 1)) {
                        $format .= TextFormat::ITALIC . TextFormat::GRAY;
                    }
                    $player->sendMessage(TextFormat::RESET . TextFormat::BOLD . $color . "$count. " . $format . $scrollChallenge->getDescription());
                }
                $player->sendMessage(" ");
                return;
            }
        }
        $player->getInventory()->addItem((new ClueScrollCasket($rarity))->toItem()->setCount(1));
        $player->sendMessage(" ");
        $player->sendMessage(TextFormat::BOLD . $color . "(!) CLUE SCROLL COMPLETE");
        $player->sendMessage(TextFormat::BOLD . $color . "Discovered $rarity Clue Casket!");
        $player->sendMessage(" ");
    }
}