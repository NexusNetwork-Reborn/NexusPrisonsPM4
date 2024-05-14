<?php

declare(strict_types=1);

namespace core\game\item\types\custom;

use core\game\item\types\Interactive;
use core\game\item\types\vanilla\Fireworks;
use core\level\entity\types\FireworksRocket;
use core\game\kit\GodKit;
use core\Nexus;
use core\player\NexusPlayer;
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

class GKitBeacon extends Interactive {

    const GKIT_UNLOCKED = "GKitUnlocked";

    /** @var GodKit */
    private $kit;

    /**
     * GKitBeacon constructor.
     *
     * @param GodKit $kit
     * @param string|null $uuid
     */
    public function __construct(GodKit $kit, ?string $uuid = null) {
        $color = $kit->getColor();
        $name = $kit->getName();
        $customName = TextFormat::RESET . $color . TextFormat::BOLD . $name . TextFormat::RESET . $color . " G-Kit";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::WHITE . "Claiming this item will";
        $lore[] = TextFormat::RESET . TextFormat::WHITE . "give you permanent access";
        $lore[] = TextFormat::RESET . TextFormat::WHITE . "to the $name G-Kit";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "If you already own this G-Kit:";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "This item will level up the";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "$name G-Kit on your account,";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "improving the loot and cooldown time.";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "(Right-click to claim)";
        $this->kit = $kit;
        parent::__construct(VanillaBlocks::BEACON()->asItem(), $customName, $lore, false, true, $uuid);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::GKIT_UNLOCKED => StringTag::class
        ];
    }

    /**
     * @param Item $item
     *
     * @return $this
     */
    public static function fromItem(Item $item): self {
        $tag = self::getCustomTag($item);
        $kit = $tag->getString(self::GKIT_UNLOCKED);
        $kit = Nexus::getInstance()->getGameManager()->getKitManager()->getKitByName($kit);
        $uuid = $tag->getString(self::UUID);
        return new self($kit, $uuid);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setString(self::GKIT_UNLOCKED, $this->kit->getName());
        $tag->setString(self::UUID, $this->getUniqueId());
        return $tag;
    }

    /**
     * @return GodKit
     */
    public function getKit(): GodKit {
        return $this->kit;
    }

    /**
     * @param NexusPlayer $player
     * @param Inventory $inventory
     * @param Item $item
     * @param CompoundTag $tag
     * @param int $face
     * @param Block $blockClicked
     */
    public function execute(NexusPlayer $player, Inventory $inventory, Item $item): void {
        $kit = $this->getKit();
        $lowercaseName = strtolower($kit->getName());
        if(!$player->hasPermission("permission.$lowercaseName")) {
            $player->getDataSession()->addPermanentPermission("permission.$lowercaseName");
            return;
        }
        $oldLevel = $player->getDataSession()->getGodKitTier($kit);
        if($oldLevel >= 3) {
            $player->sendAlert(TextFormat::RED . "You already achieved the maxed out version of this G-Kit!");
            $player->playErrorSound();
            return;
        }
        $new = $oldLevel + 1;
        $color = $kit->getColor();
        $name = $kit->getName();
        $customName = TextFormat::RESET . $color . $name . " G-Kit";
        $player->getDataSession()->levelUpGodKitTier($kit);
        $level = $player->getWorld();
        if($level !== null) {
            /** @var Fireworks $fw */
            $fw = ItemFactory::getInstance()->get(ItemIds::FIREWORKS);
            $fw->addExplosion(Fireworks::TYPE_STAR, Fireworks::COLOR_WHITE, Fireworks::COLOR_YELLOW, true, true);
            $fw->setFlightDuration(1);
            $pos = $player->getPosition();
            $yaw = lcg_value() * 360;
            $pitch = 90;
            $entity = new FireworksRocket(new Location($pos->x, $pos->y, $pos->z, $pos->getWorld(), $yaw, $pitch), $fw);
            if($entity instanceof FireworksRocket) {
                $entity->spawnToAll();
            }
        }
        // $this->setUsed();
        $player->sendMessage(TextFormat::BOLD . TextFormat::GOLD . "(!) $customName " . TextFormat::BOLD . TextFormat::GOLD . "LEVELED UP! " . TextFormat::RESET . TextFormat::GRAY . "(Level $new/3)");
        $player->getServer()->broadcastMessage(TextFormat::GRAY . $player->getName() . " leveled up their $name G-Kit to Level $new!");
        $inventory->setItemInHand($item->setCount($item->getCount() - 1));
    }
}