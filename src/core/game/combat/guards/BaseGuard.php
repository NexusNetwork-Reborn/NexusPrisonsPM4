<?php

namespace core\game\combat\guards;

use core\player\NexusPlayer;
use core\translation\Translation;
use pocketmine\block\Block;
use pocketmine\block\Flowable;
use pocketmine\block\Slab;
use pocketmine\block\Stair;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

abstract class BaseGuard extends Human implements ArtificialIntelligence {

    const ATTACK_DISTANCE = 5;

    const FIND_DISTANCE = 10;

    const LOSE_DISTANCE = 40;

    /** @var int */
    public $attackDamage;

    /** @var float */
    public $speed;

    /** @var int */
    public $attackWait;

    /** @var int */
    public $defAttackWait = 20;

    /** @var int */
    public $regenerationWait = 0;

    /** @var int */
    public $regenerationRate;

    /** @var NexusPlayer|null */
    private $target = null;

    /** @var int */
    private $jumpTicks = 5;

    /** @var int */
    private $lastDeath = 0;

    /**
     * @param int $tickDiff
     *
     * @return bool
     */
    protected function entityBaseTick(int $tickDiff = 1): bool {
        parent::entityBaseTick($tickDiff);
        if(!$this->isAlive()) {
            if(!$this->closed) {
                $this->flagForDespawn();
            }
            return false;
        }
        if($this->regenerationWait-- <= 0) {
            $this->setHealth($this->getHealth() + $this->regenerationRate);
            $this->regenerationWait = 20;
        }
        if($this->hasTarget()) {
            return $this->attackTarget();
        }
        else {
            $this->flagForDespawn();
            return false;
        }
    }

