<?php

namespace core\game\wormhole\entity;

use core\game\item\types\custom\KeyComponentA;
use core\player\task\ExecutiveMineTask;
use pocketmine\block\Flowable;
use pocketmine\block\Liquid;
use pocketmine\block\Slab;
use pocketmine\block\Stair;
use pocketmine\data\bedrock\EntityLegacyIds;
use pocketmine\data\bedrock\LegacyEntityIdToStringIdMap;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\TextFormat;

class ExecutiveEnderman extends Living {

    const NETWORK_ID = EntityLegacyIds::ENDERMAN;
    const HEIGHT = 2.9;

    public $randomPosition = null;
    public $findNewPositionTicks = 200;
    public $jumpTicks = 0;

    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);
        $this->setMaxHealth(10);
        $this->setHealth(10);
        $this->setNametag($this->getNametag());
        $this->setScoreTag($this->getHP());
        $this->setNameTagAlwaysVisible(true);
        $this->generateRandomPosition();
    }

    public function getNameTag(): string
    {
        return TextFormat::RED . "Enderman";
    }

    public function getHP() : string {
        return TextFormat::WHITE . $this->getHealth() . TextFormat::BOLD . TextFormat::RED . " HP";
    }

    protected function onDeath(): void
    {
        $this->startDeathAnimation();
        ExecutiveMineTask::$endermanConter--;
        if(mt_rand(1, 100) <= 15) {
            $this->getWorld()->dropItem($this->getPosition(), (new KeyComponentA())->toItem());
        }
    }

    public function entityBaseTick(int $tickDiff = 1) : bool{
        parent::entityBaseTick($tickDiff);
        if(!$this->isAlive()){
            if(!$this->closed) $this->flagForDespawn();
            return false;
        }
        $this->setNametag($this->getNametag());
        $this->setScoreTag($this->getHP());

        if($this->findNewPositionTicks > 0){
            $this->findNewPositionTicks--;
        }

        if(!$this->isOnGround()){
            if($this->motion->y > -$this->gravity * 4){
                $this->motion->y = -$this->gravity * 4;
            }else{
                $this->motion->y += $this->isUnderwater() ? $this->gravity : -$this->gravity;
            }
        }else{
            $this->motion->y -= $this->gravity;
        }
        $this->move($this->motion->x, $this->motion->y, $this->motion->z);
        if($this->shouldJump()){
            $this->jump();
        }

        if($this->atRandomPosition() || $this->findNewPositionTicks === 0){
            $this->generateRandomPosition();
            $this->findNewPositionTicks = 200;
            return true;
        }

        $position = $this->getRandomPosition();
        $pos = $this->getPosition();
        $x = $position->x - $pos->getX();
        $y = $position->y - $pos->getY();
        $z = $position->z - $pos->getZ();

        if($x * $x + $z * $z < 4 + $this->getScale()) {
            $this->motion->x = 0;
            $this->motion->z = 0;
        } else {
            $this->motion->x = $this->getSpeed() * 0.15 * ($x / (abs($x) + abs($z)));
            $this->motion->z = $this->getSpeed() * 0.15 * ($z / (abs($x) + abs($z)));
        }

        $this->setRotation(rad2deg(atan2(-$x, $z)), 0);

        $this->move($this->motion->x, $this->motion->y, $this->motion->z);
        if($this->shouldJump()){
            $this->jump();
        }

        $this->updateMovement();
        return $this->isAlive();
    }

    public function getSpeed() : float {
        return 1;
    }

    public function canSaveWithChunk(): bool
    {
        return false;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(static::HEIGHT, 1);
    }

    public static function getNetworkTypeId(): string
    {
        return LegacyEntityIdToStringIdMap::getInstance()->legacyToString(static::NETWORK_ID) ?? throw new \LogicException(static::class . ' has invalid Entity ID');
    }

    public function atRandomPosition(){
        return $this->getRandomPosition() == null || $this->getPosition()->distance($this->getRandomPosition()) <= 2;
    }

    public function getRandomPosition(){
        return $this->randomPosition ?? $this->getPosition();
    }

    public function generateRandomPosition(){
        $pos = $this->getPosition();
        $minX = $pos->getFloorX() - 8;
        $minY = $pos->getFloorY() - 8;
        $minZ = $pos->getFloorZ() - 8;

        $maxX = $minX + 16;
        $maxY = $minY + 16;
        $maxZ = $minZ + 16;

        $level = $this->getWorld();

        for($attempts = 0; $attempts < 16; ++$attempts){
            $x = mt_rand($minX, $maxX);
            $y = mt_rand($minY, $maxY);
            $z = mt_rand($minZ, $maxZ);
            while($y >= 0 and !$level->getBlockAt($x, $y, $z)->isSolid()){
                $y--;
            }
            if($y < 0){
                continue;
            }
            $blockUp = $level->getBlockAt($x, $y + 1, $z);
            $blockUp2 = $level->getBlockAt($x, $y + 2, $z);
            if($blockUp->isSolid() or $blockUp instanceof Liquid or $blockUp2->isSolid() or $blockUp2 instanceof Liquid){
                continue;
            }

            break;
        }

        $this->randomPosition = new Vector3($x, $y + 1, $z);
    }

    public function getFrontBlock($y = 0){
        $dv = $this->getDirectionVector();
        $pos = $this->getPosition()->add($dv->x * $this->getScale(), $y + 1, $dv->z * $this->getScale())->round();
        return $this->getWorld()->getBlock($pos);
    }

    public function shouldJump() {
        if($this->jumpTicks > 0) return false;

        return $this->isCollidedHorizontally ||
            ($this->getFrontBlock()->getId() != 0 || $this->getFrontBlock(-1) instanceof Stair) ||
            ($this->getWorld()->getBlock($this->getPosition()->add(0,-0,5)) instanceof Slab &&
                (!$this->getFrontBlock(-0.5) instanceof Slab && $this->getFrontBlock(-0.5)->getId() != 0)) &&
            $this->getFrontBlock(1)->getId() == 0 &&
            $this->getFrontBlock(2)->getId() == 0 &&
            !$this->getFrontBlock() instanceof Flowable &&
            $this->jumpTicks == 0;
    }

    public function jump() : void{
        $this->motion->y = $this->gravity * 16;
        $this->move($this->motion->x * 1.25, $this->motion->y, $this->motion->z * 1.25);
        $this->jumpTicks = 5; //($this->getJumpMultiplier() == 4 ? 2 : 5);
    }

    public function getName(): string
    {
        return "Enderman";
    }
}