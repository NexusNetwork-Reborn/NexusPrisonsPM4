<?php

declare(strict_types=1);

namespace core\level\entity\types;

use core\game\item\enchantment\types\weapon\SystemElectrocutionEnchantment;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;

class Lightning extends Entity {

    /** @var int */
    protected $age = 0;

    /** @var int */
    protected $strikes = 1;

    /** @var int */
    protected $damageDone = 5;

    protected function getInitialSizeInfo(): EntitySizeInfo {
        return new EntitySizeInfo(1.8, 0.3);
    }

    /**
     * @return string
     */
    public static function getNetworkTypeId(): string {
        return EntityIds::LIGHTNING_BOLT;
    }

    /**
     * @return string
     */
    public function getName(): string{
        return "Lightning";
    }

    /**
     * @param int $tickDiff
     *
     * @return bool
     */
    protected function entityBaseTick(int $tickDiff = 1): bool {
        if($this->closed) {
            return false;
        }
        $hasUpdate = parent::entityBaseTick($tickDiff);
        if(++$this->age % 20 == 0) {
            $target = $this->getTargetEntity();
            if($target !== null) {
                if(!$target->isAlive()) {
                    $this->flagForDespawn();
                    return false;
                }
                $ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_CUSTOM, $this->damageDone, [], 0);
                $ev->call();
                if(!$ev->isCancelled()) {
                    $target->attack($ev);
                }
                $this->playImpactSound();
                $this->spawnStrike();
            }
        }
        if($this->age > ($this->strikes * 20)) {
            $this->flagForDespawn();
        }
        return $hasUpdate;
    }

    /**
     * @param int $strikes
     */
    public function setStrikes(int $strikes): void {
        $this->strikes = $strikes;
    }

    /**
     * @param int $damageDone
     */
    public function setDamageDone(int $damageDone): void {
        $this->damageDone = $damageDone;
    }

    public function playImpactSound(): void {
        $spk = new PlaySoundPacket();
        $spk->soundName = "ambient.weather.lightning.impact";
        $spk->x = $this->getPosition()->getX();
        $spk->y = $this->getPosition()->getY();
        $spk->z = $this->getPosition()->getZ();
        $spk->volume = 500;
        $spk->pitch = 1;
        foreach($this->getViewers() as $p) {
            $p->getNetworkSession()->sendDataPacket($spk);
        }
    }

    public function spawnStrike(): void {
        $target = $this->getTargetEntity();
        if($target === null) {
            return;
        }
        $pk = new AddActorPacket();
        $pk->actorRuntimeId = Entity::nextRuntimeId();
        $pk->actorUniqueId = $pk->actorRuntimeId;
        $pk->type = EntityIds::LIGHTNING_BOLT;
        $pk->position = $target->getPosition();
        $pk->motion = $this->getMotion();
        $pk->yaw = $this->getLocation()->getYaw();
        $pk->headYaw = $this->getLocation()->getYaw();
        $pk->pitch = $this->getLocation()->getPitch();
        $pk->attributes = $this->attributeMap->getAll();
        $pk->metadata = $this->getAllNetworkData();
        $pk->syncedProperties = new PropertySyncData([], []);
        foreach($this->getViewers() as $player) {
            $player->getNetworkSession()->sendDataPacket($pk);
        }
    }
}