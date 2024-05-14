<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\sets\utils\SetUtils;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\entity\Attribute;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;

class FinalStandEnchantment extends Enchantment {

    /** @var array */
    public static array $enhancedMovement = [];

    /**
     * FinalStandEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::FINAL_STAND, "Final Stand", self::EXECUTIVE, "Increased chance to prevent you from dying upon taking fatal damage by healing you and granting you with absorption temporarily (Requires Last Stand 5)", self::DAMAGE_BY_ALL, self::SLOT_ARMOR, 3, self::SLOT_NONE, self::LAST_STAND);
        $this->callable = function(EntityDamageEvent $event, int $level, float &$damage) {
            $level = min(3, $level);
            $entity = $event->getEntity();
            if(!$entity instanceof NexusPlayer) {
                return;
            }
            if($entity instanceof NexusPlayer) {
                if($entity->getHealth() <= $event->getFinalDamage()) {
                    $random = mt_rand(1, 5);
                    $chance = $level;
                    if($chance >= $random) {
                        $systemReboot = min(3, $entity->getCESession()->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::SYSTEM_REBOOT)));
                        if($systemReboot <= 0) return;
                        if((time() - $entity->getCESession()->getLastSystemReboot()) >= (540 / $systemReboot)) {
                            $price = 15000000 / $systemReboot;
                            if($entity->canPayEnergy($price)) {
                                return;
                            }
                        }
                        $event->cancel();
                        $pk = new LevelSoundEventPacket();
                        $pk->position = $entity->getPosition();
                        $pk->sound = LevelSoundEvent::BEACON_ACTIVATE;
                        $entity->getNetworkSession()->sendDataPacket($pk);
                        $entity->heal(new EntityRegainHealthEvent($entity, $entity->getMaxHealth() * ($level * 0.2), EntityRegainHealthEvent::CAUSE_CUSTOM));
                        $entity->getEffects()->add(new EffectInstance(VanillaEffects::ABSORPTION(), $level * 300, (int)$level, false));

                        $id = $entity->getUniqueId()->toString();
                        self::$enhancedMovement[$id] = $id;

                        $entity->getMotion()->multiply(1.5);
                        $entity->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 45 * 20, 1));

                        if(SetUtils::isWearingFullSet($entity, "underling")) {
                            Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use($id, $entity) {
                                if($entity !== null && isset(self::$enhancedMovement[$id])) $entity->getAttributeMap()->get(Attribute::MOVEMENT_SPEED)->setDefaultValue(self::$enhancedMovement[$id]);
                                unset(self::$enhancedMovement[$id]);
                            }), 45 * 20);
                        }

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
                            $player->sendMessage(TextFormat::LIGHT_PURPLE . TextFormat::BOLD . " ** FINAL STAND [" . TextFormat::RESET . TextFormat::GRAY . $entity->getName() . TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "] **");
                        }
                    }
                }
            }
        };
    }
}