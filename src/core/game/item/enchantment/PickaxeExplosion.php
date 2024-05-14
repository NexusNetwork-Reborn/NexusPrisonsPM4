<?php

declare(strict_types=1);

namespace core\game\item\enchantment;

use core\level\block\Ore;
use core\level\tile\CrudeOre;
use core\level\tile\Meteorite;
use core\player\NexusPlayer;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\world\Explosion;
use pocketmine\world\particle\HugeExplodeSeedParticle;
use pocketmine\world\Position;

class PickaxeExplosion extends Explosion {

    /** @var NexusPlayer */
    protected $what;

    /**
     * PickaxeExplosion constructor.
     *
     * @param Position $center
     * @param float $size
     * @param NexusPlayer $what
     */
   public function __construct(Position $center, float $size, NexusPlayer $what) {
       parent::__construct($center, $size, $what);
       $this->what = $what;
   }

    /**
     * @return bool
     */
    public function explodeB(): bool {
        $send = [];
        $updateBlocks = [];
        $source = (new Vector3($this->source->x, $this->source->y, $this->source->z))->floor();
        $ev = new EntityExplodeEvent($this->what, $this->source, $this->affectedBlocks, (1 / $this->size) * 100);
        $ev->call();
        if($ev->isCancelled()) {
            return false;
        }
        else {
            $this->affectedBlocks = $ev->getBlockList();
        }
        $item = $this->what->getInventory()->getItemInHand();
        $this->what->getCESession()->setExplode(true);
        foreach($this->affectedBlocks as $key => $block) {
            $t = $this->world->getTileAt($block->getPosition()->getFloorX(), $block->getPosition()->getFloorY(), $block->getPosition()->getFloorZ());
            if((!$t instanceof Meteorite) and (!$t instanceof CrudeOre)) {
                if(!$block instanceof Ore) {
                    continue;
                }
            }
            $this->what->getWorld()->useBreakOn($block->getPosition(), $item, $this->what);
            $pos = new Vector3($block->getPosition()->x, $block->getPosition()->y, $block->getPosition()->z);
            for($side = 0; $side <= 5; $side++) {
                $sideBlock = $pos->getSide($side);
                if(!$this->world->isInWorld($sideBlock->getFloorX(), $sideBlock->getFloorY(), $sideBlock->getFloorZ())) {
                    continue;
                }
                if(!isset($this->affectedBlocks[$index = ((($sideBlock->x) & 0xFFFFFFF) << 36) | ((($sideBlock->y) & 0xff) << 28) | (($sideBlock->z) & 0xFFFFFFF)]) and !isset($updateBlocks[$index])) {
                    $ev = new BlockUpdateEvent($this->world->getBlockAt($sideBlock->getFloorX(), $sideBlock->getFloorY(), $sideBlock->getFloorZ()));
                    $ev->call();
                    if(!$ev->isCancelled()) {
                        foreach($this->world->getNearbyEntities(new AxisAlignedBB($sideBlock->x - 1, $sideBlock->y - 1, $sideBlock->z - 1, $sideBlock->x + 2, $sideBlock->y + 2, $sideBlock->z + 2)) as $entity) {
                            $entity->onNearbyBlockChange();
                        }
                        $ev->getBlock()->onNearbyBlockChange();
                    }
                    $updateBlocks[$index] = true;
                }
            }
            $send[] = new Vector3($block->getPosition()->x - $source->x, $block->getPosition()->y - $source->y, $block->getPosition()->z - $source->z);
        }
        $this->what->getCESession()->setExplode(false);
        $this->world->addParticle($source, new HugeExplodeSeedParticle(), [$this->what]);
        $pk = new LevelSoundEventPacket();
        $pk->sound = LevelSoundEvent::EXPLODE;
        $pk->position = $source;
        $this->what->getNetworkSession()->sendDataPacket($pk);
        return true;
    }
}