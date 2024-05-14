<?php

declare(strict_types=1);

namespace core\game\quest\types;

use core\display\animation\entity\MeteorBaseEntity;
use core\game\item\event\EarnEnergyEvent;
use core\game\quest\Quest;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class DamageByMeteorQuest extends Quest {

    /**
     * EarnEnergyQuest constructor.
     *
     * @param string $name
     * @param string $description
     * @param string $rarity
     * @param int $targetValue
     */
    public function __construct(string $name, string $description, string $rarity, int $targetValue) {
        $callable = function(EntityDamageByEntityEvent $event): void {
            $player = $event->getEntity();
            if(!$player instanceof NexusPlayer) {
                return;
            }
            $damager = $event->getDamager();
            if(!$damager instanceof MeteorBaseEntity) {
                return;
            }
            $progress = $player->getDataSession()->getQuestProgress($this->getName());
            if($progress < $this->targetValue) {
                $progress = min($this->targetValue, (++$progress));
                $player->getDataSession()->setQuestProgress($this->getName(), $progress);
                if($progress >= $this->targetValue) {
                    $this->celebrate($player);
                }
            }
        };
        parent::__construct($name, $description, $rarity, self::DAMAGE_BY_METEOR, $targetValue, $callable);
    }
}