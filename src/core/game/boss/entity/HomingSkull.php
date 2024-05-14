<?php

declare(strict_types=1);

namespace core\game\boss\entity;

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\world\Explosion;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\GameMode;
use pocketmine\player\Player;
use pocketmine\world\Position;

class HomingSkull extends Projectile
{

    public $isFire = true;

    public function __construct(Location $level, CompoundTag $nbt, ?Entity $shootingEntity = null, bool $critical = false, int $enchantmentLevel = 1)
    {
        parent::__construct($level, $shootingEntity, $nbt);
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        if (!$this->closed && !$this->isFlaggedForDespawn() && $this->blockHit === null) {
            $target = $this->findNearestEntity(6 * 10);
            if ($target !== null) {
                $this->setMotion($target->getPosition()->add(0, $target->getSize()->getHeight() / 2, 0)->subtractVector($this->getPosition())->normalize()->multiply(1.5));
                $this->lookAt($target->getPosition()->add(0, $target->getSize()->getHeight() / 2, 0));
            }
        }
        return parent::entityBaseTick($tickDiff);
    }

    public function findNearestEntity(int $range): ?Living
    {
        $nearestEntity = null;
        $nearestEntityDistance = $range;
        foreach ($this->getWorld()->getPlayers() as $entity) {
            $distance = $this->getPosition()->distance($entity->getPosition());
            if ($distance <= $range && $distance <= $nearestEntityDistance && $entity->isAlive() && !$entity->isClosed() && !$entity->isFlaggedForDespawn()) {
                $canAttack = true;
//                if($entity instanceof Player && $owner instanceof Player) {
//                    if(Loader::getInstance()->getFactionFor($owner) !== null){
//                        $canAttack = !(Loader::getInstance()->getFactionFor($owner)->isAlly(Loader::getInstance()->getFactionFor($entity)) || Loader::getInstance()->getFactionFor($owner)->isMember(Loader::getInstance()->getMember($entity)));
//                    }
//                }
                if($entity instanceof Player && $entity->isCreative()){
                    $canAttack = false;
                }

                if ($canAttack) {
                    $nearestEntity = $entity;
                    $nearestEntityDistance = $distance;
                }
            }
        }
        return $nearestEntity;
    }

    public function lookAt(Vector3 $target): void
    {
        $pos = $this->getPosition();
        $horizontal = sqrt(($target->x - $pos->x) ** 2 + ($target->z - $pos->z) ** 2);
        $vertical = $target->y - $pos->y;

        $xDist = $target->x - $pos->x;
        $zDist = $target->z - $pos->z;
        $this->setRotation(atan2($zDist, $xDist) / M_PI * 180 - 90, -atan2($vertical, $horizontal) / M_PI * 180);
        if ($this->getLocation()->getYaw() < 0) {
            $this->setRotation(360 + $this->getLocation()->getYaw(), $this->getLocation()->getPitch());
        }
    }

    const NETWORK_ID = EntityIds::WITHER_SKULL_DANGEROUS;

    /** @var float */
    public $width = 0.5;
    /** @var float */
    public $length = 0.5;
    /** @var float */
    public $height = 0.5;

    /** @var float */
    protected $drag = 0.01;
    /** @var float */
    protected $gravity = 0.05;

    /** @var int */
    protected $damage = 5;

    public function onHitEntity(Entity $entityHit, RayTraceResult $hitResult): void
    {
        $expl = new HadesExplosion($entityHit->getPosition(), 2, $this->getOwningEntity());
        $expl->explodeB();
        if($entityHit instanceof Living) { // TODO: NOT NPC CHECK
            if (!$entityHit->isClosed() && !$entityHit->isFlaggedForDespawn()) {
                $entityHit->knockBack($entityHit->getPosition()->x - $this->getOwningEntity()->getPosition()->x, $entityHit->getPosition()->z - $this->getOwningEntity()->getPosition()->z, 0.8);

                if ($this->isFire){
                    $entityHit->setOnFire(3);
                }
            }
        }
        /*foreach($entityHit->getLevel()->getNearbyEntities($hitResult->getBoundingBox()->expand(2, 2, 2)) as $ent) {
            if ($ent instanceof Living && !in_array(SlapperTrait::class, array_keys((new \ReflectionClass($ent))->getTraits()))) {
                $ent->knockBack($this->getOwningEntity() ?? $ent, 0, 2, 2, 2);
            }
        }*/
        parent::onHitEntity($entityHit, $hitResult);
    }

    public function onHitBlock(Block $blockHit, RayTraceResult $hitResult): void
    {
        $expl = new HadesExplosion($blockHit->getPosition(), 2, $this->getOwningEntity());
        $expl->explodeB();
        foreach ($expl->getAffectedEntities() as $entity) {
            if ($entity instanceof Living && $entity->isAlive()) {
                $entity->knockBack($entity->getPosition()->x - $this->getPosition()->x, $entity->getPosition()->z - $this->getPosition()->z, 0.8);
            }
        }
        /*foreach($blockHit->getLevel()->getNearbyEntities($hitResult->getBoundingBox()->expand(2, 2, 2)) as $ent) {
            if ($ent instanceof Living && !in_array(SlapperTrait::class, array_keys((new \ReflectionClass($ent))->getTraits()))) {
                $ent->knockBack($this->getOwningEntity() ?? $ent, 0, 2, 2, 2);
            }
        }*/
        parent::onHitBlock($blockHit, $hitResult);
    }

    protected function onHit(ProjectileHitEvent $event): void
    {
        $this->flagForDespawn();
        parent::onHit($event);
    }

    public function canSaveWithChunk(): bool
    {
        return false;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.5, 0.5);
    }

    public static function getNetworkTypeId(): string
    {
        return self::NETWORK_ID;
    }

    public function canCollideWith(Entity $entity): bool
    {
        return parent::canCollideWith($entity) && !$entity instanceof Hades;
    }
}