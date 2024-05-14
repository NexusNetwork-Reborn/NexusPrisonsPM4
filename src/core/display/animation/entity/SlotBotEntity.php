<?php

namespace core\display\animation\entity;

use core\game\slotbot\SlotBotUI;
use core\level\FakeChunkLoader;
use core\player\NexusPlayer;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class SlotBotEntity extends Entity {

    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);
        $this->setNameTagAlwaysVisible(true);
        $this->setNameTagVisible(true);
        $this->setNameTag(
            TextFormat::AQUA . TextFormat::BOLD . "Cosmo" . TextFormat::WHITE . "-"  . TextFormat::colorize("&dSlot Bot") .
            "\n" . TextFormat::GRAY . "Right-Click to open the SlotBot."
        );

        $x = $location->getFloorX() >> 4;
        $z = $location->getFloorZ() >> 4;
        $location->getWorld()->registerChunkLoader(new FakeChunkLoader($x, $z), $x, $z);
    }

    /**
     * @return EntitySizeInfo
     */
    protected function getInitialSizeInfo(): EntitySizeInfo {
        return new EntitySizeInfo(1, 1);
    }

    /**
     * @return string
     */
    public static function getNetworkTypeId(): string {
        return EntityIds::ENDER_CRYSTAL;
    }

    public function onInteract(Player $player, Vector3 $clickPos): bool
    {
        (new SlotBotUI($player))->send(true);
        return true;
    }

    public function attack(EntityDamageEvent $source): void
    {
        if($source instanceof EntityDamageByEntityEvent) {
            $ent = $source->getDamager();
            if($ent instanceof NexusPlayer) {
                (new SlotBotUI($ent))->send(true);
            }
        }
        $source->cancel(); // TODO: Test
        parent::attack($source);
    }

    public function canSaveWithChunk(): bool
    {
        return false;
    }
}