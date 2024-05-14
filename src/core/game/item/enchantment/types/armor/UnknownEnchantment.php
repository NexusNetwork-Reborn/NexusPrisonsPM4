<?php

namespace core\game\item\enchantment\types\armor;

use core\game\item\enchantment\Enchantment;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

class UnknownEnchantment extends Enchantment {

    /**
     * UnknownEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::UNKNOWN, "Unknown", self::SIMPLE, "Chance on hit to hide your health.", self::DAMAGE_BY, self::SLOT_ARMOR, 3);
        $this->callable = function(EntityDamageByEntityEvent $event, int $level, float &$damage) {
            $level = min($level, 3);
            $entity = $event->getEntity();
            if(!$entity instanceof NexusPlayer) {
                return;
            }
            if($entity->getCESession()->isHidingHealth()) {
                return;
            }
            $random = mt_rand(1, 250);
            $chance = min(5, $level * $entity->getCESession()->getArmorLuckModifier());
            if($chance >= $random) {
                $entity->getCESession()->setHidingHealth(true);
                Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new class($entity) extends Task {

                    /** @var NexusPlayer */
                    private $player;

                    /**
                     *  constructor.
                     *
                     * @param NexusPlayer $player
                     */
                    public function __construct(NexusPlayer $player) {
                        $this->player = $player;
                    }

                    /**
                     * @param int $currentTick
                     */
                    public function onRun(): void {
                        if($this->player->isOnline()) {
                            $this->player->getCESession()->setHidingHealth(false);
                            $hp = round($this->player->getHealth(), 1);
                            $this->player->setScoreTag(TextFormat::WHITE . $hp . TextFormat::RED . TextFormat::BOLD . " HP");
                        }
                    }
                }, 50 * $level);
            }
        };
    }
}