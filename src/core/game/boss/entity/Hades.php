<?php

namespace core\game\boss\entity;

use core\level\FakeChunkLoader;
use core\Nexus;
use core\player\NexusPlayer;

//use jacknoordhuis\combatlogger\CombatLogger;
use core\game\boss\task\BossSummonTask;
use JetBrains\PhpStorm\Pure;
use pocketmine\block\Flowable;
use pocketmine\block\Liquid;
use pocketmine\block\Slab;
use pocketmine\block\Stair;
use pocketmine\entity\Attribute;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\scheduler\CancelTaskException;
use pocketmine\world\Position;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\math\VoxelRayTrace;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\AnimateEntityPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\types\GameMode;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use xenialdan\apibossbar\BossBar;

class Hades extends Living
{

    public const NETWORK_ID = 150;

    public $width = 1;

    public $height = 1;

    public static function getNetworkTypeId(): string
    {
        return "nexus:hades";
    }

    #[Pure] protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(1.0, 1.0, 1.0);
    }

    /**
     * Called by spawnTo() to send whatever packets needed to spawn the entity to the client.
     */
    protected function sendSpawnPacket(Player $player): void
    {
        $pk = new AddActorPacket();
        $pk->actorRuntimeId = $this->getId();
        $pk->actorUniqueId = $pk->actorRuntimeId;
        $pk->type = "nexus:hades";
        $pk->position = $this->getPosition()->asVector3();
        $pk->motion = $this->getMotion();
        $pk->yaw = $this->getLocation()->getYaw();
        $pk->headYaw = $this->getLocation()->getYaw(); //TODO
        $pk->pitch = $this->getLocation()->getPitch();
        $pk->attributes = array_map(function (Attribute $attr): \pocketmine\network\mcpe\protocol\types\entity\Attribute {
            return new \pocketmine\network\mcpe\protocol\types\entity\Attribute($attr->getId(), $attr->getMinValue(), $attr->getMaxValue(), $attr->getValue(), $attr->getDefaultValue(), []);
        }, $this->attributeMap->getAll());
        $pk->metadata = $this->getAllNetworkData();
        $pk->syncedProperties = new PropertySyncData([], []);

        $player->getNetworkSession()->sendDataPacket($pk);
        //$player->dataPacket($pk);
    }

    public function canSaveWithChunk(): bool
    {
        return false;
    }

    public function playAnimation(string $name): void
    {
        $pk = AnimateEntityPacket::create($name, "", "", 0, "", 0, [$this->getId()]);
        Server::getInstance()->broadcastPackets(Server::getInstance()->getOnlinePlayers(), [$pk]);
    }

    public function playSound(string $name): void
    {
        $pk = new PlaySoundPacket();
        $pos = $this->getPosition();
        $pk->soundName = $name;
        $pk->x = $pos->x;
        $pk->y = $pos->y;
        $pk->z = $pos->z;
        $pk->volume = 6;
        $pk->pitch = 1.2;
        Server::getInstance()->broadcastPackets(Server::getInstance()->getOnlinePlayers(), [$pk]);

    }

    const FIND_DISTANCE = 30;
    const LOSE_DISTANCE = 50;

    public $target = "";
    public ?Living $entityTarget = null;
    public $findNewTargetTicks = 0;

    public $randomPosition = null;
    public $findNewPositionTicks = 200;

    public $jumpTicks = 5;
    public $attackWait = 20;

    public $canWhistle = true;

    public $attackDamage = 4;
    public $speed = 0.35;
    public $startingHealth = 1000;

    public $assisting = [];

    /** @var BossBar */
    public $bossBar;

    /** @var ClosureTask */
    public $bossBarTask;

    /**
     * @throws \pocketmine\scheduler\CancelTaskException
     */
    public function __construct(Location $location, CompoundTag $nbt)
    {
        parent::__construct($location, $nbt);
        $this->setMaxHealth($this->startingHealth);
        $this->setHealth($this->startingHealth);
        $this->setNametag($this->getNametag() . "\n" . $this->getHP());
        $this->setScoreTag($this->getHP());
        $this->setNameTagAlwaysVisible(true);
        $this->generateRandomPosition();
        $this->setScale(3);
        $this->bossBar = new BossBar();
        $this->bossBar->setTitle(TextFormat::RED . TextFormat::BOLD . "         Hades");
        $this->bossBar->setSubTitle(TextFormat::RED . "Keeper Of The Underworld");
        $this->bossBar->setPercentage($this->getHealth() / $this->getMaxHealth());
        $this->bossBarTask = new ClosureTask(function (): void {
            if ($this->isAlive() && $this->getWorld() !== null) {
                $add = [];
                $keep = [];
                $pos = $this->getPosition();
                foreach ($this->getWorld()->getNearbyEntities(new AxisAlignedBB(
                    $pos->getX() - 36, $pos->getY() - 36, $pos->getZ() - 36,
                    $pos->getX() + 36, $pos->getY() + 36, $pos->getZ() + 36
                ), $this) as $ent) {
                    if ($ent instanceof Player) {
                        if (!$this->bossBar->playerExists($ent)) {
                            $add[] = $ent;
                        } else {
                            $keep[$ent->getId()] = $ent;
                        }
                    }
                }
                foreach ($this->bossBar->getPlayers() as $id => $player) {
                    if (!isset($keep[$id])) {
                        $this->bossBar->removePlayer($player);
                    }
                }
                $this->bossBar->addPlayers($add);
                $this->bossBar->showToAll();
            } else {
                $this->bossBar->hideFromAll();
                $this->bossBarTask->getHandler()->cancel();
                //Nexus::getInstance()->getScheduler()->cancelTask($this->bossBarTask->getTaskId());
            }
        });
//        try {
//            $this->bossBarTask->onRun();
//        } catch (CancelTaskException $e) {
//        }
//        Nexus::getInstance()->getScheduler()->scheduleRepeatingTask(
//            $this->bossBarTask, 100
//        );
        $this->getEffects()->add(new EffectInstance(VanillaEffects::REGENERATION(), 107374182, 1, false));
    }

    public function isBoss()
    {
        return true;
    }

    public function getType()
    {
        return "Hades";
    }

    public function getNametag(): string
    {
        return TextFormat::RED . TextFormat::BOLD . $this->getType();
    }

    public function getHP() : string {
        return TextFormat::WHITE . $this->getHealth() . TextFormat::BOLD . TextFormat::RED . " HP";
    }

    private $throwTicks = 0;

    private $fireballTicks = 0;

    public $tauntTicks = 0;

    private $sweepTicks = 0;

    private $chunkLoader = null;

    private function testChunkLoader(): void
    {
        $x = $this->getPosition()->getFloorX() >> 4;
        $z = $this->getPosition()->getFloorZ() >> 4;
        if (is_null($this->chunkLoader)) {
            $this->chunkLoader = new FakeChunkLoader($x, $z);
            $this->getWorld()->registerChunkLoader($this->chunkLoader, $x, $z);
        } else if ($this->chunkLoader->getX() !== $x || $this->chunkLoader->getZ() !== $z) {
            $this->getWorld()->unregisterChunkLoader($this->chunkLoader, $this->chunkLoader->getX(), $this->chunkLoader->getZ());
            $this->chunkLoader = new FakeChunkLoader($x, $z);
            $this->getWorld()->registerChunkLoader($this->chunkLoader, $x, $z);
        }
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        parent::entityBaseTick($tickDiff);
        if (!$this->isAlive()) {
            if (!$this->closed) $this->flagForDespawn();
            return false;
        }
        $this->testChunkLoader();
        $this->bossBar->setPercentage(round($this->getHealth()) / $this->getMaxHealth());
        $this->setNametag($this->getNametag() . "\n" . $this->getHP());
        $this->setScoreTag($this->getHP());

        if ($this->fireballTicks > 0) {
            $this->fireballTicks--;
        }

        if ($this->tauntTicks > 0 || $this->sweepTicks > 0 || $this->whistleTicks > 0) {
            if ($this->tauntTicks > 0) $this->tauntTicks--;
            if ($this->sweepTicks > 0) $this->sweepTicks--;
            if ($this->whistleTicks > 0) $this->whistleTicks--;
            return $this->isAlive();
        } elseif ($this->hasTarget() && $this->throwTicks === 0) {
            //$dist = sqrt((($this->getTarget()->x - $this->x) ** 2) + (($this->getTarget()->z - $this->z) ** 2));
            $dist = $this->getPosition()->distance($this->getTarget()->getPosition());
            if ($dist < 14 || $dist > 17) {
                //$distCheck = $dist > 17 || $dist < 20;
                if ($dist > 20 && $this->fireballTicks == 0) {
                    if (mt_rand(1, 4) == 1) {
                        $this->tauntTicks = 94;
                        $this->playSound("mob.nexus_hades.say");
                        $this->playAnimation("animation.nexus_hades.taunt");
                        return $this->isAlive();
                    } else {
                        $task = new ClosureTask(
                            function (): void {
                                if ($this->isAlive()) {
                                    $pos = $this->getPosition()->add(0, 5, 0);// TODO: Fix
                                    $location = new Location($pos->x, $pos->y, $pos->z, $this->getWorld(), ($this->getLocation()->getYaw() > 180 ? 360 : 0) - $this->getLocation()->getYaw(), -$this->getLocation()->getPitch());
//                                    $nbt = Entity::createBaseNBT(
//                                        $this->getPosition()->add(0, 2, 0),
//                                        $this->getDirectionVector(),
//                                        ($this->yaw > 180 ? 360 : 0) - $this->yaw,
//                                        -$this->pitch
//                                    );
//                                  $fireball = Entity::createEntity("homing_skull", $this->getLevel(), $nbt);
                                    $fireball = new HomingSkull($location, new CompoundTag()); // TODO: Insert getDirectionVector
                                    $fireball->setOwningEntity($this);
                                    $fireball->isFire = false;
                                    $fireball->spawnToAll();
                                }
                            }
                        );
                        $task2 = clone $task;
                        $task3 = clone $task;
                        $this->playAnimation("animation.nexus_hades.slash2");
                        Nexus::getInstance()->getScheduler()->scheduleDelayedTask($task, 10);
                        Nexus::getInstance()->getScheduler()->scheduleDelayedTask($task2, 20);
                        Nexus::getInstance()->getScheduler()->scheduleDelayedTask($task3, 30);
                        $this->fireballTicks = 401;

                        return $this->isAlive();
                    }
                } else {
                    if ($this->throwTicks > 30 || $this->throwTicks == 0) {
                        if ($this->throwTicks > 0) {
                            $this->throwTicks = 0;
                            $this->playAnimation("animation.player.bob.stationary");
                        }
                        return $this->attackTarget();
                    }
                }
            } else {
                $this->throwTicks = 73;
                $this->playAnimation("animation.nexus_hades.throw");
                $pos = $this->getPosition();
                $dirVec = $this->getDirectionVector();
                $dirVec->y = 0;
                Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(
                    function () use ($pos, $dirVec): void {
                        if ($this->isAlive() && $this->throwTicks > 0) {
                            foreach (VoxelRayTrace::inDirection($pos, $dirVec, 15) as $voxel) {
                            }
                            //$goalX = 15*(sin($yaw));
                            //$goalZ = 15*(cos($yaw));
                            //$expl = new Explosion(Position::fromObject($pos->add($goalX, 0, $goalZ))->setLevel($pos->getLevel()), 3, $this);
                            $expl = new HadesExplosion(Position::fromObject($voxel, $this->getWorld()), 3, $this);
                            if($this->isAlive() && !$this->isFlaggedForDespawn() && !$this->isClosed()) {
                                $expl->explodeB();
                            }
                            Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(
                                function () use ($expl): void {
                                    if ($this->isAlive() && $this->throwTicks > 0) {
                                        foreach ($expl->getAffectedEntities() as $entity) {
                                            if ($entity instanceof Living && $entity->isAlive()) {
                                                /* if ($entity instanceof Player) {
                                                     if (!CombatLogger::getInstance()->isTagged($entity)) {
                                                         $entity->sendMessage(CombatLogger::getInstance()->getMessageManager()->getMessage("player-tagged"));
                                                     }
                                                     CombatLogger::getInstance()->setTagged($entity, true, CombatLogger::getInstance()->getListener()->taggedTime);
                                                 }*/
                                                $entPos = $entity->getPosition();
                                                $pos = $this->getPosition();
                                                $entity->knockBack($entPos->x - $pos->x, $entPos->z - $pos->z, -5);
                                            }
                                        }
                                    }
                                }
                            ), 12);
                        }
                    }
                ), 49
                );
            }
        }

        if ($this->throwTicks > 0) {
            if ($this->throwTicks >= 30 && $this->hasTarget()) {
                $target = $this->getTarget();
                $targetPos = $target->getPosition();
                $pos = $this->getPosition();
                $x = $targetPos->x - $pos->x;
                $y = $targetPos->y - $pos->y;
                $z = $targetPos->z - $pos->z;

                $this->setRotation(rad2deg(atan2(-$x, $z)), rad2deg(-atan2($y, sqrt($x * $x + $z * $z))));
                //$this->yaw = rad2deg(atan2(-$x, $z));
                //$this->pitch = rad2deg(-atan2($y, sqrt($x * $x + $z * $z)));

                $this->updateMovement();
            }
            $this->throwTicks--;
            return $this->isAlive();
        }

        if ($this->findNewTargetTicks > 0) {
            $this->findNewTargetTicks--;
        }
        if (!$this->hasTarget() && $this->findNewTargetTicks === 0) {
            $this->findNewTarget();
        }

        if ($this->jumpTicks > 0) {
            $this->jumpTicks--;
        }
        if ($this->findNewPositionTicks > 0) {
            $this->findNewPositionTicks--;
        }

        if (!$this->isOnGround()) {
            if ($this->motion->y > -$this->gravity * 4) {
                $this->motion->y = -$this->gravity * 4;
            } else {
                $this->motion->y += $this->isUnderwater() ? $this->gravity : -$this->gravity;
            }
        } else {
            $this->motion->y -= $this->gravity;
        }
        $this->move($this->motion->x, $this->motion->y, $this->motion->z);
        if ($this->shouldJump()) {
            $this->jump();
        }

        if ($this->atRandomPosition() || $this->findNewPositionTicks === 0) {
            $this->generateRandomPosition();
            $this->findNewPositionTicks = 200;
            return true;
        }

        $position = $this->getRandomPosition();
        $x = $position->x - $this->getPosition()->getX();
        //$y = $position->y - $this->getY();
        $z = $position->z - $this->getPosition()->getZ();

        if ($x * $x + $z * $z < 4 + $this->getScale()) {
            $this->motion->x = 0;
            $this->motion->z = 0;
        } else {
            $this->motion->x = $this->getSpeed() * 0.15 * ($x / (abs($x) + abs($z)));
            $this->motion->z = $this->getSpeed() * 0.15 * ($z / (abs($x) + abs($z)));
        }

        $this->setRotation(rad2deg(atan2(-$x, $z)), 0);

        $this->move($this->motion->x, $this->motion->y, $this->motion->z);
        if ($this->shouldJump()) {
            $this->jump();
        }

        $this->updateMovement();
        return $this->isAlive();
    }

    public function attackTarget()
    {
        $target = $this->entityTarget;
        if ($target == null || !$target->isAlive() || $target->getPosition()->distance($this->getPosition()) >= self::LOSE_DISTANCE) {
            $target = $this->getTarget();
            if ($target == null || $target->getPosition()->distance($this->getPosition()) >= self::LOSE_DISTANCE) {
                $this->target = null;
                return true;
            }
        }
        $this->lookAt($target->getPosition());

        if ($this->jumpTicks > 0) {
            $this->jumpTicks--;
        }

        if (!$this->isOnGround()) {
            if ($this->motion->y > -$this->gravity * 4) {
                $this->motion->y = -$this->gravity * 4;
            } else {
                $this->motion->y += $this->isUnderwater() ? $this->gravity : -$this->gravity;
            }
        } else {
            $this->motion->y -= $this->gravity;
        }
        $this->move($this->motion->x, $this->motion->y, $this->motion->z);
        if ($this->shouldJump()) {
            $this->jump();
        }

        $targetPos = $target->getPosition();
        $pos = $this->getPosition();
        $x = $targetPos->x - $pos->x;
        $y = $targetPos->y - $pos->y;
        $z = $targetPos->z - $pos->z;

        if ($x * $x + $z * $z < 1.2) {
            $this->motion->x = 0;
            $this->motion->z = 0;
        } else {
            $this->motion->x = $this->getSpeed() * 0.15 * ($x / (abs($x) + abs($z)));
            $this->motion->z = $this->getSpeed() * 0.15 * ($z / (abs($x) + abs($z)));
        }
        $this->setRotation(rad2deg(atan2(-$x, $z)), 0);

        $this->move($this->motion->x, $this->motion->y, $this->motion->z);
        if ($this->shouldJump()) {
            $this->jump();
        }

        if ($this->getPosition()->distance($target->getPosition()) <= $this->getScale() + 0.3 && $this->attackWait <= 0) {
            $event = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getBaseAttackDamage(), [], 1.2);
            /*if($target->getHealth() - $event->getFinalDamage() <= 0){
                $event->setCancelled(true);
                $this->target = null;
                $this->findNewTarget();
            }*/
            //$this->broadcastEntityEvent(4);

            $skip = false;
            if (mt_rand(1, 7) == 1) {
                $event->setKnockBack(2);
                $target->getEffects()->add(new EffectInstance(VanillaEffects::SLOWNESS(), 80, 4));
                $this->playAnimation("animation.nexus_hades.launch");
            } elseif (($rand = mt_rand(1, 3)) == 1) {
                $event->setKnockBack(1.7);
                $this->playAnimation("animation.nexus_hades.slash2");
            } elseif ($rand == 2) {
                $event->setKnockBack(1.7);
                $this->playAnimation("animation.nexus_hades.slash");
            } elseif (mt_rand(1, 5) == 1 && $target->getPosition()->distance($this->getPosition()) < 11) {
                $ents = $this->getWorld()->getNearbyEntities($this->getBoundingBox()->expandedCopy(10, 10, 10), $this);
                if (count($ents) > 1 && mt_rand(1, 3) < 3) {
                    $this->sweep();
                    $this->sweepTicks = 79;
                    $skip = true;
                    $this->playAnimation("animation.nexus_hades.sweep");
                } elseif (mt_rand(1, 3) == 3) {
                    $this->sweep();
                    $this->sweepTicks = 79;
                    $skip = true;
                    $this->playAnimation("animation.nexus_hades.sweep");
                } else {
                    $this->playAnimation("animation.nexus_hades.wacksweep");
                }
            } else {
                $this->playAnimation("animation.nexus_hades.wacksweep");
            }

            if (!$skip) {
                /*if ($target instanceof Player) {
                    if (!CombatLogger::getInstance()->isTagged($target)) {
                        $target->sendMessage(CombatLogger::getInstance()->getMessageManager()->getMessage("player-tagged"));
                    }
                    CombatLogger::getInstance()->setTagged($target, true, CombatLogger::getInstance()->getListener()->taggedTime);
                }*/
                $target->attack($event);
                if (!$target->isAlive()) {
                    $this->target = null;
                    $this->findNewTarget();
                }
                $this->attackWait = 20;
            }
        }

        $this->updateMovement();
        $this->attackWait--;
        return $this->isAlive();
    }

    public function setOnFire(int $seconds): void
    {
    }

    public function knockBack(float $x, float $z, float $base = 0.4, ?float $verticalLimit = null): void
    {
        parent::knockBack($x, $z, $base * 0.1, null);
    }

    public function attack(EntityDamageEvent $source): void
    {
        if ($source->getCause() == EntityDamageEvent::CAUSE_LAVA || $source->getCause() == EntityDamageEvent::CAUSE_FIRE || $source->getCause() == EntityDamageEvent::CAUSE_FIRE_TICK) {
            return;
        }
        if ($source instanceof EntityDamageByEntityEvent) {
            $killer = $source->getDamager();
            if ($killer instanceof Player) {
                if ($killer->getGamemode() === GameMode::CREATIVE || !BossSummonTask::getBossFight()->inArena($killer)) {
                    $source->cancel();
                    return;
                }
                if (BossSummonTask::getBossFight() !== null && !BossSummonTask::getBossFight()->inArena($killer)) {
                    $source->cancel();
                    return;
                }
                if ($this->target != $killer->getName() && mt_rand(1, 5) == 1 || $this->target == "") {
                    $this->target = $killer->getName();
                }
                if (!isset($this->assisting[$killer->getName()])) {
                    $this->assisting[$killer->getName()] = true;
                }
                if ($this->whistles >= ($this->getHealth() / 1000) && $this->whistles > 0 && $this->whistleTicks == 0) {
                    $this->whistle();
                    $this->whistles--;
                }
                /*if ($this->getHealth() <= $this->getMaxHealth() / 2 && mt_rand(1, 2) == 1 && $this->canWhistle) {
                    $this->whistle();
                    $this->canWhistle = false;
                }*/

                if (BossSummonTask::getBossFight() !== null) {
                    BossSummonTask::getBossFight()->leaderboardUpdate($killer->getName(), $source->getFinalDamage());
                }
            } elseif ($killer instanceof Living) {
                if($killer->getOwningEntity() instanceof Player) {
                    //$this->entityTarget = $killer->getOwningEntity();
                }
                //$this->entityTarget = $killer;
            }
            if ($this->throwTicks > 0 && !($killer instanceof Arrow) && $source->getCause() !== EntityDamageEvent::CAUSE_PROJECTILE /*&& !($killer instanceof StaffMinion) && !($killer instanceof ScytheMinion)*/) {
                $this->throwTicks = 0;
                $this->playAnimation("animation.player.bob.stationary");
            }
        }

        parent::attack($source);
    }

    private int $whistles = 4;

    private $whistleTicks = 0;

    /**
     * @param int $whistles
     */
    public function setWhistles(int $whistles): void
    {
        $this->whistles = $whistles;
    }

    public function whistle()
    {
        $positions = [
            $this->getPosition()->add(1, 0, 0),
            $this->getPosition()->add(0, 0, 1),
            $this->getPosition()->add(-1, 0, 0),
            $this->getPosition()->add(0, 0, -1)
        ];
        foreach ($positions as $pos) {
//            $ent = Entity::createEntity(
//                "nexus:hades_minion",
//                $this->getWorld(),
//                Entity::createBaseNBT($pos)
//            );
            $ent = new HadesMinion(Location::fromObject($pos, $this->getWorld(), 0, 0), CompoundTag::create());
            $ent->spawnToAll();
        }
        $this->whistleTicks = 42;
        $this->playAnimation("animation.nexus_hades.summon");
        /*foreach ($this->getLevel()->getNearbyEntities($this->getBoundingBox()->expandedCopy(15, 15, 15)) as $entity) {
            if ($entity instanceof $this && !$entity->hasTarget() && $entity->canWhistle) {
                $entity->target = $this->target;
                $entity->canWhistle = false;
            }
        }*/
    }

    public function kill(): void
    {
        parent::kill();
        Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(
            function (): void {
                BossSummonTask::getBossFight()->end();
            }
        ), 100);
    }

    public function getDirectionVectorWhenLookingAt(Vector3 $target): Vector3
    {
        $pos = $this->getPosition();
        $horizontal = sqrt(($target->x - $pos->x) ** 2 + ($target->z - $pos->z) ** 2);
        $vertical = $target->y - $pos->y;
        $pitch = -atan2($vertical, $horizontal) / M_PI * 180; //negative is up, positive is down

        $xDist = $target->x - $pos->x;
        $zDist = $target->z - $pos->z;
        $yaw = atan2($zDist, $xDist) / M_PI * 180 - 90;
        if ($yaw < 0) {
            $yaw += 360.0;
        }

        $y = -sin(deg2rad($pitch));
        $xz = cos(deg2rad($pitch));
        $x = -$xz * sin(deg2rad($yaw));
        $z = $xz * cos(deg2rad($yaw));

        return (new Vector3($x, $y, $z))->normalize();
    }

    //Targetting//
    public function findNewTarget()
    {
        $distance = self::FIND_DISTANCE;
        $target = null;
        /** @var NexusPlayer $player */
        foreach ($this->getWorld()->getPlayers() as $player) {

            if (BossSummonTask::getBossFight() !== null && !BossSummonTask::getBossFight()->inArena($player)) {
                continue;
            }
//                if($target === null){
//                    $target = $this->getWorld()->getNearestEntity($this->getPosition(), $distance, StaffMinion::class);
//                }
//                if($target === null){
//                    $target = $this->getWorld()->getNearestEntity($this->getPosition(), $distance, ScytheMinion::class);
//                }
            if ($player->getPosition()->distance($this->getPosition()) <= $distance && $player->getGamemode() !== GameMode::CREATIVE && BossSummonTask::getBossFight() !== null && BossSummonTask::getBossFight()->inArena($player)) {
                /*$obstructed = false;
                foreach (VoxelRayTrace::inDirection($this->getPosition(), $this->getDirectionVectorWhenLookingAt($player), $player->distance($this)) as $voxel) {
                    if($this->getLevel()->getBlock($voxel->floor())->isSolid()){
                        $obstructed = true;
                        break;
                    }
                }*/
                //if(!$obstructed) {
                $distance = $player->getPosition()->distance($this->getPosition());
                $target = $player;
                //}
            }


        }
        $this->findNewTargetTicks = 60;
        $this->target = ($target != null ? $target->getName() : "");
    }

    public function hasTarget()
    {
        $target = $this->getTarget();
        if ($target == null) return false;

        $player = $this->getTarget();
        return $player->getGamemode() !== GameMode::CREATIVE;
    }

    public function getTarget()
    {
        return Server::getInstance()->getPlayerExact((string)$this->target);
    }

    public function atRandomPosition()
    {
        return $this->getRandomPosition() == null || $this->getPosition()->distance($this->getRandomPosition()) <= 2;
    }

    public function getRandomPosition()
    {
        return $this->randomPosition;
    }

    public function generateRandomPosition()
    {
        $pos = $this->getPosition();
        $minX = $pos->getFloorX() - 8;
        $minY = $pos->getFloorY() - 8;
        $minZ = $pos->getFloorZ() - 8;

        $maxX = $minX + 16;
        $maxY = $minY + 16;
        $maxZ = $minZ + 16;

        $level = $this->getWorld();

        for ($attempts = 0; $attempts < 16; ++$attempts) {
            $x = mt_rand($minX, $maxX);
            $y = mt_rand($minY, $maxY);
            $z = mt_rand($minZ, $maxZ);
            while ($y >= 0 and !$level->getBlockAt($x, $y, $z)->isSolid()) {
                $y--;
            }
            if ($y < 0) {
                continue;
            }
            $blockUp = $level->getBlockAt($x, $y + 1, $z);
            $blockUp2 = $level->getBlockAt($x, $y + 2, $z);
            if ($blockUp->isSolid() or $blockUp instanceof Liquid or $blockUp2->isSolid() or $blockUp2 instanceof Liquid) {
                continue;
            }

            break;
        }

        $this->randomPosition = new Vector3($x, $y + 1, $z);
    }

    public function getSpeed()
    {
        return ($this->isUnderwater() ? $this->speed / 2 : $this->speed);
    }

    public function getBaseAttackDamage()
    {
        return $this->attackDamage;
    }

    public function getAssisting()
    {
        $assisting = [];
        foreach ($this->assisting as $name => $bool) {
            $player = Server::getInstance()->getPlayerExact($name);
            if ($player instanceof Player) $assisting[] = $player;
        }
        return $assisting;
    }

    public function getFrontBlock($y = 0)
    {
        $dv = $this->getDirectionVector();
        $pos = $this->getPosition()p->add($dv->x * $this->getScale(), $y + 1, $dv->z * $this->getScale())->round();
        return $this->getWorld()->getBlock($pos);
    }


    public function shouldJump()
    {
        if ($this->jumpTicks > 0) return false;

        return $this->isCollidedHorizontally ||
            ($this->getFrontBlock()->getId() != 0 || $this->getFrontBlock(-1) instanceof Stair) ||
            ($this->getWorld()->getBlock($this->getPosition()->add(0, -0, 5)) instanceof Slab &&
                (!$this->getFrontBlock(-0.5) instanceof Slab && $this->getFrontBlock(-0.5)->getId() != 0)) &&
            $this->getFrontBlock(1)->getId() == 0 &&
            $this->getFrontBlock(2)->getId() == 0 &&
            !$this->getFrontBlock() instanceof Flowable &&
            $this->jumpTicks == 0;
    }

    public function getJumpMultiplier()
    {
        return 16;

        if (
            $this->getFrontBlock() instanceof Slab ||
            $this->getFrontBlock() instanceof Stair ||

            $this->getWorld()->getBlock($this->getPosition()->subtract(0, 0.5, 0)->round()) instanceof Slab &&
            $this->getFrontBlock()->getId() != 0
        ) {
            $fb = $this->getFrontBlock();
            if ($fb instanceof Slab && $fb->getDamage() & 0x08 > 0) return 8;
            if ($fb instanceof Stair && $fb->getDamage() & 0x04 > 0) return 8;
            return 4;
        }
        return 8;
    }

    public function jump(): void
    {
        $this->motion->y = $this->gravity * $this->getJumpMultiplier();
        $this->move($this->motion->x * 1.25, $this->motion->y, $this->motion->z * 1.25);
        $this->jumpTicks = 5; //($this->getJumpMultiplier() == 4 ? 2 : 5);
    }

    public function getName(): string
    {
        return "Hades";
    }

    public function sweep(): void
    {
        $center = $this->getPosition();
        $default = [$this->getLocation()->yaw, $this->getLocation()->pitch];
        foreach (VoxelRayTrace::inDirection($center, $this->getDirectionVector(), 10) as $voxel) {
        }
        $task = new ClosureTask(function () use (&$voxel, $center): void {
            if ($this->isAlive()) {
                //    $voxel->setComponents(($center->z - $voxel->z) * -1 + $center->z, $voxel->y, ($center->x - $voxel->x) * 1 + $center->x);
                $x = $center->x - $voxel->x;
                $z = $center->z - $voxel->z;
                $newX = -$z;
                $newZ = $x;
                $voxel = new Vector3($newX + $center->x, $voxel->y, $newZ + $center->z);


                $this->lookAt($voxel);
                // 90* rotation ^
                /*foreach($this->getLevel()->getNearbyEntities(new AxisAlignedBB(
                    $voxel->x - 2, $voxel->y - 2, $voxel->z - 2,
                    $voxel->x + 2, $voxel->y + 2, $voxel->z + 2
                )) as $ent){
                    $ent->attack(new EntityDamageByEntityEvent($this, $ent, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getBaseAttackDamage(), [], 1.2));
                }*/

                //var_dump($newX . ":" . $newZ);
                $expl = new HadesExplosion(Position::fromObject($voxel, $center->getWorld()), 3, $this);
                $expl2 = new HadesExplosion($center, 3, $this);
                $expl->explodeB();
                $expl2->explodeB();
                foreach ($expl->getAffectedEntities() as $entity) {
                    if ($entity instanceof Living && $entity->isAlive()) {
                        $entity->knockBack($entity->getPosition()->x - $this->getPosition()->x, $entity->getPosition()->z - $this->getPosition()->z, 1.5);
                    }
                }
                foreach ($expl2->getAffectedEntities() as $entity) {
                    if ($entity instanceof Living && $entity->isAlive()) {
                        $entity->knockBack($entity->getPosition()->x - $this->getPosition()->x, $entity->getPosition()->z - $this->getPosition()->z, 1.5);
                    }
                }
            }
        });
        $tasks = [clone $task, clone $task, clone $task, clone $task, clone $task];
        Nexus::getInstance()->getScheduler()->scheduleDelayedTask($task, 36);
        Nexus::getInstance()->getScheduler()->scheduleDelayedTask($tasks[0], 38);
        Nexus::getInstance()->getScheduler()->scheduleDelayedTask($tasks[1], 41);
        Nexus::getInstance()->getScheduler()->scheduleDelayedTask($tasks[2], 44);
        Nexus::getInstance()->getScheduler()->scheduleDelayedTask($tasks[3], 47);
        Nexus::getInstance()->getScheduler()->scheduleDelayedTask($tasks[4], 51);
        Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(
            function () use ($default): void {
                if(!$this->isClosed()) {
                    $this->setRotation($default[0], $default[1]);
                }
            }
        ), 58);
        /*$task = new ClosureTask(function() use($default) : void{
            if($this->isAlive()) {
                $center = $this->getPosition();
                foreach (VoxelRayTrace::inDirection($center, $this->getDirectionVector(), 10) as $voxel) {
                }
                $voxel->setComponents(($center->z - $voxel->z) * -1 + $center->z, $voxel->y, ($center->x - $voxel->x) * 1 + $center->x);
                $this->lookAt($voxel);
                // 90* rotation ^

                foreach (VoxelRayTrace::inDirection($center, $this->getDirectionVector(), 10) as $voxel) {
                }
                var_dump($voxel);
                $expl = new HadesExplosion(Position::fromObject($voxel)->setLevel($center->getLevel()), 3, $this);
                $expl->explodeB();

                Loader::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use($default, $voxel, $center) : void{
                    if($this->isAlive()) {
                        $voxel->setComponents(($center->z - $voxel->z) * -1 + $center->z, $voxel->y, ($center->x - $voxel->x) * 1 + $center->x);
                        $this->lookAt($voxel);
                        // 90* rotation ^

                        foreach (VoxelRayTrace::inDirection($center, $this->getDirectionVector(), 10) as $voxel) {
                        }
                        var_dump($voxel);
                        $expl = new HadesExplosion(Position::fromObject($voxel)->setLevel($center->getLevel()), 3, $this);
                        $expl->explodeB();

                        Loader::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use($default, $voxel, $center) : void{
                            if($this->isAlive()) {
                                $voxel->setComponents(($center->z - $voxel->z) * -1 + $center->z, $voxel->y, ($center->x - $voxel->x) * 1 + $center->x);
                                $this->lookAt($voxel);
                                // 90* rotation ^

                                foreach (VoxelRayTrace::inDirection($center, $this->getDirectionVector(), 10) as $voxel) {
                                }
                                var_dump($voxel);
                                $expl = new HadesExplosion(Position::fromObject($voxel)->setLevel($center->getLevel()), 3, $this);
                                $expl->explodeB();

                                Loader::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use($default, $voxel, $center) : void{
                                    if($this->isAlive()) {
                                        $voxel->setComponents(($center->z - $voxel->z) * -1 + $center->z, $voxel->y, ($center->x - $voxel->x) * 1 + $center->x);
                                        $this->lookAt($voxel);
                                        // 90* rotation ^

                                        foreach (VoxelRayTrace::inDirection($center, $this->getDirectionVector(), 10) as $voxel) {
                                        }
                                        var_dump($voxel);
                                        $expl = new HadesExplosion(Position::fromObject($voxel)->setLevel($center->getLevel()), 3, $this);
                                        $expl->explodeB();

                                        Loader::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use($default, $voxel, $center) : void{
                                            if($this->isAlive()) {
                                                $voxel->setComponents(($center->z - $voxel->z) * -1 + $center->z, $voxel->y, ($center->x - $voxel->x) * 1 + $center->x);
                                                $this->lookAt($voxel);
                                                // 90* rotation ^

                                                foreach (VoxelRayTrace::inDirection($center, $this->getDirectionVector(), 10) as $voxel) {
                                                }
                                                var_dump($voxel);
                                                $expl = new HadesExplosion(Position::fromObject($voxel)->setLevel($center->getLevel()), 3, $this);
                                                $expl->explodeB();

                                                Loader::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use($default, $voxel, $center) : void{
                                                    if($this->isAlive()) {
                                                        $voxel->setComponents(($center->z - $voxel->z) * -1 + $center->z, $voxel->y, ($center->x - $voxel->x) * 1 + $center->x);
                                                        $this->lookAt($voxel);
                                                        // 90* rotation ^

                                                        foreach (VoxelRayTrace::inDirection($center, $this->getDirectionVector(), 10) as $voxel) {
                                                        }
                                                        var_dump($voxel);
                                                        $expl = new HadesExplosion(Position::fromObject($voxel)->setLevel($center->getLevel()), 3, $this);
                                                        $expl->explodeB();

                                                        Loader::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use($default, $voxel, $center) : void{
                                                            if($this->isAlive()) {
                                                                $this->yaw = $default[0];
                                                                $this->pitch = $default[1];
                                                            }
                                                        }), 28);
                                                    }
                                                }), 4);
                                            }
                                        }), 3);
                                    }
                                }), 2);
                            }
                        }), 3);
                    }
                }), 2);
            }
        });
        Loader::getInstance()->getScheduler()->scheduleDelayedTask($task, 36);*/
    }
}