<?php
declare(strict_types=1);

namespace core\level\entity\types;

use core\game\item\types\vanilla\Fireworks;
use core\level\entity\animation\FireworkParticlesAnimation;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\world\sound\LaunchSound;

class FireworksRocket extends Entity {

    /** @var int */
    public const DATA_FIREWORK_ITEM = 16; //firework item

    /** @var int */
    protected $lifeTime = 0;

    protected function getInitialSizeInfo(): EntitySizeInfo {
        return new EntitySizeInfo(0.25,  0.25);
    }

    /**
     * @return string
     */
    public static function getNetworkTypeId(): string {
        return EntityIds::FIREWORKS_ROCKET;
    }

    /**
     * FireworksRocket constructor.
     *
     * @param Location $location
     * @param Fireworks|null $fireworks
     * @param CompoundTag|null $nbt
     */
    public function __construct(Location $location, ?Fireworks $fireworks = null, ?CompoundTag $nbt = null){
        parent::__construct($location, $nbt);
        if($fireworks !== null && $fireworks->getNamedTag()->getCompoundTag("Fireworks") instanceof CompoundTag) {
            $this->getNetworkProperties()->setCompoundTag(self::DATA_FIREWORK_ITEM, new CacheableNbt($fireworks->getNamedTag()));
            $this->setLifeTime($fireworks->getRandomizedFlightDuration());
        }
        $location->getWorld()->addSound($location, new LaunchSound());
        $this->setMotion(new Vector3(0.001, 0.05, 0.001));
    }

    protected function tryChangeMovement(): void {
        $this->motion->x *= 1.15;
        $this->motion->y += 0.04;
        $this->motion->z *= 1.15;
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
        if($this->doLifeTimeTick()) {
            $hasUpdate = true;
        }
        return $hasUpdate;
    }

    /**
     * @param int $life
     */
    public function setLifeTime(int $life): void {
        $this->lifeTime = $life;
    }

    /**
     * @return bool
     */
    protected function doLifeTimeTick(): bool {
        if(!$this->isFlaggedForDespawn() and --$this->lifeTime < 0) {
            $this->doExplosionAnimation();
            $this->flagForDespawn();
            return true;
        }
        return false;
    }

    protected function doExplosionAnimation(): void {
        $this->broadcastAnimation(new FireworkParticlesAnimation($this));
    }
}