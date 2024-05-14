<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\player\NexusPlayer;
use pocketmine\entity\EffectInstance;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\utils\TextFormat;

class LastStandEnchantment extends Enchantment {

    /**
     * LastStandEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::LAST_STAND, "Last Stand", self::LEGENDARY, "Chance to prevent you from dying upon taking fatal damage and heals you slightly.", self::DAMAGE_BY_ALL, self::SLOT_ARMOR, 5);
        $this->callable = function(EntityDamageEvent $event, int $level, float &$damage) {
            $level = min(5, $level);
            $entity = $event->getEntity();
            if(!$entity instanceof NexusPlayer) {
                return;
            }
            if($entity instanceof NexusPlayer) {
                if($entity->getHealth() <= $event->getFinalDamage()) {
                    $random = mt_rand(1, 10);
                    if($level >= $random) {
                        $systemReboot = min(3, $entity->getCESession()->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::SYSTEM_REBOOT)));
                        if($systemReboot > 0) {
                            if((time() - $entity->getCESession()->getLastSystemReboot()) >= (540 / $systemReboot)) {
                                $price = 15000000 / $systemReboot;
                                if($entity->canPayEnergy($price)) {
                                    return;
                                }
                            }
                        }
                        $event->cancel();
                        $pk = new LevelSoundEventPacket();
                        $pk->position = $entity->getPosition();
                        $pk->sound = LevelSoundEvent::BEACON_ACTIVATE;
                        $entity->getNetworkSession()->sendDataPacket($pk);
                        $entity->heal(new EntityRegainHealthEvent($entity, $entity->getMaxHealth() * ($level * 0.075), EntityRegainHealthEvent::CAUSE_CUSTOM));
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
                            $player->sendMessage(TextFormat::GOLD . TextFormat::BOLD . " * LAST STAND [" . TextFormat::RESET . TextFormat::GRAY . $entity->getName() . TextFormat::BOLD . TextFormat::GOLD . "] *");
                        }
                    }
                }
            }
        };
    }
}