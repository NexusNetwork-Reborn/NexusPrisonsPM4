<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use core\game\item\mask\Mask;
use core\game\item\types\vanilla\Armor;
use core\player\NexusPlayer;
use pocketmine\entity\EffectInstance;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class ConsecrationEnchantment extends Enchantment {

    /**
     * ConsecrationEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::CONSECRATION, "Consecration", self::LEGENDARY, "The closer you stay to the proc location the more damage you deal", self::DAMAGE, self::SLOT_ARMOR, 5);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $damager = $event->getDamager();
            if(!$damager instanceof NexusPlayer) {
                return;
            }
            $procLocation = $damager->getCESession()->getOffensiveProcLocation();
            if($procLocation === null) {
                return;
            }
            $cap = 1 + ($level * 0.025);
            $distance = $damager->getPosition()->distance($procLocation);
            if($distance >= 5) {
                return;
            }
            $cap -= ($distance / 5);
            $damage *= $cap;

            $ent = $event->getEntity();
            if($ent instanceof NexusPlayer) {
                /** @var Armor $helm */
                $helm = $ent->getArmorInventory()->getHelmet();
                if ($helm instanceof Armor && $helm->hasMask(Mask::CUPID)) {
                    $damage *= 0.5;
                }
            }
        };
    }
}