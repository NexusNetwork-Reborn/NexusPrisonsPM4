<?php

namespace core\game\wormhole;

use core\display\animation\AnimationException;
use core\display\animation\entity\WormholeSelectEntity;
use core\display\animation\entity\ItemBaseEntity;
use core\display\animation\type\WormholeAnimation;
use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\event\LevelUpPickaxeEvent;
use core\game\item\types\custom\EnchantmentBook;
use core\game\item\types\custom\EnchantmentPage;
use core\game\item\types\custom\Satchel;
use core\game\item\types\Rarity;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Axe;
use core\game\item\types\vanilla\Bow;
use core\game\item\types\vanilla\Pickaxe;
use core\game\item\types\vanilla\Sword;
use core\game\wormhole\task\InitializePositionTask;
use core\game\wormhole\task\ShowResultTask;
use core\game\wormhole\task\SpawnOptionsTask;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\color\Color;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\DustParticle;
use pocketmine\world\particle\LavaParticle;
use pocketmine\world\Position;

class WormholeSession {

    /** @var NexusPlayer */
    private $owner;

    /** @var Wormhole */
    private $wormhole;

    /** @var WormholeAnimation */
    private $animation;

    /** @var Item */
    private $item;

    /** @var EnchantmentInstance[] */
    private $enchantments = [];

    /** @var int[] */
    private $chances = [];

    /** @var WormholeSelectEntity[] */
    private $entities = [];

    /** @var int */
    private $rarityLimit;

    /** @var null|SpawnOptionsTask */
    private $spawnOptionsTask = null;