    /**
     * @return bool
     */
    public function attackTarget(): bool {
        $target = $this->getTarget();
        if($target == null or $target->getPosition()->distance($this->getPosition()) >= self::LOSE_DISTANCE) {
            $this->target = null;
            return true;
        }
        if($this->jumpTicks > 0) {
            $this->jumpTicks--;
        }
        if(!$this->isOnGround()) {
            if($this->motion->y > -$this->gravity * 4) {
                $this->motion->y = -$this->gravity * 4;
            }
            else {
                $this->motion->y += $this->isUnderwater() ? $this->gravity : -$this->gravity;
            }
        }
        else {
            $this->motion->y -= $this->gravity;
        }
        $this->move($this->motion->x, $this->motion->y, $this->motion->z);
        if($this->shouldJump()) {
            $this->jump();
        }
        $x = $target->getPosition()->x - $this->getPosition()->x;
        $y = $target->getPosition()->y - $this->getPosition()->y;
        $z = $target->getPosition()->z - $this->getPosition()->z;
        if($x * $x + $z * $z < 1.2) {
            $this->motion->x = 0;
            $this->motion->z = 0;
        }
        else {
            $this->motion->x = $this->getSpeed() * 0.15 * ($x / (abs($x) + abs($z)));
            $this->motion->z = $this->getSpeed() * 0.15 * ($z / (abs($x) + abs($z)));
        }
        $this->location->yaw = rad2deg(atan2(-$x, $z));
        $this->location->pitch = rad2deg(-atan2($y, sqrt($x * $x + $z * $z)));
        $this->move($this->motion->x, $this->motion->y, $this->motion->z);
        if($this->shouldJump()) {
            $this->jump();
        }
        if($this->getPosition()->distance($target->getPosition()) <= self::ATTACK_DISTANCE and $this->attackWait <= 0 and (!$target->isClosed())) {
            $damage = $this->attackDamage;
            $multiplier = 1 + ($target->getDataSession()->getGuardsKilled() * 0.5);
            $damage *= $multiplier;
            $ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_CUSTOM, $damage, []);
            $ev->call();
            if(!$ev->isCancelled()) {
                $target->attack($ev);
            }
            if($target->isTagged()) {
                $target->combatTag();
            }
            else {
                $target->combatTag();
                $target->sendMessage(Translation::getMessage("combatTag"));
            }
            if($target->isLoaded()) {
                if($target->getCESession()->getFrenzyHits() > 0) {
                    $target->getCESession()->resetFrenzyHits();
                }
            }
            $this->broadcastAnimation(new ArmSwingAnimation($this));
            $this->attackWait = $this->defAttackWait;
        }
        $this->updateMovement();
        $this->attackWait--;
        return $this->isAlive();
    }

    /**
     * @param EntityDamageEvent $source
     */
    public function attack(EntityDamageEvent $source): void {
        if($source->getCause() === EntityDamageEvent::CAUSE_FALL) {
            $source->cancel();
        }
        if($source instanceof EntityDamageByEntityEvent) {
            $killer = $source->getDamager();
            if($killer instanceof NexusPlayer) {
                if($killer->isFlying() or $killer->getAllowFlight() == true) {
                    $killer->setFlying(false);
                    $killer->setAllowFlight(false);
                }
                if($this->target === null or $this->target->getName() != $killer->getName()) {
                    $source->cancel();
                    $killer->playErrorSound();
                    $killer->sendMessage(TextFormat::DARK_GRAY . "[" . $this->getNameTag() . TextFormat::RESET . TextFormat::DARK_GRAY . "] " . TextFormat::RED . "I don't think you want to do that!");
                }
                $source->setKnockBack(0.0);
            }
        }
        parent::attack($source);
    }

    protected function onDeath(): void {
        if(time() - $this->lastDeath <= 3) {
            return;
        }
        $this->lastDeath = time();
        $cause = $this->getLastDamageCause();
        if($cause instanceof EntityDamageByEntityEvent) {
            $killer = $cause->getDamager();
            if($killer instanceof NexusPlayer) {
                $killer->getDataSession()->addGuardsKilled();
                $multiplier = 1 + ($killer->getDataSession()->getGuardsKilled() * 0.5);
                $killer->sendMessage(TextFormat::RED . "Guards will deal " . $multiplier . "x damage due to guard frenzy.");
            }
        }
        $this->startDeathAnimation();
    }

    /**
     * @return array
     */
    public function getDrops(): array {
        return [];
    }

    /**
     * @return bool
     */
    public function hasTarget(): bool {
        $target = $this->getTarget();
        if($target == null) {
            return false;
        }
        return true;
    }

    /**
     * @param Player|null $target
     */
    public function setTarget(?Player $target): void {
        $this->target = $target;
    }

    /**
     * @return NexusPlayer|null
     */
    public function getTarget(): ?NexusPlayer {
        return $this->target;
    }

    /**
     * @return float
     */
    public function getSpeed(): float {
        return ($this->isUnderwater() ? $this->speed / 2 : $this->speed);
    }

    /**
     * @return int
     */
    public function getBaseAttackDamage(): int {
        return $this->attackDamage;
    }

    /**
     * @param int $y
     *
     * @return Block
     */
    public function getFrontBlock($y = 0): Block {
        $dv = $this->getDirectionVector();
        $pos = $this->getPosition()->add($dv->x, $y + 1, $dv->z)->floor();
        return $this->getWorld()->getBlock($pos);
    }

    /**
     * @return bool
     */
    public function shouldJump(): bool {
        if($this->jumpTicks > 0) {
            return false;
        }
        return $this->isCollidedHorizontally or
            ($this->getFrontBlock()->getId() != 0 or $this->getFrontBlock(-1) instanceof Stair) or
            ($this->getWorld()->getBlock($this->getPosition()->add(0, -0, 5)) instanceof Slab and
                (!$this->getFrontBlock(-0.5) instanceof Slab and $this->getFrontBlock(-0.5)->getId() != 0)) and
            $this->getFrontBlock(1)->getId() == 0 and
            $this->getFrontBlock(2)->getId() == 0 and
            !$this->getFrontBlock() instanceof Flowable and
            $this->jumpTicks == 0;
    }

    /**
     * @return int
     */
    public function getJumpMultiplier(): int {
        if($this->getFrontBlock() instanceof Slab or $this->getFrontBlock() instanceof Stair or
            $this->getWorld()->getBlock($this->getPosition()->subtract(0, 0.5, 0)->round()) instanceof Slab and
            $this->getFrontBlock()->getId() != 0) {
            $fb = $this->getFrontBlock();
            if($fb instanceof Slab and $fb->getMeta() & 0x08 > 0) {
                return 8;
            }
            if($fb instanceof Stair and $fb->getMeta() & 0x04 > 0) {
                return 8;
            }
            return 4;
        }
        return 16;
    }

    public function jump(): void {
        $multiplier = $this->getJumpMultiplier();
        for($i = 1; $i <= 20; $i++) {
            $block = $this->getFrontBlock($i);
            if($block->getId() === 0) {
                break;
            }
            $multiplier += 16;
        }
        $this->motion->y = $this->gravity * $multiplier;
        $this->move($this->motion->x * 1.25, $this->motion->y, $this->motion->z * 1.25);
        $this->jumpTicks = 5;
    }
}