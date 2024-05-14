<?php

namespace core\game\boss\entity;

use core\player\NexusPlayer;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\TNT;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Entity;
use pocketmine\entity\object\ItemEntity;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\world\sound\ExplodeSound;
use pocketmine\world\utils\SubChunkExplorer;
use pocketmine\world\utils\SubChunkExplorerStatus;
use pocketmine\world\World;
use pocketmine\world\World as Level;
use pocketmine\world\particle\HugeExplodeSeedParticle;
use pocketmine\world\Position;
use pocketmine\world\utils\SubChunkExplorer as SubChunkIteratorManager;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\block\tile\Chest;
use pocketmine\block\tile\Container;
use pocketmine\block\tile\Tile;
//use pocketmine\world\Explosion

class HadesExplosion {

    /** @var int */
    private $rays = 16;
    /** @var Level */
    public $level;
    /** @var Position */
    public $source;
    /** @var float */
    public $size;



    /** @var Block[] */
    public $affectedBlocks = [];
    /** @var float */
    public $stepLen = 0.3;
    /** @var Entity|Block|null */
    private $what;

    /** @var SubChunkIteratorManager */
    private $subChunkHandler;

    /**
     * @param Entity|Block|null $what
     */
    public function __construct(Position $center, float $size, $what = null){
        if(!$center->isValid()){
            throw new \InvalidArgumentException("Position does not have a valid world");
        }
        $this->source = $center;
        $this->level = $center->getWorld();

        if($size <= 0){
            throw new \InvalidArgumentException("Explosion radius must be greater than 0, got $size");
        }
        $this->size = $size;

        $this->what = $what;
        $this->subChunkHandler = new SubChunkIteratorManager($this->level, false);
    }
    private $istnt = false;
    /**
     * Calculates which blocks will be destroyed by this explosion. If explodeB() is called without calling this, no blocks
     * will be destroyed.
     */
    public function explodeA() : bool{
        if($this->size < 0.1){
            return false;
        }

        $vector = new Vector3(0, 0, 0);
        $vBlock = new Position(0, 0, 0, $this->level);

        $currentChunk = null;
        $currentSubChunk = null;

        $mRays = $this->rays - 1;
        for($i = 0; $i < $this->rays; ++$i){
            for($j = 0; $j < $this->rays; ++$j){
                for($k = 0; $k < $this->rays; ++$k){
                    if($i === 0 or $i === $mRays or $j === 0 or $j === $mRays or $k === 0 or $k === $mRays){
                        $vector = new Vector3($i / $mRays * 2 - 1, $j / $mRays * 2 - 1, $k / $mRays * 2 - 1);
                        //$vector->setComponents($i / $mRays * 2 - 1, $j / $mRays * 2 - 1, $k / $mRays * 2 - 1);
                        $vector = new Vector3(($vector->x / ($len = $vector->length())) * $this->stepLen, ($vector->y / $len) * $this->stepLen, ($vector->z / $len) * $this->stepLen);
                        $pointerX = $this->source->x;
                        $pointerY = $this->source->y;
                        $pointerZ = $this->source->z;

                        for($blastForce = $this->size * (mt_rand(700, 1300) / 1000); $blastForce > 0; $blastForce -= $this->stepLen * 0.75){
                            $x = (int) $pointerX;
                            $y = (int) $pointerY;
                            $z = (int) $pointerZ;
                            $vBlock->x = $pointerX >= $x ? $x : $x - 1;
                            $vBlock->y = $pointerY >= $y ? $y : $y - 1;
                            $vBlock->z = $pointerZ >= $z ? $z : $z - 1;

                            $pointerX += $vector->x;
                            $pointerY += $vector->y;
                            $pointerZ += $vector->z;

                            if($this->subChunkHandler->moveTo($vBlock->x, $vBlock->y, $vBlock->z) === SubChunkExplorerStatus::INVALID){
                                continue;
                            }

                            $blockId = $this->subChunkHandler->currentSubChunk->getFullBlock($vBlock->x & 0x0f, $vBlock->y & 0x0f, $vBlock->z & 0x0f);


                            if($blockId !== 0){
                                $blastForce -= (BlockFactory::getInstance()->blastResistance[$blockId] / 5 + 0.3) * $this->stepLen;
                                if($blastForce > 0){
                                    if(!isset($this->affectedBlocks[World::blockHash($vBlock->x, $vBlock->y, $vBlock->z)])){
                                        $_block = $this->level->getBlockAt($vBlock->x, $vBlock->y, $vBlock->z, true, false);
                                        foreach($_block->getAffectedBlocks() as $_affectedBlock){
                                            $_affectedBlockPos = $_affectedBlock->getPosition();
                                            $this->affectedBlocks[World::blockHash($_affectedBlockPos->x, $_affectedBlockPos->y, $_affectedBlockPos->z)] = $_affectedBlock;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return true;
    }



    public function IsTnT($bool){
        $this->istnt = $bool;
    }

    private $affectedEntities = [];

    /**
     * Executes the explosion's effects on the world. This includes destroying blocks (if any), harming and knocking back entities,
     * and creating sounds and particles.
     */
    public function explodeB() : bool{
        $updateBlocks = [];

        $source = (new Vector3($this->source->x, $this->source->y, $this->source->z))->floor();
        $yield = (1 / $this->size) * 100;

        if($this->what instanceof Entity){
            if($this->istnt == true) {
                $ev = new EntityExplodeEvent($this->what, $this->source, $this->affectedBlocks, $yield);
                $ev->call();
                if ($ev->isCancelled()) {
                    return false;
                } else {
                    $yield = $ev->getYield();
                    $this->affectedBlocks = $ev->getBlockList();
                }
            }
        }

        $explosionSize = $this->size * 2;
        $minX = (int) floor($this->source->x - $explosionSize - 1);
        $maxX = (int) ceil($this->source->x + $explosionSize + 1);
        $minY = (int) floor($this->source->y - $explosionSize - 1);
        $maxY = (int) ceil($this->source->y + $explosionSize + 1);
        $minZ = (int) floor($this->source->z - $explosionSize - 1);
        $maxZ = (int) ceil($this->source->z + $explosionSize + 1);

        $explosionBB = new AxisAlignedBB($minX, $minY, $minZ, $maxX, $maxY, $maxZ);

        $list = $this->level->getNearbyEntities($explosionBB, $this->what instanceof Entity ? $this->what : null);
        foreach($list as $entity){
            $distance = $entity->getPosition()->distance($this->source) / $explosionSize;

            if($distance <= 1 && !($entity instanceof ItemEntity)){
                $motion = $entity->getPosition()->subtractVector($this->source)->normalize();

                $impact = (1 - $distance) * ($exposure = 1);

                //$damage = (int) ((($impact * $impact + $impact) / 2) * 8 * $explosionSize + 1);
                $damage = 5;

                if($this->what instanceof Entity){
                    if($entity !== null && !$entity->isClosed()) {
                        if($entity instanceof NexusPlayer && $entity->isConnected()) {
                            $ev = new EntityDamageByEntityEvent($this->what, $entity, EntityDamageEvent::CAUSE_ENTITY_EXPLOSION, $damage);
                        }
                    }
                }elseif($this->what instanceof Block){
                    $ev = new EntityDamageByBlockEvent($this->what, $entity, EntityDamageEvent::CAUSE_BLOCK_EXPLOSION, $damage);
                }else{
                    $ev = new EntityDamageEvent($entity, EntityDamageEvent::CAUSE_BLOCK_EXPLOSION, $damage);
                }

                if(isset($ev)) {
                    $entity->attack($ev);
                }
                $entity->setMotion($motion->multiply($impact));
                $this->affectedEntities[] = $entity;
            }
        }

        $air = VanillaItems::AIR();

        foreach($this->affectedBlocks as $block){
            $yieldDrops = false;

            if($block instanceof TNT){
                $block->ignite(mt_rand(10, 30));
            }elseif($block->getId() == BlockLegacyIds::MONSTER_SPAWNER){
                $silk = ItemFactory::getInstance()->get(ItemIds::DIAMOND_PICKAXE);
                $silk->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SILK_TOUCH(), 1));
                foreach ($block->getDrops($silk) as $drop){
                    $this->level->dropItem($block->getPosition()->add(0.5, 0.5, 0.5), $drop);
                }
            }elseif($yieldDrops = (mt_rand(0, 100) < $yield)){
                foreach($block->getDrops($air) as $drop){
                    $this->level->dropItem($block->getPosition()->add(0.5, 0.5, 0.5), $drop);
                }
            }

            $this->level->setBlock($block->getPosition(), VanillaBlocks::AIR());
//            $this->level->setBlockIdAt($block->x, $block->y, $block->z, 0);
//            $this->level->setBlockDataAt($block->x, $block->y, $block->z, 0);

            $t = $this->level->getTileAt($block->x, $block->y, $block->z);
            if($t instanceof Tile){
                if($t instanceof Chest){
                    $t->unpair();
                }
                //if($yieldDrops and $t instanceof Container){
                    //$t->getInventory()->dropContents($this->level, $t->add(0.5, 0.5, 0.5));
                //}

                $t->close();
            }
        }

        foreach($this->affectedBlocks as $block){
            $pos = new Vector3($block->getPosition()->x, $block->getPosition()->y, $block->getPosition()->z);

            for($side = 0; $side <= 5; $side++){
                $sideBlock = $pos->getSide($side);
                if(!$this->level->isInWorld($sideBlock->x, $sideBlock->y, $sideBlock->z)){
                    continue;
                }
                if(!isset($this->affectedBlocks[$index = ((($sideBlock->x) & 0xFFFFFFF) << 36) | ((( $sideBlock->y) & 0xff) << 28) | (( $sideBlock->z) & 0xFFFFFFF)]) and !isset($updateBlocks[$index])){
                    $ev = new BlockUpdateEvent($this->level->getBlockAt($sideBlock->x, $sideBlock->y, $sideBlock->z));
                    $ev->call();
                    if(!$ev->isCancelled()){
                        foreach($this->level->getNearbyEntities(new AxisAlignedBB($sideBlock->x - 1, $sideBlock->y - 1, $sideBlock->z - 1, $sideBlock->x + 2, $sideBlock->y + 2, $sideBlock->z + 2)) as $entity){
                            $entity->onNearbyBlockChange();
                        }
                        $ev->getBlock()->onNearbyBlockChange();
                    }
                    $updateBlocks[$index] = true;
                }
            }
        }

        $this->level->addParticle($source, new HugeExplodeSeedParticle());
        $this->level->addSound($source, new ExplodeSound());

        return true;
    }

    public function getAffectedEntities(): array
    {
        return $this->affectedEntities;
    }

}