<?php

declare(strict_types=1);

namespace core\game\item\types\custom;

use core\display\animation\entity\FlareEntity;
use core\display\animation\entity\GKitEntity;
use core\display\animation\entity\TorchEntity;
use core\game\fund\FundManager;
use core\game\item\types\Interactive;
use core\game\item\types\vanilla\Armor;
use core\game\kit\GodKit;
use core\Nexus;
use core\player\NexusPlayer;
use libs\utils\LevelUtils;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;

class MeteorFlare extends Interactive {

    const METEOR = "Meteor";

    /**
     * MeteorFlare constructor.
     */
    public function __construct() {
        $customName = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "Meteor Flare";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::WHITE . "Causes a massive meteor to fall";
        $lore[] = TextFormat::RESET . TextFormat::WHITE . "wherever you place this!";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hint: Place on ground to spawn";
        parent::__construct(VanillaBlocks::TORCH()->asItem(), $customName, $lore);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::METEOR => StringTag::class,
        ];
    }


    /**
     * @param Item $item
     *
     * @return $this
     */
    public static function fromItem(Item $item): self {
        return new self();
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setString(self::METEOR, self::METEOR);
        return $tag;
    }

    /**
     * @param NexusPlayer $player
     * @param Inventory $inventory
     * @param Item $item
     * @param int $face
     * @param Block $block
     */
    public function onInteract(NexusPlayer $player, Inventory $inventory, Item $item, int $face, Block $block): void {
        if(!Nexus::getInstance()->getGameManager()->getFundManager()->isUnlocked(FundManager::PHASE_ONE)) {
            $player->sendTranslatedMessage("fundDisabled", [
                "feature" => TextFormat::RED . "Meteors"
            ]);
            return;
        }
        if($block->isSolid()) {
            $cd = $player->getCooldownLeft(300, NexusPlayer::SPAWN_FLARE);
            if($cd > 0) {
                $player->sendTranslatedMessage("actionCooldown", [
                    "amount" => TextFormat::RED . $cd
                ]);
                $player->playErrorSound();
                return;
            }
            $areaManager = Nexus::getInstance()->getServerManager()->getAreaManager();
            $areas = $areaManager->getAreasInPosition($block->getPosition());
            if($areas !== null) {
                foreach($areas as $area) {
                    if($area->getEditFlag() === false) {
                        $player->sendAlert(TextFormat::RED . "You can not spawn a flare in a safe zone!");
                        return;
                    }
                }
            }
            $world = $block->getPosition()->getWorld();
            $position = Position::fromObject($block->getPosition()->floor()->add(0, 1, 0), $world);
            $x = $position->getX();
            $y = $position->getY();
            $z = $position->getZ();
            $blocks = [];
            if($world !== null) {
                for($i = $x - 11; $i <= $x + 11; ++$i) {
                    for($j = $z - 11; $j <= $z + 11; ++$j) {
                        $location = LevelUtils::getSafeSpawn($world, new Vector3($i, 1, $j), $position->getFloorY() + 6);
                        $blocks[] = $location;
                    }
                }
                for($i = 1; $i < 30; $i++) {
                    if($world->getBlock(Position::fromObject($block->getPosition()->add(0, $i, 0), $world))->isSolid()) {
                        $player->playErrorSound();
                        $player->sendAlert(TextFormat::RED . "You can only spawn a flare in a clear area!");
                        return;
                    }
                }
                FlareEntity::create(Position::fromObject($position->add(0.5, 3, 0.5), $player->getWorld()), $blocks)->spawnToAll();
                $inventory->setItemInHand($item->setCount($item->getCount() - 1));
                $player->setCooldown(NexusPlayer::SPAWN_FLARE);
                $add = "";
                $zone = Nexus::getInstance()->getGameManager()->getZoneManager()->getZoneInPosition($position);
                if($zone !== null) {
                    switch($zone->getTierId()) {
                        case Armor::TIER_CHAIN:
                            $name = "Chain";
                            break;
                        case Armor::TIER_GOLD:
                            $name = "Gold";
                            break;
                        case Armor::TIER_IRON:
                            $name = "Iron";
                            break;
                        case Armor::TIER_DIAMOND | Armor::TIER_LEATHER:
                            $name = "Diamond";
                            break;
                        default:
                            return;
                    }
                    $add .= TextFormat::GRAY . " ($name Zone)";
                }
                $add .= TextFormat::WHITE . " ETA: 3 Minutes";
                Server::getInstance()->broadcastMessage(TextFormat::BOLD . TextFormat::GOLD . "(!) A meteor summoned by {$player->getName()} is falling from the sky at:");
                Server::getInstance()->broadcastMessage(TextFormat::RESET . TextFormat::WHITE . "$x" . TextFormat::GRAY . "x, " . TextFormat::WHITE . "$y" . TextFormat::GRAY . "y, " . TextFormat::WHITE . "$z" . TextFormat::GRAY . "z" . $add);
                Server::getInstance()->broadcastMessage(TextFormat::RESET . TextFormat::GRAY . "Meteors are from lost galaxies that give massive amounts of energy and loot.");
            }
        }
    }
}