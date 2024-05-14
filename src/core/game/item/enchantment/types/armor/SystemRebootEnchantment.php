<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\utils\TextFormat;

class SystemRebootEnchantment extends Enchantment {

    /**
     * SystemRebootEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::SYSTEM_REBOOT, "System Reboot", self::ENERGY, "Heals you to full health upon taking fatal damage (3-9min cooldown, costs 5-15m energy upon activation)", self::DAMAGE_BY_ALL, self::SLOT_FEET, 3);
        $this->callable = function(EntityDamageEvent $event, int $level, float &$damage) {
            $level = min(3, $level);
            $entity = $event->getEntity();
            if(!$entity instanceof NexusPlayer) {
                return;
            }
            if($entity instanceof NexusPlayer) {
                if($entity->getHealth() <= $event->getFinalDamage()) {
                    if((time() - $entity->getCESession()->getLastSystemReboot()) >= (540 / $level)) {
                        $price = 15000000 / $level;
                        if($entity->payEnergy($price)) {
                            $event->cancel();
                            $entity->getCESession()->setLastSystemReboot();
                            $pk = new LevelSoundEventPacket();
                            $pk->position = $entity->getPosition();
                            $pk->sound = LevelSoundEvent::BEACON_POWER;
                            $entity->getNetworkSession()->sendDataPacket($pk);
                            $entity->heal(new EntityRegainHealthEvent($entity, $entity->getMaxHealth(), EntityRegainHealthEvent::CAUSE_CUSTOM));

                            $p = [];
                            $bb = $entity->getBoundingBox()->expandedCopy(20, 20, 20);
                            foreach($entity->getWorld()->getNearbyEntities($bb) as $e) {
                                if($e instanceof NexusPlayer) {
                                    $p[] = $e;
                                }
                            }
                            if(empty($p)) {
                                return;
                            }
                            foreach($p as $player) {
                                $player->sendMessage(TextFormat::AQUA . TextFormat::BOLD . " ** SYSTEM REBOOT [" . TextFormat::RESET . TextFormat::GRAY . $entity->getName() . ", -" . number_format($price) . " Energy" . TextFormat::AQUA . TextFormat::BOLD . "] **");
                            }
                        }
                    }
                }
            }
        };
    }
}