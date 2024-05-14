<?php
declare(strict_types=1);

namespace core\game\zone;

use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Axe;
use core\game\item\types\vanilla\Sword;
use core\level\LevelManager;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\item\ToolTier;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use pocketmine\world\World;

class ZoneListener implements Listener {

    const TIER_TO_ATTACK_POINTS = [
        Armor::TIER_LEATHER => 7,
        Armor::TIER_CHAIN => 4,
        Armor::TIER_GOLD => 5,
        Armor::TIER_IRON => 6,
        Armor::TIER_DIAMOND => 7
    ];

    const ITEM_TO_ARMOR_TIER = [
        1 => Armor::TIER_CHAIN,
        3 => Armor::TIER_CHAIN,
        2 => Armor::TIER_GOLD,
        4 => Armor::TIER_IRON,
        5 => Armor::TIER_DIAMOND
    ];

    /** @var Nexus */
    private $core;

    /** @var Zone[] */
    private $zones = [];

    /** @var string[][] */
    private $oldBlocks = [];

    /** @var int */
    private int $minX, $minZ, $maxX, $maxZ;

    /**
     * ZoneListener constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;

        $setup = LevelManager::getSetup();
        $xz1 = explode(":", $setup->getNested("world-border.xz1"));
        $xz2 = explode(":", $setup->getNested("world-border.xz2"));
        $this->minX = (int)$xz1[0];
        $this->maxX = (int)$xz2[0];
        $this->minZ = (int)$xz1[1];
        $this->maxZ = (int)$xz2[1];
    }

    public function onPlayerTeleport(EntityTeleportEvent $event) {
        $e = $event->getEntity();
        if($e instanceof Player) {
            $from = $event->getFrom();
            $to = $event->getTo();
            $this->onPlayerMove(new PlayerMoveEvent($e, Location::fromObject($from, $from->getWorld()), Location::fromObject($to, $to->getWorld())));
        }
    }

    /**
     * @priority NORMAL
     *
     * @param PlayerMoveEvent $event
     */
    public function onPlayerMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if(!$player->isLoaded()) {
            return;
        }
        $zone = $player->getZone();
        $next = $this->core->getGameManager()->getZoneManager()->getZoneInPosition($event->getTo());
        if($next === null) {
            if($zone !== null) {
                $player->setZone(null);
            }
            return;
        }
        switch($next->getTierId()) {
            case Armor::TIER_CHAIN:
                $color = TextFormat::DARK_GRAY;
                $name = "Chain";
                break;
            case Armor::TIER_GOLD:
                $color = TextFormat::YELLOW;
                $name = "Gold";
                break;
            case Armor::TIER_IRON:
                $color = TextFormat::WHITE;
                $name = "Iron";
                break;
            case Armor::TIER_DIAMOND:
                $color = TextFormat::AQUA;
                $name = "Diamond";
            break;
            case Armor::TIER_LEATHER:
                $color = TextFormat::LIGHT_PURPLE;
                $name = "Leather";
                break;
            default:
                return;
        }
        if($zone === null or $zone->getTierId() !== $next->getTierId()) {
            $player->setZone($next);
            $player->sendTitle(" ", $color . "(!) You have entered the " . TextFormat::BOLD . "$name Zone");
            $player->getCESession()->setActiveHeldItemEnchantments();
            $player->getCESession()->setActiveArmorEnchantments();
            $maxLevel = ZoneManager::getArmorMaxLevel($next->getTierId());
            foreach($player->getArmorInventory()->getContents() as $content) {
                if($content instanceof Armor and $content->getTierId() > $next->getTierId()) {
                    $contentColor = match ($content->getTierId()) {
                        Armor::TIER_CHAIN => TextFormat::DARK_GRAY,
                        Armor::TIER_GOLD => TextFormat::YELLOW,
                        Armor::TIER_IRON => TextFormat::WHITE,
                        Armor::TIER_DIAMOND => TextFormat::AQUA,
                        Armor::TIER_LEATHER => TextFormat::LIGHT_PURPLE,
                        default => TextFormat::DARK_RED,
                    };
                    $totalLevels = 0;
                    if($content->hasEnchantments()) {
                        foreach($content->getEnchantments() as $ei) {
                            $totalLevels += $ei->getLevel();
                        }
                    }
                    $max = min($totalLevels, $maxLevel);
                    $customName = TextFormat::clean($content->hasOriginalCustomName() ? $content->getOriginalCustomName() : $content->getVanillaName());
                    $player->sendMessage($contentColor . TextFormat::BOLD . $customName . TextFormat::GREEN . " $totalLevels" . TextFormat::WHITE . TextFormat::BOLD . " ➤ " . $color . $customName . TextFormat::GREEN . " $max");
                }
            }
            $heldItem = $player->getInventory()->getItemInHand();
            if($heldItem instanceof Axe or $heldItem instanceof Sword) {
                if(isset(self::ITEM_TO_ARMOR_TIER[$heldItem->getTier()->getHarvestLevel()])) {
                    if(self::ITEM_TO_ARMOR_TIER[$heldItem->getTier()->getHarvestLevel()] > $next->getTierId()) {
                        $contentColor = match ($heldItem->getTierId()) {
                            ToolTier::WOOD()->id() => TextFormat::DARK_RED,
                            ToolTier::STONE()->id() => TextFormat::DARK_GRAY,
                            ToolTier::GOLD()->id() => TextFormat::YELLOW,
                            ToolTier::IRON()->id() => TextFormat::WHITE,
                            default => TextFormat::AQUA,
                        };
                        $totalLevels = 0;
                        if($heldItem->hasEnchantments()) {
                            foreach($heldItem->getEnchantments() as $ei) {
                                $totalLevels += $ei->getLevel();
                            }
                        }
                        $max = min($totalLevels, $maxLevel);
                        $customName = TextFormat::clean($heldItem->hasOriginalCustomName() ? $heldItem->getOriginalCustomName() : $heldItem->getVanillaName());
                        $player->sendMessage($contentColor . TextFormat::BOLD . $customName . TextFormat::GREEN . " $totalLevels" . TextFormat::WHITE . TextFormat::BOLD . " ➤ " . $color . $customName . TextFormat::GREEN . " $max");
                    }
                }
            }
        }
    }

    /**
     * @priority LOWEST
     * @param PlayerMoveEvent $event
     */
    public function onPlayerMove2(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $level = $player->getWorld();
        if($level === null) {
            return;
        }
        if($level->getFolderName() !== $this->core->getServer()->getWorldManager()->getDefaultWorld()->getFolderName()) {
            return;
        }
        $minX = $this->minX;
        $maxX = $this->maxX;
        $minZ = $this->minZ;
        $maxZ = $this->maxZ;
        $x = $player->getPosition()->getFloorX();
        $z = $player->getPosition()->getFloorZ();
        if($x >= $maxX and $z >= $maxZ) {
            $player->teleport(new Position($maxX - 1, $player->getPosition()->getY(), $maxZ - 1, $player->getWorld()));
        }
        elseif($x <= $minX and $z <= $minZ) {
            $player->teleport(new Position($minX + 1, $player->getPosition()->getY(), $minZ + 1, $player->getWorld()));
        }
        elseif($x >= $maxX) {
            $player->teleport(new Position($maxX - 1, $player->getPosition()->getY(), $z, $player->getWorld()));
        }
        elseif($z >= $maxZ) {
            $player->teleport(new Position($x, $player->getPosition()->getY(), $maxZ - 1, $player->getWorld()));
        }
        elseif($x <= $minX) {
            $player->teleport(new Position($minX + 1, $player->getPosition()->getY(), $z, $player->getWorld()));
        }
        elseif($z <= $minZ) {
            $player->teleport(new Position($x, $player->getPosition()->getY(), $minZ + 1, $player->getWorld()));
        }
        $to = $event->getTo();
        $from = $event->getFrom();
        if($to->getFloorY() > 256) {
            return;
        }
        if($to->getFloorY() <= 0) {
            return;
        }
        if(($x > ($maxX - 5) or $x < ($minX + 5) or $z > ($maxZ - 5) or $z < ($minZ + 5)) and (!$from->floor()->equals($to->floor()))) {
            $this->updateBorders($player, $to);
        }
    }

    /**
     * @param NexusPlayer $player
     * @param Position $newPosition
     */
    public function updateBorders(NexusPlayer $player, Position $newPosition): void {
        $x = $newPosition->getFloorX();
        $y = $newPosition->getFloorY();
        $z = $newPosition->getFloorZ();
        $level = $player->getWorld();
        if($level === null) {
            return;
        }
        $oldBlocks = [];
        if(isset($this->oldBlocks[$player->getUniqueId()->toString()])) {
            $oldBlocks = $this->oldBlocks[$player->getUniqueId()->toString()];
        }
        $this->oldBlocks[$player->getUniqueId()->toString()] = [];
        $blocks = [];
        $minX = $this->minX;
        $maxX = $this->maxX;
        $minZ = $this->minZ;
        $maxZ = $this->maxZ;
        $border = $maxX;
        if($x > ($maxX - 5) or $x < ($minX + 5)) {
            if($x < 0) {
                $border = $minX;
            }
            for($i = $y - 1; $i <= $y + 2; $i++) {
                for($j = $z - 2; $j <= $z + 2; $j++) {
                    if($i >= 256) {
                        break;
                    }
                    $vector = new Vector3($border, $i, $j);
                    if(($border < 0 and $j < $border) or ($border > 0 and $j > $border)) {
                        continue;
                    }
                    if($level->getBlock($vector)->isSolid()) {
                        continue;
                    }
                    if(isset($blocks[World::blockHash($border, $i, $j)])) {
                        continue;
                    }
                    $blocks[World::blockHash($border, $i, $j)] = VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::RED());
                }
            }
        }
        $border = $maxZ;
        if($z > ($maxZ - 5) or $z < ($minZ + 5)) {
            if($z < 0) {
                $border = $minZ;
            }
            for($i = $y - 1; $i <= $y + 2; $i++) {
                for($j = $x - 2; $j <= $x + 2; $j++) {
                    if($i >= 256) {
                        break;
                    }
                    $vector = new Vector3($j, $i, $border);
                    if(($border < 0 and $j < $border) or ($border > 0 and $j > $border)) {
                        continue;
                    }
                    if($level->getBlock($vector)->isSolid()) {
                        continue;
                    }
                    if(isset($blocks[World::blockHash($j, $i, $border)])) {
                        continue;
                    }
                    $blocks[World::blockHash($j, $i, $border)] = VanillaBlocks::STAINED_GLASS()->setColor(DyeColor::RED());
                }
            }
        }
        $this->oldBlocks[$player->getUniqueId()->toString()] = array_merge($this->oldBlocks[$player->getUniqueId()->toString()], array_keys($blocks));
        foreach($oldBlocks as $hash) {
            if(!isset($blocks[$hash])) {
                $blocks[$hash] = VanillaBlocks::AIR();
            }
        }
        if(empty($blocks)) {
            return;
        }
        foreach($blocks as $hash => $block) {
            World::getBlockXYZ($hash, $x, $y, $z);
            $packet = new UpdateBlockPacket();
            $packet->blockPosition = new BlockPosition($x, $y, $z);
            $packet->blockRuntimeId = RuntimeBlockMapping::getInstance()->toRuntimeId($block->getFullId());
            $player->getNetworkSession()->sendDataPacket($packet);
        }
    }
}