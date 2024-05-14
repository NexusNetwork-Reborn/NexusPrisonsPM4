<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon;

use core\game\item\enchantment\Enchantment;
use core\level\entity\types\PummelBlock;
use core\player\NexusPlayer;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\world\Position;

class PummelEnchantment extends Enchantment {

    /**
     * PummelEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::PUMMEL, "Pummel", self::ULTIMATE, "Chance to cause blocks to float up out of the ground and thrust them at your enemy.", self::DAMAGE, self::SLOT_SWORD, 3, self::SLOT_AXE);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            $damager = $event->getDamager();
            if(!$damager instanceof NexusPlayer) {
                return;
            }
            $random = mt_rand(1, 200);
            $chance = $level * $damager->getCESession()->getItemLuckModifier();
            if($chance >= $random) {
                $position = $entity->getPosition();
                $cx = $position->getX();
                $cy = $position->getY();
                $cz = $position->getZ();
                $radius = 3;
                $blocks = [
                    VanillaBlocks::DIRT(),
                    VanillaBlocks::GRASS(),
                    VanillaBlocks::DIORITE(),
                    VanillaBlocks::STONE()
                ];
                $delay = 5;
                for($i = 0; $i < $level * 3; $i += 1.1) {
                    $x = $cx + ($radius * cos($i));
                    $z = $cz + ($radius * sin($i));
                    $pos = new Position($x, $cy, $z, $entity->getWorld());
                    $block = PummelBlock::create($damager, $entity, $blocks[array_rand($blocks)], $pos, $delay);
                    $delay += 5;
                    $block->spawnToAll();
                }
            }
        };
    }
}