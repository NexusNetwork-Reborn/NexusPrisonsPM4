<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon;

use core\game\item\enchantment\Enchantment;
use core\level\entity\types\PummelBlock;
use core\player\NexusPlayer;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\world\Position;

class EndlessPummelEnchantment extends Enchantment {

    /**
     * PummelEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::ENDLESS_PUMMEL, "Endless Pummel", self::EXECUTIVE, "Tears Earth out of the ground and thrusts it at your enemy causing damage and slowness (Requires Pummel 3)", self::DAMAGE, self::SLOT_SWORD, 4, self::SLOT_AXE, self::PUMMEL);
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
                for($i = 0; $i < ($level + 3) * 3; $i += 1.1) {
                    $x = $cx + ($radius * cos($i));
                    $z = $cz + ($radius * sin($i));
                    $pos = new Position($x, $cy, $z, $entity->getWorld());
                    $block = PummelBlock::create($damager, $entity, $blocks[array_rand($blocks)], $pos, $delay);
                    $delay += 5;
                    $block->spawnToAll();
                }
                if($entity instanceof Living) {
                    $entity->getEffects()->add(new EffectInstance(VanillaEffects::SLOWNESS(), $level * 40, 2, false));
                }
            }
        };
    }
}