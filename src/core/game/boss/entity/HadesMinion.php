<?php

namespace core\game\boss\entity;

use pocketmine\block\Flowable;
use pocketmine\block\Liquid;
use pocketmine\block\Slab;
use pocketmine\block\Stair;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\Entity;
use pocketmine\data\bedrock\EntityLegacyIds as EntityIds;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\world\World as Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class HadesMinion extends Living {

    public const NETWORK_ID = "minecraft:wither_skeleton";

    const FIND_DISTANCE = 30;
    const LOSE_DISTANCE = 50;

    public $target = "";
    public $findNewTargetTicks = 0;

    public $randomPosition = null;
    public $findNewPositionTicks = 200;

    public $jumpTicks = 5;
    public $attackWait = 20;

    public $canWhistle = false;

    public $attackDamage = 5;
    public $speed = 1;
    public $startingHealth = 100;

    public $assisting = [];

    public $width = 1;

    public $height = 1;

    public function __construct(Location $level, CompoundTag $nbt){
        parent::__construct($level, $nbt);
        $this->setMaxHealth($this->startingHealth);
        $this->setHealth($this->startingHealth);
        $this->setNametag($this->getNametag() . "\n" . $this->getHP());
        $this->setScoreTag($this->getHP());
        $this->setNameTagAlwaysVisible(true);
        $this->generateRandomPosition();
    }

    public function isBoss(){
        return false;
    }

    public function getType(){
        return "Servant";
    }

    public function getName(): string
    {
        return $this->getType();
    }

    public function getNametag(): string
    {
        return TextFormat::RED . $this->getType();
    }

    public function getHP() : string {
        return TextFormat::WHITE . $this->getHealth() . TextFormat::BOLD . TextFormat::RED . " HP";
    }

    public function entityBaseTick(int $tickDiff = 1) : bool{
        $hasUpdate = parent::entityBaseTick($tickDiff);
        if(!$this->isAlive()){
            if(!$this->closed) $this->flagForDespawn();
            return false;
        }
        $this->setNametag($this->getNametag() . "\n" . $this->getHP());
        $this->setScoreTag($this->getHP());
        if($this->hasTarget()){
            return $this->attackTarget();
        }

        if($this->findNewTargetTicks > 0){
            $this->findNewTargetTicks--;
        }
        if(!$this->hasTarget() && $this->findNewTargetTicks === 0){
            $this->findNewTarget();
        }

        if($this->jumpTicks > 0){
            $this->jumpTicks--;
        }
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

    public function attackTarget(){
        $target = $this->getTarget();
        if($target == null || $target->getPosition()->distance($this->getPosition()) >= self::LOSE_DISTANCE){
            $this->target = null;
            return true;
        }

        if($this->jumpTicks > 0) {
            $this->jumpTicks--;
        }

        if(!$this->isOnGround()) {
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

        $x = $target->getPosition()->x - $this->getPosition()->x;
        $y = $target->getPosition()->y - $this->getPosition()->y;
        $z = $target->getPosition()->z - $this->getPosition()->z;

        if($x * $x + $z * $z < 1.2){
            $this->motion->x = 0;
            $this->motion->z = 0;
        } else {
            $this->motion->x = $this->getSpeed() * 0.15 * ($x / (abs($x) + abs($z)));
            $this->motion->z = $this->getSpeed() * 0.15 * ($z / (abs($x) + abs($z)));
        }
        $this->yaw = rad2deg(atan2(-$x, $z));
        $this->pitch = rad2deg(-atan2($y, sqrt($x * $x + $z * $z)));

        $this->move($this->motion->x, $this->motion->y, $this->motion->z);
        if($this->shouldJump()){
            $this->jump();
        }

        if($this->getPosition()->distance($target->getPosition()) <= $this->getScale() + 0.3 && $this->attackWait <= 0){
            $event = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getBaseAttackDamage());
            $this->broadcastAnimation(new ArmSwingAnimation($this));
            $target->attack($event);
            if (!$target->isAlive()) {
                $this->target = null;
                $this->findNewTarget();
            }
            $this->attackWait = 20;
        }

        $this->updateMovement();
        $this->attackWait--;
        return $this->isAlive();
    }

    public function attack(EntityDamageEvent $source) : void{
        if($source instanceof EntityDamageByEntityEvent){
            $killer = $source->getDamager();
            if($killer instanceof Player){
                if ($killer->getGamemode() === GameMode::CREATIVE) {
                    $source->cancel();
                    return;
                }
                if($this->target != $killer->getName() && mt_rand(1,5) == 1 || $this->target == ""){
                    $this->target = $killer->getName();
                }
                if(!isset($this->assisting[$killer->getName()])){
                    $this->assisting[$killer->getName()] = true;
                }

                if($this->getHealth() <= $this->getMaxHealth() / 2 && mt_rand(0,2) == 1 && $this->canWhistle){
                    $this->whistle();
                    $this->canWhistle = false;
                }
            }
        }

        parent::attack($source);
    }

    public function knockBack(float $x, float $z, float $base = 0.4, ?float $verticalLimit = null) : void{
        if($this->getWorld() !== null) {
            parent::knockBack($x, $z, $base * 2, null);
        }
    }

    public function whistle(){
        foreach($this->getWorld()->getNearbyEntities($this->getBoundingBox()->expandedCopy(15, 15, 15)) as $entity){
            if($entity instanceof $this && !$entity->hasTarget() && $entity->canWhistle){
                $entity->target = $this->target;
                $entity->canWhistle = false;
            }
        }
    }

    public function kill() : void{
        parent::kill();
    }

    //Targetting//
    public function findNewTarget(){
        $distance = self::FIND_DISTANCE;
        $target = null;
        foreach($this->getWorld()->getPlayers() as $player){
            if($player->getPosition()->distance($this->getPosition()) <= $distance && $player->getGamemode() !== GameMode::CREATIVE){
                $distance = $player->getPosition()->distance($this->getPosition());
                $target = $player;
            }
        }
        $this->findNewTargetTicks = 60;
        $this->target = ($target != null ? $target->getName() : "");
    }

    public function hasTarget(){
        $target = $this->getTarget();
        if($target == null) return false;

        $player = $this->getTarget();
        return $player->getGamemode() !== GameMode::CREATIVE;
    }

    public function getTarget(){
        return Server::getInstance()->getPlayerExact((string) $this->target);
    }

    public function atRandomPosition(){
        return $this->getRandomPosition() == null || $this->getPosition()->distance($this->getRandomPosition()) <= 2;
    }

    public function getRandomPosition(){
        return $this->randomPosition;
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

    public function getSpeed(){
        return ($this->isUnderwater() ? $this->speed / 2 : $this->speed);
    }

    public function getBaseAttackDamage(){
        return $this->attackDamage;
    }

    public function getAssisting(){
        $assisting = [];
        foreach($this->assisting as $name => $bool){
            $player = Server::getInstance()->getPlayerExact($name);
            if($player instanceof Player) $assisting[] = $player;
        }
        return $assisting;
    }

    public function getFrontBlock($y = 0){
        $dv = $this->getDirectionVector();
        $pos = $this->getPosition()->add($dv->x * $this->getScale(), $y + 1, $dv->z * $this->getScale())->round();
        return $this->getWorld()->getBlock($pos);
    }


    public function shouldJump(){
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

    public function getJumpMultiplier(){
        return 16;
        if(
            $this->getFrontBlock() instanceof Slab ||
            $this->getFrontBlock() instanceof Stair ||

            $this->getWorld()->getBlock($this->getPosition()->subtract(0,0.5, 0)->round()) instanceof Slab &&
            $this->getFrontBlock()->getId() != 0
        ){
            $fb = $this->getFrontBlock();
            if($fb instanceof Slab && $fb->getDamage() & 0x08 > 0) return 8;
            if($fb instanceof Stair && $fb->getDamage() & 0x04 > 0) return 8;
            return 4;
        }
        return 8;
    }

    public function jump() : void{
        $this->motion->y = $this->gravity * $this->getJumpMultiplier();
        $this->move($this->motion->x * 1.25, $this->motion->y, $this->motion->z * 1.25);
        $this->jumpTicks = 5; //($this->getJumpMultiplier() == 4 ? 2 : 5);
    }

    public function canSaveWithChunk(): bool
    {
        return false;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(2.412, 0.864);
    }

    public static function getNetworkTypeId(): string
    {
        return self::NETWORK_ID;
    }
}