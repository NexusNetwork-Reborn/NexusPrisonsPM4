<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\item\types\Interactive;
use core\game\item\types\vanilla\Fireworks;
use core\level\entity\types\FireworksRocket;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rank\Rank;
use core\translation\Translation;
use pocketmine\block\Block;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\Location;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;;
use pocketmine\utils\TextFormat;

class RankNote extends Interactive {

    const RANK = "Rank";

    /** @var Rank */
    private $rank;

    /**
     * RankNote constructor.
     *
     * @param Rank $rank
     * @param string|null $uuid
     */
    public function __construct(Rank $rank, ?string $uuid = null) {
        $customName = TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . "Rank: " . $rank->getColoredName();
        $id = ItemIds::PAPER;
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Right click this note to unlock the";
        $lore[] = TextFormat::RESET . $rank->getColoredName() . TextFormat::RESET . TextFormat::GRAY . " rank.";
        if($rank->getIdentifier() === Rank::EMPEROR_HEROIC) {
            $customName = TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . "Heroic Rank Crystal: " . $rank->getColoredName();
            $id = ItemIds::NETHERSTAR;
            $lore = [];
            $lore[] = "";
            $lore[] = TextFormat::RESET . TextFormat::GRAY . "Right click this crystal to unlock the";
            $lore[] = TextFormat::RESET . $rank->getColoredName() . TextFormat::RESET . TextFormat::GRAY . " rank.";
            $lore[] = "";
            $lore[] = TextFormat::RESET . TextFormat::GRAY . "This item requires the user to have";
            $lore[] = TextFormat::RESET . TextFormat::GRAY . "a rank of " . TextFormat::AQUA . TextFormat::BOLD . "Martian" . TextFormat::RESET . TextFormat::GRAY . " to apply!";
        }
        if($rank->getIdentifier() === Rank::PRESIDENT) {
            $customName = TextFormat::RESET . $rank->getColoredName() . TextFormat::BOLD . TextFormat::DARK_RED . " Rank Letter";
            $id = ItemIds::EMPTY_MAP;
            $lore = [];
            $lore[] = "";
            $lore[] = TextFormat::RESET . TextFormat::GRAY . "Right-click with this note to unlock";
            $lore[] = TextFormat::RESET . TextFormat::GRAY . "the " . TextFormat::BOLD . TextFormat::RED . "President" . TextFormat::RESET . TextFormat::GRAY . " Rank";
            $lore[] = "";
            $lore[] = TextFormat::RESET . TextFormat::GRAY . "This item requires the user to have";
            $lore[] = TextFormat::RESET . TextFormat::GRAY . "a rank of " . TextFormat::YELLOW . TextFormat::BOLD . "Martian" . TextFormat::GOLD . "+". TextFormat::RESET . TextFormat::GRAY . " to apply!";
            $lore[] = "";
            $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_RED . "President Perks";
            $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_RED . " * " . TextFormat::RESET . TextFormat::RED . "Custom Overhead Rank";
            $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_RED . " * " . TextFormat::RESET . TextFormat::RED . "Access to /summonheroic (7 Day Cooldown)";
            $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_RED . " * " . TextFormat::RESET . TextFormat::RED . "/ah limit increased from 8 to 12 items";
            $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_RED . " * " . TextFormat::RESET . TextFormat::RED . "Increased render distance by 1 chunk";
            $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_RED . " * " . TextFormat::RESET . TextFormat::RED . "Highest Join Priority";
            $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_RED . " * " . TextFormat::RESET . TextFormat::RED . "Access to /fix all (5 minute cooldown)";
            $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_RED . " * " . TextFormat::RESET . TextFormat::RED . "Light Red Global Rank Color";
            $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_RED . " * " . TextFormat::RESET . TextFormat::RED . "Light Red Global Chat Color";
            $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_RED . " * " . TextFormat::RESET . TextFormat::RED . "10% larger player model in safe zones";
            $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_RED . " * " . TextFormat::RESET . TextFormat::RED . "Access to the Presidential Lounge";
            $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_RED . " * " . TextFormat::RESET . TextFormat::RED . "No /cf Energy Tax";
            $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_RED . " * " . TextFormat::RESET . TextFormat::RED . "+10% Success rates when applying enchants";
            $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_RED . " * " . TextFormat::RESET . TextFormat::RED . "Access to the Presidential Slot Bot";
            $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_RED . " * " . TextFormat::RESET . TextFormat::RED . "Access to the Presidential Mystery Man";
        }
        $this->rank = $rank;
        parent::__construct(ItemFactory::getInstance()->get($id), $customName, $lore, false, true, $uuid);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::RANK => ByteTag::class
        ];
    }

    /**
     * @param Item $item
     *
     * @return $this
     */
    public static function fromItem(Item $item): self {
        $tag = self::getCustomTag($item);
        $rank = $tag->getByte(self::RANK);
        $rank = Nexus::getInstance()->getPlayerManager()->getRankManager()->getRankByIdentifier($rank);
        $uuid = $tag->getString(self::UUID);
        return new self($rank, $uuid);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setByte(self::RANK, $this->rank->getIdentifier());
        $tag->setString(self::UUID, $this->getUniqueId());
        return $tag;
    }

    /**
     * @return Rank
     */
    public function getRank(): Rank {
        return $this->rank;
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
        $rank = $this->getRank();
        if($rank->getIdentifier() === Rank::EMPEROR_HEROIC) {
            if($player->getDataSession()->getRank()->getIdentifier() !== Rank::EMPEROR) {
                $player->playErrorSound();
                $player->sendAlert(TextFormat::RED . "You need a rank of " . TextFormat::BOLD . TextFormat::WHITE  . "<" . TextFormat::AQUA . "Martian" . TextFormat::WHITE . ">" . TextFormat::RESET . TextFormat::RED . " rank to redeem this!");
                return;
            }
        }
        if($rank->getIdentifier() === Rank::PRESIDENT) {
            if($player->getDataSession()->getRank()->getIdentifier() !== Rank::EMPEROR_HEROIC) {
                $player->playErrorSound();
                $player->sendAlert(TextFormat::RED . "You need a rank of " . TextFormat::BOLD . TextFormat::WHITE  . "<" . TextFormat::YELLOW . "Martian" . TextFormat::GOLD . "+" . TextFormat::WHITE . ">" . TextFormat::RESET . TextFormat::RED . " rank to redeem this!");
                return;
            }
        }
        if($rank->getIdentifier() <= $player->getDataSession()->getRank()->getIdentifier()) {
            $player->playErrorSound();
            $player->sendAlert(TextFormat::RED . "Your current rank is better than this!");
            return;
        }
        /** @var Fireworks $fw */
        $fw = ItemFactory::getInstance()->get(ItemIds::FIREWORKS);
        $player->getDataSession()->setRank($rank);
        if($rank->getIdentifier() === Rank::EMPEROR_HEROIC) {
            $fw->addExplosion(Fireworks::TYPE_STAR, Fireworks::COLOR_YELLOW, Fireworks::COLOR_WHITE, true, true);
            $fw->addExplosion(Fireworks::TYPE_STAR, Fireworks::COLOR_GOLD, Fireworks::COLOR_BROWN, true, true);
            $player->getServer()->broadcastMessage(Translation::ORANGE . TextFormat::YELLOW . $player->getName() . " has claimed a Heroic Rank Crystal and unlocked " . $rank->getColoredName() . TextFormat::RESET . TextFormat::YELLOW . "!");
        }
        elseif($rank->getIdentifier() === Rank::PRESIDENT) {
            $fw->addExplosion(Fireworks::TYPE_STAR, Fireworks::COLOR_RED, Fireworks::COLOR_YELLOW, true, true);
            $fw->addExplosion(Fireworks::TYPE_STAR, Fireworks::COLOR_DARK_PURPLE, Fireworks::COLOR_PINK, true, true);
            $player->getServer()->broadcastMessage(Translation::RED . TextFormat::RED . $player->getName() . " has claimed a Rank Letter and unlocked " . $rank->getColoredName() . TextFormat::RESET . TextFormat::RED . "!");
        }
        else {
            $fw->addExplosion(Fireworks::TYPE_STAR, Fireworks::COLOR_WHITE, Fireworks::COLOR_YELLOW, true, true);
        }
        $player->sendMessage(Translation::getMessage("claimRank", [
            "name" => $rank->getColoredName()
        ]));
        $level = $player->getWorld();
        if($level !== null) {
            $fw->setFlightDuration(1);
            $pos = $player->getPosition();
            $yaw = lcg_value() * 360;
            $pitch = 90;
            $entity = new FireworksRocket(new Location($pos->x, $pos->y, $pos->z, $pos->getWorld(), $yaw, $pitch), $fw);
            if($entity instanceof FireworksRocket) {
                $entity->spawnToAll();
            }
        }
        $this->setUsed();
        $player->sendMessage(TextFormat::GRAY . "Congratulations! Enjoy the perks of your new rank!");
        $inventory->setItemInHand($item->setCount($item->getCount() - 1));
    }
}