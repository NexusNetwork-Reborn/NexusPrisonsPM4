<?php

namespace core\game\item\trinket\entity;

use core\game\item\types\custom\Trinket;
use core\player\NexusPlayer;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Projectile;
use pocketmine\item\ItemIds;
use pocketmine\math\RayTraceResult;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\utils\Random;

class GrapplingHook extends Projectile {

    /** @var float */
    public $height = 0.25;

    /** @var float */
    public $width = 0.25;

    /** @var float */
    protected $gravity = 0.1;

    /**
     * GrapplingHook constructor.
     *
     * @param Location $location
     * @param Entity|null $shootingEntity
     * @param CompoundTag|null $nbt
     */
    public function __construct(Location $location, ?Entity $shootingEntity, ?CompoundTag $nbt = null) {
        parent::__construct($location, $shootingEntity, $nbt);
        if($shootingEntity instanceof NexusPlayer) {
            $this->setOwningEntity($shootingEntity);
            $this->setPosition($this->getPosition()->add(0, $shootingEntity->getEyeHeight() - 0.1, 0));
            $this->setMotion($shootingEntity->getDirectionVector()->multiply(0.4));
            $shootingEntity->setGrapplingHook($this);
            $this->handleHookCasting($this->motion->x, $this->motion->y, $this->motion->z, 1.5, 1.0);
        }
    }

    /**
     * @return EntitySizeInfo
     */
    protected function getInitialSizeInfo(): EntitySizeInfo {
        return new EntitySizeInfo(0.25, 0.25);
    }

    /**
     * @return string
     */
    public static function getNetworkTypeId(): string {
        return EntityIds::FISHING_HOOK;
    }

    /**
     * @param Entity $entityHit
     * @param RayTraceResult $hitResult
     */
    public function onHitEntity(Entity $entityHit, RayTraceResult $hitResult): void {

    }

    /**
     * @param float $x
     * @param float $y
     * @param float $z
     * @param float $f1
     * @param float $f2
     */
    public function handleHookCasting(float $x, float $y, float $z, float $f1, float $f2) {
        $rand = new Random();
        $f = sqrt($x * $x + $y * $y + $z * $z);
        $x = $x / (float)$f;
        $y = $y / (float)$f;
        $z = $z / (float)$f;
        $x = $x + $rand->nextSignedFloat() * 0.007499999832361937 * (float)$f2;
        $y = $y + $rand->nextSignedFloat() * 0.007499999832361937 * (float)$f2;
        $z = $z + $rand->nextSignedFloat() * 0.007499999832361937 * (float)$f2;
        $x = $x * (float)$f1;
        $y = $y * (float)$f1;
        $z = $z * (float)$f1;
        $this->motion->x += $x;
        $this->motion->y += $y;
        $this->motion->z += $z;
    }

    /**
     * @param int $tickDiff
     *
     * @return bool
     */
    public function entityBaseTick(int $tickDiff = 1): bool {
        $hasUpdate = parent::entityBaseTick($tickDiff);
        $owner = $this->getOwningEntity();
        if($owner instanceof NexusPlayer) {
            $item = $owner->getInventory()->getItemInHand();
            if($item->getId() !== ItemIds::FISHING_ROD or (!Trinket::isInstanceOf($item)) or !$owner->isAlive() or $owner->isClosed()) {
                $this->flagForDespawn();
            }
        }
        else {
            $this->flagForDespawn();
        }
        return $hasUpdate;
    }


    public function flagForDespawn(): void {
        if (!$this->isFlaggedForDespawn() && !$this->isClosed()) {
            parent::flagForDespawn();
            $owner = $this->getOwningEntity();
            if ($owner instanceof NexusPlayer) {
                $owner->setGrapplingHook(null);
            }
        }
    }

    public function handleHookRetraction(): void {
        $owner = $this->getOwningEntity();
        $x = $owner->getPosition()->getX();
        $y = $owner->getPosition()->getY();
        $z = $owner->getPosition()->getZ();
        $owner->setMotion($this->getPosition()->subtract($x, $y, $z)->multiply(0.2));
        $this->flagForDespawn();
    }
}