<?php
declare(strict_types=1);

namespace core\level\entity\types;

use core\game\item\types\vanilla\Fireworks;
use core\game\item\types\vanilla\Pickaxe;
use core\level\entity\animation\FireworkParticlesAnimation;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\world\particle\FlameParticle;
use pocketmine\world\sound\LaunchSound;

class Powerball extends Entity {

    /** @var int */
    protected $lifeTime = 0;

    /** @var Vector3 */
    protected $directionVector;

    /** @var Pickaxe */
    protected $pickaxe;

    protected function getInitialSizeInfo(): EntitySizeInfo {
        return new EntitySizeInfo(0.25,  0.25);
    }

    /**
     * @return string
     */
    public static function getNetworkTypeId(): string {
        return EntityIds::SMALL_FIREBALL;
    }

    /**
     * FireworksRocket constructor.
     *
     * @param Location $location
     * @param CompoundTag|null $nbt
     */
    public function __construct(Location $location, ?CompoundTag $nbt = null) {
        parent::__construct($location, $nbt);
        $this->setLifeTime(mt_rand(100, 200));
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
            if($this->directionVector !== null) {
                $blocks = [];
                $block = $this->getWorld()->getBlock($this->getPosition());
                foreach(Facing::ALL as $face) {
                    $blocks[] = $block->getSide($face);
                }
                foreach($blocks as $ore) {
                    if($ore->hasEntityCollision()) {
                        $ore->onEntityInside($this);
                    }
                }
                $this->motion->x = $this->directionVector->x * 0.25;
                $this->motion->y = $this->directionVector->y * 0.25;
                $this->motion->z = $this->directionVector->z * 0.25;
                $this->updateMovement();
            }
        }
        return $hasUpdate;
    }

    protected function tryChangeMovement(): void {

    }

    /**
     * @param Vector3 $directionVector
     */
    public function setDirectionVector(Vector3 $directionVector): void {
        $this->directionVector = $directionVector;
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
            $this->flagForDespawn();
            return false;
        }
        $this->getPosition()->getWorld()->addParticle($this->getPosition(), new FlameParticle());
        return true;
    }
}