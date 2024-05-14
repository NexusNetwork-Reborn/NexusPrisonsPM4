<?php

declare(strict_types=1);

namespace core\game\item\types\custom;

use core\game\item\sets\utils\SetUtils;
use core\game\item\types\Interactive;
use core\game\item\types\Rarity;
use core\game\item\types\vanilla\Fireworks;
use core\level\entity\types\FireworksRocket;
use core\game\kit\GodKit;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\Location;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class ClueScrollCasket extends Interactive {

    const CASKET_RARITY = "CasketRarity";

    /** @var string */
    private $rarity;

    /**
     * ClueScrollCasket constructor.
     *
     * @param string $rarity
     * @param string|null $uuid
     */
    public function __construct(string $rarity, ?string $uuid = null) {
        $color = Rarity::RARITY_TO_COLOR_MAP[$rarity];
        $customName = TextFormat::RESET . $color . TextFormat::BOLD . $rarity . " Clue Casket";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . $color . TextFormat::BOLD . "Contents:";
        $lore[] = TextFormat::RESET . $color . TextFormat::BOLD .  " * " . TextFormat::RESET . TextFormat::WHITE . "$rarity XP Bottle";
        $lore[] = TextFormat::RESET . $color . TextFormat::BOLD .  " * " . TextFormat::RESET . TextFormat::WHITE . "Money";
        $lore[] = TextFormat::RESET . $color . TextFormat::BOLD .  " * " . TextFormat::RESET . TextFormat::WHITE . "and other $rarity items!";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Obtained from completing a Clue Scroll";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "(Right-click to open)";
        $this->rarity = $rarity;
        parent::__construct(VanillaBlocks::CHEST()->asItem(), $customName, $lore, false, true, $uuid);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::CASKET_RARITY => StringTag::class
        ];
    }

    /**
     * @param Item $item
     *
     * @return $this
     */
    public static function fromItem(Item $item): self {
        $tag = self::getCustomTag($item);
        $rarity = $tag->getString(self::CASKET_RARITY);
        $uuid = $tag->getString(self::UUID);
        return new self($rarity, $uuid);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setString(self::CASKET_RARITY, $this->rarity);
        $tag->setString(self::UUID, $this->getUniqueId());
        return $tag;
    }

    /**
     * @return string
     */
    public function getRarity(): string {
        return $this->rarity;
    }

    /**
     * @param NexusPlayer $player
     * @param Inventory $inventory
     * @param Item $item
     */
    public function execute(NexusPlayer $player, Inventory $inventory, Item $item): void {
        $color = Rarity::RARITY_TO_COLOR_MAP[$this->rarity];
        if(count($player->getInventory()->getContents()) === $player->getInventory()->getSize()) {
            $player->sendMessage(Translation::getMessage("fullInventory"));
            return;
        }
        $this->setUsed();
        $loot = [];
        $multiplier = [
            Rarity::SIMPLE => 1,
            Rarity::UNCOMMON => 5,
            Rarity::ELITE => 25,
            Rarity::ULTIMATE => 45,
            Rarity::LEGENDARY => 60,
            Rarity::GODLY => 85,
        ];
        $isObsidian = SetUtils::isWearingFullSet($player, "obsidian");
        $multiplier = $multiplier[$this->rarity];
        $xpBaseMin = 100000 * $multiplier;
        $xpBaseMax = 300000 * $multiplier;
        $moneyBaseMin = 10000 * $multiplier;
        $moneyBaseMax = 20000 * $multiplier;

        if($isObsidian) {
            $loot[] = (new XPBottle((int)(mt_rand($xpBaseMin, $xpBaseMax) * 1.15), $this->rarity))->toItem()->setCount(1);
        } else {
            $loot[] = (new XPBottle(mt_rand($xpBaseMin, $xpBaseMax), $this->rarity))->toItem()->setCount(1);
        }

        $loot[] = (new MoneyNote(mt_rand($moneyBaseMin, $moneyBaseMax)))->toItem()->setCount(1);
        $loot[] = (new Shard($this->rarity))->toItem()->setCount(mt_rand(15, 45));
        if(mt_rand(1, 3) === mt_rand(1, 3)) {
            $loot[] = (new Contraband($this->rarity))->toItem()->setCount(1);
        }
        $player->sendMessage(TextFormat::BOLD . $color . "(!) $this->rarity Clue Casket Loot:");
        /** @var Item $item */
        foreach($loot as $item) {
            $player->sendMessage(TextFormat::BOLD . TextFormat::WHITE . " * " . $item->getCustomName() . TextFormat::RESET . TextFormat::BOLD . TextFormat::GRAY . " * " . TextFormat::RESET . TextFormat::WHITE . $item->getCount());
            if($player->getInventory()->canAddItem($item)) {
                $player->getInventory()->addItem($item);
            }
        }
        $player->playBlastSound();
        $player->playTwinkleSound();
        $inventory->setItemInHand($item->setCount($item->getCount() - 1));
        $pos = $player->getPosition();
        /** @var Fireworks $fw */
        $fw = ItemFactory::getInstance()->get(ItemIds::FIREWORKS);
        $fw->addExplosion(Fireworks::TYPE_STAR, Fireworks::COLOR_WHITE, Rarity::getFireworkColor($this->rarity), true, true);
        $fw->setFlightDuration(1);
        $yaw = lcg_value() * 360;
        $pitch = 90;
        $entity = new FireworksRocket(new Location($pos->x, $pos->y, $pos->z, $pos->getWorld(), $yaw, $pitch), $fw);
        if($entity instanceof FireworksRocket) {
            $entity->spawnToAll();
        }
    }
}