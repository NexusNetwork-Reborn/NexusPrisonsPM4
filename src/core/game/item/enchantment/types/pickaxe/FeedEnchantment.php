<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\pickaxe;

use core\game\item\enchantment\Enchantment;
use core\player\NexusPlayer;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;

class FeedEnchantment extends Enchantment {

    /**
     * FeedEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::FEED, "Feed", self::SIMPLE, "Chance to feed yourself while mining.", self::BREAK, self::SLOT_PICKAXE, 1);
        $this->callable = function(BlockBreakEvent $event, int $level) {
            $entity = $event->getPlayer();
            if(!$entity instanceof NexusPlayer) {
                return;
            }
            if($entity->getHungerManager()->getFood() > ($entity->getHungerManager()->getMaxFood() - 6)) {
                return;
            }
            $random = mt_rand(1, 20);
            $level *= $entity->getCESession()->getItemLuckModifier();
            if($level >= $random) {
                $pk = new LevelSoundEventPacket();
                $pk->position = $entity->getPosition();
                $pk->sound = LevelSoundEvent::BURP;
                $entity->getNetworkSession()->sendDataPacket($pk);
                $entity->getHungerManager()->setFood($entity->getHungerManager()->getMaxFood());
            }
            return;
        };
    }
}