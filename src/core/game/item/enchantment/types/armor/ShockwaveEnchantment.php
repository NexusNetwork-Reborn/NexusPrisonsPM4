<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use core\game\item\mask\Mask;
use core\game\item\types\vanilla\Armor;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\utils\TextFormat;

class ShockwaveEnchantment extends Enchantment {

    /**
     * ShockwaveEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::SHOCKWAVE, "Shockwave", self::ELITE, "Chance on hit to shove players away from you.", self::DAMAGE_BY, self::SLOT_ARMOR, 3);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $player = $event->getEntity();
            if($player instanceof NexusPlayer) {
                $bb = $player->getBoundingBox()->expandedCopy(10, 10, 10);
                $world = $player->getWorld();
                if($world === null) {
                    return;
                }
                $gang = $player->getDataSession()->getGang();
                $random = mt_rand(1, 500);
                $chance = min(25, $level * $player->getCESession()->getArmorLuckModifier());
                if($chance >= $random) {
                    foreach($world->getNearbyEntities($bb) as $e) {
                        if(!$e instanceof NexusPlayer) {
                            continue;
                        }
                        if($e->getId() === $player->getId()) {
                            continue;
                        }
                        if($gang !== null and $gang->isInGang($e->getName())) {
                            continue;
                        }
                        $helm = $e->getArmorInventory()->getHelmet();
                        if ($helm instanceof Armor && $helm->hasMask(Mask::FOCUS)) {
                            continue;
                        }
                        $e->knockBack($e->getPosition()->getX() - $player->getPosition()->getX(), $e->getPosition()->getZ() - $player->getPosition()->getZ(), 0.05 * $level);
                        $e->addCEPopup(TextFormat::BLUE . TextFormat::BOLD . "* SHOCKWAVE [" . TextFormat::RESET . TextFormat::RED . $player->getName() . TextFormat::BLUE . TextFormat::BOLD . "] *");
                    }
                }
            }
            return;
        };
    }
}