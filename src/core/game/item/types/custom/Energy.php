<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\boop\task\DupeLogTask;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\event\SatchelLevelUpEvent;
use core\game\item\types\Interactive;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Pickaxe;
use core\level\LevelException;
use core\player\NexusPlayer;
use core\player\rpg\RPGManager;
use core\player\rpg\XPUtils;
use core\translation\Translation;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class Energy extends Interactive {

    const ENERGY = "Energy";

    const ENERGY_ORB = "EnergyOrb";

    /** @var int */
    private $energy;

    /**
     * Energy constructor.
     *
     * @param int $amount
     * @param string $withdrawer
     * @param string|null $uuid
     */
    public function __construct(int $amount, string $withdrawer = "Admin", ?string $uuid = null) {
        $customName = TextFormat::RESET . TextFormat::WHITE . TextFormat::BOLD . number_format($amount) . TextFormat::AQUA . " Energy";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GOLD . "Contains " . TextFormat::WHITE . TextFormat::BOLD . number_format($amount) . TextFormat::AQUA . " Energy";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "that is used for enchanting.";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hint: Drag this item on top of";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "something that holds energy to use it.";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GOLD . "Extracted by " . TextFormat::RESET . TextFormat::WHITE . $withdrawer;
        $this->energy = $amount;
        parent::__construct(VanillaItems::LIGHT_BLUE_DYE(), $customName, $lore, false, true, $uuid);
    }

    /**
     * @return string
     */
    public function getName(): string {
        return TextFormat::RESET . TextFormat::WHITE . TextFormat::BOLD . number_format($this->energy) . TextFormat::AQUA . " Energy";
    }

    /**
     * @param string $withdrawer
     *
     * @return string[]
     */
    public function getLore(string $withdrawer = "Admin"): array {
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GOLD . "Contains " . TextFormat::WHITE . TextFormat::BOLD . number_format($this->energy) . TextFormat::AQUA . " Energy";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "that is used for enchanting.";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hint: Drag this item on top of";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "something that holds energy to use it.";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GOLD . "Extracted by " . TextFormat::RESET . TextFormat::WHITE . $withdrawer;
        return $lore;
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::ENERGY => LongTag::class,
            self::ENERGY_ORB => StringTag::class
        ];
    }

    /**
     * @param Item $item
     *
     * @return $this
     */
    public static function fromItem(Item $item): self {
        $tag = self::getCustomTag($item);
        $amount = $tag->getLong(self::ENERGY);
        $uuid = $tag->getString(self::UUID);
        return new self($amount, "Admin", $uuid);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setString(self::ENERGY_ORB, self::ENERGY_ORB);
        $tag->setLong(self::ENERGY, $this->energy);
        $tag->setString(self::UUID, $this->getUniqueId());
        return $tag;
    }

    /**
     * @return int
     */
    public function getEnergy(): int {
        return $this->energy;
    }

    /**
     * @param int $energy
     */
    public function setEnergy(int $energy): void {
        $this->energy = $energy;
        $this->generateNewUniqueId();
    }

    /**
     * @param NexusPlayer $player
     * @param Item $itemClickedWith
     * @param Item $itemClicked
     * @param SlotChangeAction $itemClickedWithAction
     * @param SlotChangeAction $itemClickedAction
     *
     * @return bool
     *
     * @throws LevelException
     */
    public function onCombine(NexusPlayer $player, Item $itemClickedWith, Item $itemClicked, SlotChangeAction $itemClickedWithAction, SlotChangeAction $itemClickedAction): bool {
        $energy = $this->getEnergy();
        if($itemClicked instanceof Pickaxe) {
            $itemClickedAction->getInventory()->removeItem($itemClicked);
            $currentLevel = XPUtils::xpToLevel($itemClicked->getEnergy(), RPGManager::ENERGY_MODIFIER);
            $reachNext = XPUtils::levelToXP($currentLevel + 1, RPGManager::ENERGY_MODIFIER) - $itemClicked->getEnergy();
            if($energy > $reachNext) {
                $leftover = $energy - $reachNext;
            }
            if($reachNext > $energy) {
                $reachNext = $energy;
            }
            $itemClickedAction->getInventory()->addItem($itemClicked->addEnergy($reachNext));
            $itemClickedWithAction->getInventory()->removeItem($itemClickedWith);
            if(isset($leftover)) {
                $itemClickedWithAction->getInventory()->addItem((new Energy($leftover, $player->getName()))->toItem());
            }
            $player->playDingSound();
            $this->setUsed();
            return false;
        }
        if($itemClicked instanceof Armor) {
            if($energy >= $itemClicked->getEnergyPrice()) {
                $itemClickedAction->getInventory()->removeItem($itemClicked);
                $itemClickedAction->getInventory()->addItem($itemClicked->setEnergyPrice(0));
                $leftover = (new Energy($energy - $itemClicked->getEnergyPrice()));
                if($leftover->getEnergy() > 0) {
                    $itemClickedWithAction->getInventory()->removeItem($itemClickedWith);
                    $itemClickedWithAction->getInventory()->addItem($leftover->toItem());
                }
            } else {
                $player->playErrorSound();
            }
            return false;
        }
        if(EnchantmentBook::isInstanceOf($itemClicked)) {
            $book = EnchantmentBook::fromItem($itemClicked);
            $bookEnergy = $book->getEnergy();
            $enchantmentInstance = $book->getEnchantment();
            $needed = EnchantmentManager::getNeededEnergy($enchantmentInstance);
            if($needed <= $bookEnergy) {
                $player->playErrorSound();
                return true;
            }
            $energy = $bookEnergy + $energy;
            if($energy > $needed) {
                $leftover = $energy - $needed;
                $energy = $needed;
            }
            $itemClickedWithAction->getInventory()->removeItem($itemClickedWith);
            if(isset($leftover)) {
                $itemClickedWithAction->getInventory()->addItem((new Energy($leftover, $player->getName()))->toItem());
            }
            $itemClickedAction->getInventory()->removeItem($itemClicked);
            $itemClickedAction->getInventory()->addItem((new EnchantmentBook($enchantmentInstance, $book->getSuccess(), $book->getDestroy(), $energy, $book->getUniqueId()))->toItem());
            $player->playDingSound();
            $this->setUsed();
            return false;
        }
        if(Satchel::isInstanceOf($itemClicked)) {
            $satchel = Satchel::fromItem($itemClicked);
            $currentLevel = XPUtils::xpToLevel($satchel->getEnergy(), RPGManager::SATCHEL_MODIFIER);
            $reachNext = XPUtils::levelToXP($currentLevel + 1, RPGManager::SATCHEL_MODIFIER) - $satchel->getEnergy();
            if($energy > $reachNext) {
                $leftover = $energy - $reachNext;
            }
            if($reachNext > $energy) {
                $reachNext = $energy;
            }
            $ev = new SatchelLevelUpEvent($player, $satchel->addEnergy($reachNext));
            $ev->call();
            $itemClickedWithAction->getInventory()->removeItem($itemClickedWith);
            $itemClickedAction->getInventory()->removeItem($itemClicked);
            $itemClickedAction->getInventory()->addItem($satchel->toItem());
            if(isset($leftover)) {
                $itemClickedWithAction->getInventory()->addItem((new Energy($leftover, $player->getName()))->toItem());
            }
            $player->playDingSound();
            $this->setUsed();
            return false;
        }
        if(Trinket::isInstanceOf($itemClicked)) {
            $trinket = Trinket::fromItem($itemClicked);
            $trinket->addEnergy($energy);
            $itemClickedWithAction->getInventory()->removeItem($itemClickedWith);
            $itemClickedAction->getInventory()->removeItem($itemClicked);
            $itemClickedAction->getInventory()->addItem($trinket->toItem());
            $player->playDingSound();
            $this->setUsed();
            return false;
        }
        if(self::isInstanceOf($itemClicked)) {
            $clickedOrb = self::fromItem($itemClicked);
            $uuid = $clickedOrb->getUniqueId();
            if($uuid !== null and $uuid === $this->getUniqueId()) {
//                $player->kickDelay(Translation::getMessage("kickMessage", [
//                    "name" => TextFormat::RED . "BOOP",
//                    "reason" => TextFormat::YELLOW . "Possibility of being duped! Please trash this item!"
//                ]));
                Server::getInstance()->getAsyncPool()->submitTaskToWorker(new DupeLogTask("[Prison]" . date("[n/j/Y][G:i:s]", time()). " {$player->getName()} has a possibly duped item: " . TextFormat::clean($clickedOrb->getName())), 1);
                return false;
            }
            $energy += $clickedOrb->getEnergy();
            $itemClickedWithAction->getInventory()->removeItem($itemClickedWith);
            $itemClickedAction->getInventory()->removeItem($itemClicked);
            $itemClickedAction->getInventory()->addItem((new Energy(((int)$energy), $player->getName()))->toItem());
            $player->playDingSound();
            $this->setUsed();
            return false;
        }
        return true;
    }
}
