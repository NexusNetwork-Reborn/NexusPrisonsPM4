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

class AntiVirusEnchantment extends Enchantment {

    /**
     * AntiVirusEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::ANTI_VIRUS, "Anti Virus", self::ENERGY, "Chance to give massive healing to you and nearby allies (costs 200-400k energy upon activation)", self::DAMAGE_BY_ALL, self::SLOT_TORSO, 6);
        $this->callable = function(EntityDamageEvent $event, int $level, float &$damage) {
            $entity = $event->getEntity();
            if(!$entity instanceof NexusPlayer) {
                return;
            }
            if(($entity->getHealth() / $entity->getMaxHealth()) > 0.75) {
                return;
            }
            $world = $entity->getWorld();
            if($world === null) {
                return;
            }
            $random = mt_rand(1, 500);
            $chance = $level * $entity->getCESession()->getArmorLuckModifier();
            if($chance >= $random) {
                $gang = $entity->getDataSession()->getGang();
                if($gang === null) {
                    return;
                }
                $price = 200000 + (40000 * ($level - 1));
                if(!$entity->payEnergy($price)) {
                    return;
                }
                $p = [];
                $bb = $entity->getBoundingBox()->expandedCopy(20, 20, 20);
                foreach($world->getNearbyEntities($bb) as $e) {
                    if($e instanceof NexusPlayer) {
                        $p[] = $e;
                    }
                }
                if(empty($p)) {
                    return;
                }
                foreach($p as $player) {
                    if($gang->isInGang($player->getName())) {
                        $pk = new LevelSoundEventPacket();
                        $pk->position = $player->getPosition();
                        $pk->sound = LevelSoundEvent::BEACON_POWER;
                        $player->getNetworkSession()->sendDataPacket($pk);
                        $health = $player->getMaxHealth() * ($level * 0.05);
                        $player->heal(new EntityRegainHealthEvent($player, $health, EntityRegainHealthEvent::CAUSE_CUSTOM));
                    }
                    $player->sendMessage(TextFormat::AQUA . TextFormat::BOLD . " ** ANTIVIRUS [" . TextFormat::RESET . TextFormat::GRAY . $entity->getName() . ", -" . number_format($price) . " Energy" . TextFormat::AQUA . TextFormat::BOLD . "] **");
                }
            }
        };
    }
}