    /**
     * WormholeSession constructor.
     *
     * @param NexusPlayer $player
     * @param Wormhole $wormhole
     * @param Item $item
     * @param int $rarityLimit
     *
     * @throws AnimationException
     */
    public function __construct(NexusPlayer $player, Wormhole $wormhole, Item $item, int $rarityLimit) {
        $display = $item;
        if(Satchel::isInstanceOf($item)) {
            $display = Satchel::fromItem($item)->getType();
        }
        $this->animation = new WormholeAnimation($player, $display, $wormhole->getCenter());
        Nexus::getInstance()->getDisplayManager()->getAnimationManager()->addAnimation($this->animation);
        $this->owner = $player;
        $this->wormhole = $wormhole;
        $this->item = $item;
        $this->rarityLimit = $rarityLimit;
        if($item instanceof Pickaxe) {
            $this->setEnchantments();
        }
        elseif(Satchel::isInstanceOf($item)) {
            $this->rarityLimit = Enchantment::GODLY;
            $this->setEnchantments();
        }
        elseif($item instanceof Armor or $item instanceof Bow or $item instanceof Sword or $item instanceof Axe) {
            Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new class($this) extends Task {

                /** @var WormholeSession */
                private $session;

                /**
                 *  constructor.
                 *
                 * @param WormholeSession $session
                 */
                public function __construct(WormholeSession $session) {
                    $this->session = $session;
                }

                public function onRun(): void {
                    $this->session->handleRepair();
                }
            }, 80);
        }
        else {
            Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new class($this) extends Task {

                /** @var WormholeSession */
                private $session;

                /**
                 *  constructor.
                 *
                 * @param WormholeSession $session
                 */
                public function __construct(WormholeSession $session) {
                    $this->session = $session;
                }

                public function onRun(): void {
                    $this->session->handleLevelUp();
                }
            }, 80);
        }
    }

    /**
     * @return NexusPlayer
     */
    public function getOwner(): NexusPlayer {
        return $this->owner;
    }

    /**
     * @return Item
     */
    public function getItem(): Item {
        return $this->item;
    }

    /**
     * @param EnchantmentInstance|null $enchantmentInstance
     *
     * @return int
     */
    public function getChance(?EnchantmentInstance $enchantmentInstance): int {
        if($enchantmentInstance === null) {
            return 100;
        }
        return $this->chances[EnchantmentIdMap::getInstance()->toId($enchantmentInstance->getType())];
    }

    /**
     * @return int[]
     */
    public function getChances(): array {
        return $this->chances;
    }

    /**
     * @param int[] $chances
     */
    public function setChances(array $chances): void {
        $this->chances = $chances;
    }

    /**
     * @return EnchantmentInstance[]
     */
    public function getEnchantments(): array {
        return $this->enchantments;
    }

    public function setEnchantments(): void {
        $enchantments = EnchantmentManager::getEnchantments();
        $compatible = [];
        $limitedEnchants = [
            Enchantment::MOMENTUM,
            Enchantment::LUCKY,
            Enchantment::EFFICIENCY2
        ];
        foreach($enchantments as $enchantment) {
            if($this->item instanceof Pickaxe) {
                if($enchantment->getRarity() === Enchantment::GODLY) {
                    continue;
                }
            }
            if($enchantment->getRarity() > $this->rarityLimit) {
                continue;
            }
            if($this->rarityLimit === Enchantment::EXECUTIVE) {
                if(mt_rand(1, 5) !== mt_rand(1, 5)) {
                    continue;
                }
            }
            $max = $enchantment->getMaxLevel();
            if(in_array($enchantment->getRuntimeId(), $limitedEnchants)) {
                $max -= 1;
            }
            if(EnchantmentManager::canEnchant($this->item, $enchantment)) {
                if($this->item->getEnchantmentLevel($enchantment) < $max) {
                    $compatible[] = $enchantment;
                }
            }
        }
        shuffle($compatible);
        $final = [];
        for($i = 0; $i < 6; $i++) {
            if(empty($compatible)) {
                break;
            }
            $enchantment = array_shift($compatible);
            $final[] = new EnchantmentInstance($enchantment, $this->item->getEnchantmentLevel($enchantment) + 1);
        }
        $this->enchantments = $final;
        $this->chances = [];
        $add = 0;
        if($this->owner->isLoaded()) {
            $add = $this->owner->getDataSession()->getRank()->getBooster() * 100;
        }
        foreach($final as $enchantmentInstance) {
            if(Satchel::isInstanceOf($this->item)) {
                $this->chances[$enchantmentInstance->getType()->getRuntimeId()] = 100;
                continue;
            }
            $this->chances[$enchantmentInstance->getType()->getRuntimeId()] = min(100, mt_rand(1, 100) + $add);
        }
        if(empty($this->enchantments)) {
            $this->enchantments[] = null;
        }
        $this->displayEnchantments();
    }

    public function displayEnchantments(): void {
        $this->spawnOptionsTask = new SpawnOptionsTask($this, (int) floor($this->owner->getLocation()->getYaw()));
        if(!empty($this->entities)) {
            foreach($this->entities as $entity) {
                if(!$entity->isClosed()) {
                    $entity->flagForDespawn();
                }
            }
            $this->entities = [];
            Nexus::getInstance()->getScheduler()->scheduleDelayedRepeatingTask($this->spawnOptionsTask, 20, 40);
            return;
        }
        Nexus::getInstance()->getScheduler()->scheduleDelayedRepeatingTask($this->spawnOptionsTask, 80, 40);
    }

    /**
     * @return WormholeAnimation
     */
    public function getAnimation(): WormholeAnimation {
        return $this->animation;
    }

    /**
     * @param WormholeSelectEntity[] $entities
     */
    public function setEntities(array $entities): void {
        $this->entities = $entities;
    }

    /**
     * @return WormholeSelectEntity[]
     */
    public function getEntities(): array {
        return $this->entities;
    }

    /**
     * @param int $id
     *
     * @return WormholeSelectEntity|null
     */
    public function getEntityById(int $id): ?WormholeSelectEntity {
        return $this->entities[$id] ?? null;
    }

    /**
     * @return Wormhole
     */
    public function getWormhole(): Wormhole {
        return $this->wormhole;
    }

    public function close(): void {
        if($this->item instanceof Pickaxe) {
            $enchantment = $this->enchantments[array_rand($this->enchantments)];
            if($enchantment !== null) {
                $id = EnchantmentIdMap::getInstance()->toId($enchantment->getType());
                $chance = $this->chances[$id];
                if(mt_rand(1, 100) <= $chance) {
                    $this->item->addEnchantment($enchantment);
                }
            }
            $this->item->subtractPoints(1);
        }
        if($this->owner->getInventory()->canAddItem($this->item)) {
            $this->owner->getInventory()->addItem($this->item);
        } elseif($this->owner->isLoaded() && $this->owner->getDataSession()->getInbox()->getInventory()->canAddItem($this->item)) {
            $this->owner->getDataSession()->getInbox()->getInventory()->addItem($this->item);
            $this->owner->sendTranslatedMessage("wormholeInboxAlert");
        } elseif($this->owner->isLoaded()) {
            $this->owner->getWorld()->dropItem($this->owner->getPosition(), $this->item);
            $this->owner->sendTranslatedMessage("dropAlert");
        }
        /** @var ItemBaseEntity $entity */
        $entity = $this->animation->getEntity();
        if(!$entity->isClosed() && !$entity->isFlaggedForDespawn()) {
            $entity->flagForDespawn();
        }
        Nexus::getInstance()->getGameManager()->getWormholeManager()->removeSession($this);
    }

    public function handleLevelUp(): void {
        $center = $this->wormhole->getCenter();
        /** @var ItemBaseEntity $entity */
        $entity = $this->animation->getEntity();
        $entity->initialize(Position::fromObject($center->add(-0.5, 30, -0.5), $center->getWorld()), 1, false, 20);
        Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new InitializePositionTask($this, Position::fromObject($center->add(-0.5, 2, -0.5), $center->getWorld()), 1, false, 20), 20);
        Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new class($entity) extends Task {

            /** @var ItemBaseEntity */
            private $entity;

            /**
             *  constructor.
             *
             * @param ItemBaseEntity $entity
             */
            public function __construct(ItemBaseEntity $entity) {
                $this->entity = $entity;
            }

            public function onRun(): void {
                $level = $this->entity->getWorld();
                if($level === null or $this->entity->isClosed() or $this->entity->isFlaggedForDespawn()) {
                    return;
                }
                $owner = $this->entity->getOwningEntity();
                if($owner instanceof NexusPlayer and $owner->isOnline() and (!$owner->isClosed())) {
                    $cx = $this->entity->getPosition()->getX();
                    $cy = $this->entity->getPosition()->getY() - 1;
                    $cz = $this->entity->getPosition()->getZ();
                    $radius = (int)1;
                    for($i = 0; $i < 61; $i += 1.1) {
                        $x = $cx + ($radius * cos($i));
                        $z = $cz + ($radius * sin($i));
                        $pos = new Vector3($x, $cy, $z);
                        $level->addParticle($pos, new LavaParticle(), [$owner]);
                    }
                }
            }
        }, 50);
        Nexus::getInstance()->getScheduler()->scheduleDelayedRepeatingTask(new ShowResultTask($this), 100, 2);
        /** @var CompoundTag $tag */
        $book = EnchantmentBook::fromItem($this->item);
        $success = $book->getSuccess();
        $destroy = $book->getDestroy();
        $enchantment = $book->getEnchantment()->getType();
        $level = $book->getEnchantment()->getLevel();
        if($success >= mt_rand(1, 100) and $destroy < mt_rand(1, 100)) { // TODO: This executive stuff only?
            if($enchantment->getMaxLevel() === $level) {
                $enchantment = EnchantmentManager::getExecutiveEnchantmentByPremature($enchantment);
                $level = 0;
            }
            $give = (new EnchantmentBook(new EnchantmentInstance($enchantment, $level + 1), mt_rand(1, 100), mt_rand(1, 100), 0))->toItem();
            $name = $give->getCustomName();
            $name .= "\n" . TextFormat::RESET . TextFormat::GRAY . TextFormat::BOLD . "(LEVEL UP!)";
        }
        else {
            // TODO: No pages for executives
            $give = (new EnchantmentPage($enchantment->getRarity(), $level * 2, $level * 2))->toItem();
            $name = $give->getCustomName();
            $name .= "\n" . TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "(DESTROYED!)";
        }
        Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new class($entity, $name) extends Task {

            /** @var ItemBaseEntity */
            private $entity;

            /** @var string */
            private $name;

            /**
             *  constructor.
             *
             * @param ItemBaseEntity $entity
             * @param string $name
             */
            public function __construct(ItemBaseEntity $entity, string $name) {
                $this->entity = $entity;
                $this->name = $name;
            }

            public function onRun(): void {
                if($this->entity->isClosed() or $this->entity->isFlaggedForDespawn()) {
                    return;
                }
                $this->entity->setNameTag($this->name);
            }
        }, 40);
        $skin = Nexus::getInstance()->getDisplayManager()->getAnimationManager()->getSkin($give->getId());
        $entity->setSkin($skin);
        $entity->sendSkin($this->owner);
        $this->owner->getInventory()->addItem($give);
        Nexus::getInstance()->getGameManager()->getWormholeManager()->removeSession($this);
    }

    public function handleRepair(): void {
        $center = $this->wormhole->getCenter();
        /** @var ItemBaseEntity $entity */
        $entity = $this->animation->getEntity();
        $entity->initialize(Position::fromObject($center->add(-0.5, 30, -0.5), $center->getWorld()), 1, false, 20);
        Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new InitializePositionTask($this, Position::fromObject($center->add(-0.5, 2, -0.5), $center->getWorld()), 1, false, 20), 20);
        Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new class($entity) extends Task {

            /** @var ItemBaseEntity */
            private $entity;

            /**
             *  constructor.
             *
             * @param ItemBaseEntity $entity
             */
            public function __construct(ItemBaseEntity $entity) {
                $this->entity = $entity;
            }

            public function onRun(): void {
                $level = $this->entity->getWorld();
                if($level === null or $this->entity->isClosed() or $this->entity->isFlaggedForDespawn()) {
                    return;
                }
                $owner = $this->entity->getOwningEntity();
                if($owner instanceof NexusPlayer and $owner->isOnline() and (!$owner->isClosed())) {
                    $cx = $this->entity->getPosition()->getX();
                    $cy = $this->entity->getPosition()->getY() - 1;
                    $cz = $this->entity->getPosition()->getZ();
                    $radius = (int)1;
                    for($i = 0; $i < 61; $i += 1.1) {
                        $x = $cx + ($radius * cos($i));
                        $z = $cz + ($radius * sin($i));
                        $pos = new Vector3($x, $cy, $z);
                        $level->addParticle($pos, new LavaParticle(), [$owner]);
                    }
                }
            }
        }, 50);
        Nexus::getInstance()->getScheduler()->scheduleDelayedRepeatingTask(new ShowResultTask($this), 100, 2);
        $name = $this->item->hasCustomName() ? $this->item->getCustomName() : $this->item->getName();
        if($this->item instanceof Armor or $this->item instanceof Bow or $this->item instanceof Sword or $this->item instanceof Axe) {
            $this->item->setDamage(0);
            $name .= "\n" . TextFormat::RESET . TextFormat::GRAY . TextFormat::BOLD . "(REPAIRED!)";
        }
        Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new class($entity, $name) extends Task {

            /** @var ItemBaseEntity */
            private $entity;

            /** @var string */
            private $name;

            /**
             *  constructor.
             *
             * @param ItemBaseEntity $entity
             * @param string $name
             */
            public function __construct(ItemBaseEntity $entity, string $name) {
                $this->entity = $entity;
                $this->name = $name;
            }

            public function onRun(): void {
                if($this->entity->isClosed() or $this->entity->isFlaggedForDespawn()) {
                    return;
                }
                $this->entity->setNameTag($this->name);
            }
        }, 40);
        if($this->owner !== null && $this->owner->isOnline() && $this->owner->getInventory() !== null) {
            $this->owner->getInventory()->addItem($this->item);
        } elseif($this->owner !== null && $this->owner->isLoaded() && $this->owner->getDataSession()->getInbox()->getInventory()->canAddItem($this->item)) {
            $this->owner->getDataSession()->getInbox()->getInventory()->addItem($this->item);
            $this->owner->sendTranslatedMessage("inboxAlert");
        } elseif($this->owner !== null && $this->owner->isLoaded()) {
            $this->owner->getWorld()->dropItem($this->owner->getPosition(), $this->item);
            $this->owner->sendTranslatedMessage("dropAlert");
        }
        Nexus::getInstance()->getGameManager()->getWormholeManager()->removeSession($this);
    }

    /**
     * @param WormholeSelectEntity $item
     */
    public function handleInteract(WormholeSelectEntity $item): void {
        if($this->item instanceof Pickaxe or Satchel::isInstanceOf($this->item)) {
            $enchantment = $item->getEnchantment();
            if($enchantment !== null) {
                $chance = $this->chances[EnchantmentIdMap::getInstance()->toId($enchantment->getType())];
                $this->enchant($enchantment, $chance);
            }
            elseif($this->item instanceof Pickaxe) {
                $this->levelUpPickaxe();
            }
        }
        $cx = $item->getPosition()->getX();
        $cy = $item->getPosition()->getY() - 1;
        $cz = $item->getPosition()->getZ();
        $radius = (int)1;
        for($i = 0; $i < 21; $i += 1.1) {
            $x = $cx + ($radius * cos($i));
            $z = $cz + ($radius * sin($i));
            $pos = new Vector3($x, $cy, $z);
            $this->owner->getWorld()->addParticle($pos, new DustParticle(new Color(255, 0, 0)), [$this->owner]);
        }
        Nexus::getInstance()->getGameManager()->getWormholeManager()->removeSession($this);
    }

    public function levelUpPickaxe(): void {
        $center = $this->wormhole->getCenter();
        /** @var ItemBaseEntity $entity */
        $entity = $this->animation->getEntity();
        $entity->initialize(Position::fromObject($center->add(-0.5, 30, -0.5), $center->getWorld()), 1, false, 20);
        Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new InitializePositionTask($this, Position::fromObject($center->add(-0.5, 2, -0.5), $center->getWorld()), 1, false, 20), 20);
        Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new class($entity) extends Task {

            /** @var ItemBaseEntity */
            private $entity;

            /**
             *  constructor.
             *
             * @param ItemBaseEntity $entity
             */
            public function __construct(ItemBaseEntity $entity) {
                $this->entity = $entity;
            }

            public function onRun(): void {
                $level = $this->entity->getWorld();
                if($level === null or $this->entity->isClosed() or $this->entity->isFlaggedForDespawn()) {
                    return;
                }
                $owner = $this->entity->getOwningEntity();
                if($owner instanceof NexusPlayer and $owner->isOnline() and (!$owner->isClosed())) {
                    $cx = $this->entity->getPosition()->getX();
                    $cy = $this->entity->getPosition()->getY() - 1;
                    $cz = $this->entity->getPosition()->getZ();
                    $radius = (int)1;
                    for($i = 0; $i < 61; $i += 1.1) {
                        $x = $cx + ($radius * cos($i));
                        $z = $cz + ($radius * sin($i));
                        $pos = new Vector3($x, $cy, $z);
                        $level->addParticle($pos, new LavaParticle(), [$owner]);
                    }
                }
            }
        }, 50);
        Nexus::getInstance()->getScheduler()->scheduleDelayedRepeatingTask(new ShowResultTask($this), 100, 2);
        $name = $this->item->hasCustomName() ? $this->item->getCustomName() : $this->item->getName();
        if($this->item instanceof Pickaxe) {
            $this->item->subtractPoints(1);
            $this->item->addSubtractedFailure();
            $name .= "\n" . TextFormat::RESET . TextFormat::GRAY . TextFormat::BOLD . "(LEVEL UP!)";
        }
        Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new class($entity, $name) extends Task {

            /** @var ItemBaseEntity */
            private $entity;

            /** @var string */
            private $name;

            /**
             *  constructor.
             *
             * @param ItemBaseEntity $entity
             * @param string $name
             */
            public function __construct(ItemBaseEntity $entity, string $name) {
                $this->entity = $entity;
                $this->name = $name;
            }

            public function onRun(): void {
                if($this->entity->isClosed() or $this->entity->isFlaggedForDespawn()) {
                    return;
                }
                $this->entity->setNameTag($this->name);
            }
        }, 40);
        $this->owner->getInventory()->addItem($this->item);
        $this->owner->playDingSound();
        $taskHandler = $this->spawnOptionsTask->getHandler();
        if($taskHandler !== null and Nexus::getInstance()->getScheduler()->isQueued($taskHandler)) {
            $this->spawnOptionsTask->cancel();
        }
        $ev = new LevelUpPickaxeEvent($this->owner, $this->item);
        $ev->call();
        Nexus::getInstance()->getGameManager()->getWormholeManager()->removeSession($this);
    }

    public function enchant(EnchantmentInstance $enchantment, int $chance): void {
        $center = $this->wormhole->getCenter();
        /** @var ItemBaseEntity $entity */
        $entity = $this->animation->getEntity();
        $entity->initialize(Position::fromObject($center->add(-0.5, 30, -0.5), $center->getWorld()), 1, false, 20);
        Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new InitializePositionTask($this, Position::fromObject($center->add(-0.5, 2, -0.5), $center->getWorld()), 1, false, 20), 20);
        Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new class($entity) extends Task {

            /** @var ItemBaseEntity */
            private $entity;

            /**
             *  constructor.
             *
             * @param ItemBaseEntity $entity
             */
            public function __construct(ItemBaseEntity $entity) {
                $this->entity = $entity;
            }

            public function onRun(): void {
                $level = $this->entity->getWorld();
                if($level === null or $this->entity->isClosed() or $this->entity->isFlaggedForDespawn()) {
                    return;
                }
                $owner = $this->entity->getOwningEntity();
                if($owner instanceof NexusPlayer and $owner->isOnline() and (!$owner->isClosed())) {
                    $cx = $this->entity->getPosition()->getX();
                    $cy = $this->entity->getPosition()->getY() - 1;
                    $cz = $this->entity->getPosition()->getZ();
                    $radius = (int)1;
                    for($i = 0; $i < 61; $i += 1.1) {
                        $x = $cx + ($radius * cos($i));
                        $z = $cz + ($radius * sin($i));
                        $pos = new Vector3($x, $cy, $z);
                        $level->addParticle($pos, new LavaParticle(), [$owner]);
                    }
                }
            }
        }, 50);
        Nexus::getInstance()->getScheduler()->scheduleDelayedRepeatingTask(new ShowResultTask($this), 100, 2);
        $name = $this->item->hasCustomName() ? $this->item->getCustomName() : $this->item->getName();
        if($this->item instanceof Pickaxe) {
            $type = $enchantment->getType();
            $this->item->subtractPoints(1);
            if(mt_rand(1, 100) <= $chance) {
                $this->item->addEnchantment($enchantment);
                $maxLevel = "";
                if($enchantment->getLevel() === $type->getMaxLevel()) {
                    $maxLevel = TextFormat::BOLD;
                }
                $name .= "\n" . TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . ">> " . TextFormat::RESET . Rarity::RARITY_TO_COLOR_MAP[Rarity::ENCHANTMENT_RARITY_TO_STRING_MAP[$type->getRarity()]] . $maxLevel . $type->getName() . TextFormat::AQUA . " " . EnchantmentManager::getRomanNumber($enchantment->getLevel()) . TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . " >>";
            }
            else {
                $name .= "\n" . TextFormat::RESET . TextFormat::GRAY . TextFormat::BOLD . "(ENCHANTMENT FAILED!)";
            }
        }
        if(Satchel::isInstanceOf($this->item)) {
            $satchel = Satchel::fromItem($this->item);
            $type = $enchantment->getType();
            $satchel->addEnchantment($enchantment);
            $maxLevel = "";
            if($enchantment->getLevel() === $type->getMaxLevel()) {
                $maxLevel = TextFormat::BOLD;
            }
            $name .= "\n" . TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . ">> " . TextFormat::RESET . Rarity::RARITY_TO_COLOR_MAP[Rarity::ENCHANTMENT_RARITY_TO_STRING_MAP[$type->getRarity()]] . $maxLevel . $type->getName() . TextFormat::AQUA . " " . EnchantmentManager::getRomanNumber($enchantment->getLevel()) . TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . " >>";
            $this->item = $satchel->toItem();
        }
        Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new class($entity, $name) extends Task {

            /** @var ItemBaseEntity */
            private $entity;

            /** @var string */
            private $name;

            /**
             *  constructor.
             *
             * @param ItemBaseEntity $entity
             * @param string $name
             */
            public function __construct(ItemBaseEntity $entity, string $name) {
                $this->entity = $entity;
                $this->name = $name;
            }

            public function onRun(): void {
                if($this->entity->isClosed() or $this->entity->isFlaggedForDespawn()) {
                    return;
                }
                $this->entity->setNameTag($this->name);
            }
        }, 40);
        $this->owner->getInventory()->addItem($this->item);
        $this->owner->playDingSound();
        $taskHandler = $this->spawnOptionsTask->getHandler();
        if($taskHandler !== null and Nexus::getInstance()->getScheduler()->isQueued($taskHandler)) {
            $this->spawnOptionsTask->cancel();
        }
        Nexus::getInstance()->getGameManager()->getWormholeManager()->removeSession($this);
    }

    /**
     * @return SpawnOptionsTask|null
     */
    public function getSpawnOptionsTask(): ?SpawnOptionsTask {
        return $this->spawnOptionsTask;
    }
}