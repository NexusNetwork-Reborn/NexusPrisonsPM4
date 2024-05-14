<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use core\game\item\mask\Mask;
use core\game\item\types\vanilla\Armor;
use core\player\NexusPlayer;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class ToxicMistEnchantment extends Enchantment {

    /**
     * ToxicMistEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::TOXIC_MIST, "Toxic Mist", self::ULTIMATE, "Chance to spawn a poisonous cloud upon being attacked.", self::DAMAGE_BY, self::SLOT_ARMOR, 3);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $player = $event->getEntity();
            $damager = $event->getDamager();
            $bb = $damager->getBoundingBox()->expandedCopy(15, 15, 15);
            $world = $damager->getWorld();
            if($world === null) {
                return;
            }
            if($player instanceof NexusPlayer) {
                /** @var Armor $helm */
                $helm = $player->getArmorInventory()->getHelmet();
                if($helm instanceof Armor && $helm->hasMask(Mask::BUFF)) {
                    return;
                }
                $gang = $damager->getDataSession()->getGang();
                if($player->getHealth() <= 10) {
                    $random = mt_rand(1, 350);
                    $chance = $level * $player->getCESession()->getArmorLuckModifier();
                    if($chance >= $random) {
                        foreach($world->getNearbyEntities($bb) as $e) {
                            if($e->getId() === $damager->getId()) {
                                continue;
                            }
                            if(!$e instanceof NexusPlayer) {
                                continue;
                            }
                            if($gang !== null and $gang->isInGang($e->getName())) {
                                continue;
                            }
                            if(!$e->getEffects()->has(VanillaEffects::POISON())) {
                                $e->getEffects()->add(new EffectInstance(VanillaEffects::POISON(), ($level + 3) * 20, 1, false));
                            }
                        }
                    }
                }
            }
        };
    }
}