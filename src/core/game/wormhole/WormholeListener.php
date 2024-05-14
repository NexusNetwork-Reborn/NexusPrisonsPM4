<?php
declare(strict_types=1);

namespace core\game\wormhole;

use core\display\animation\AnimationException;
use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\types\custom\EnchantmentBook;
use core\game\item\types\custom\EnchantmentDust;
use core\game\item\types\custom\EnchantmentOrb;
use core\game\item\types\custom\EnchantmentReroll;
use core\game\item\types\custom\Satchel;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Axe;
use core\game\item\types\vanilla\Bow;
use core\game\item\types\vanilla\Pickaxe;
use core\game\item\types\vanilla\Sword;
use core\game\wormhole\entity\ExecutiveEnderman;
use core\game\wormhole\event\EnchantmentOrbUseEvent;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rpg\RPGManager;
use core\player\rpg\XPUtils;
use muqsit\invmenu\inventory\InvMenuInventory;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\EndermanTeleportParticle;
use pocketmine\world\sound\PopSound;

class WormholeListener implements Listener {

    /** @var Nexus */
    private $core;

    /**
     * WormholeListener constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
    }

    /**
     * @priority HIGHEST
     * @param PlayerDropItemEvent $event
     *
     * @throws AnimationException
     */
    public function onPlayerDropItem(PlayerDropItemEvent $event): void {
        if($event->isCancelled()) {
            return;
        }
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if(!$player->isLoaded()) {
            return;
        }
        $currentWindow = $player->getCurrentWindow();
        if($currentWindow instanceof InvMenuInventory) {
            return;
        }
        $item = $event->getItem();
        $wormholeManager = $this->core->getGameManager()->getWormholeManager();
        $session = $wormholeManager->getSession($player);
        $wormholes = $wormholeManager->getAllWormholes();
        foreach($wormholes as $wormhole) {
            if ($wormhole->canUse($player, $item)) {
                $rarityLimit = $wormhole->getRarityLimit();
                if ($player->getDataSession()->getExecutiveBoostTimeLeft() > 0) {
                    $rarityLimit = Enchantment::EXECUTIVE;
                }
                $event->cancel();
                if ($session !== null) {
                    $task = $session->getSpawnOptionsTask();
                    if ($task !== null) {
                        if ($task->getHandler() !== null and Nexus::getInstance()->getScheduler()->isQueued($task->getHandler())) {
                            $player->sendTitleTo(TextFormat::BOLD . TextFormat::RED . "NOTICE", TextFormat::GRAY . "Your options are still rolling");
                            $player->playErrorSound();
                            return;
                        }
                    } else {
                        $player->sendTitleTo(TextFormat::BOLD . TextFormat::RED . "NOTICE", TextFormat::GRAY . "Slow down");
                        $player->playErrorSound();
                        return;
                    }
                    if (EnchantmentReroll::isInstanceOf($item)) {
                        if (!$session->getItem() instanceof Pickaxe) {
                            $player->sendTitleTo(TextFormat::BOLD . TextFormat::RED . "NOTICE", TextFormat::GRAY . "You're not enchanting a pickaxe");
                            $player->playErrorSound();
                            return;
                        }
                        $player->getInventory()->removeItem($item->setCount(1));
                        $player->getArmorInventory()->removeItem($item->setCount(1));
                        $player->getCursorInventory()->removeItem($item->setCount(1));
                        $player->getCraftingGrid()->removeItem($item->setCount(1));
                        $player->getWorld()->addSound($player->getPosition(), new PopSound(), [$player]);
                        $session->setEnchantments();
                        return;
                    }
                    if (EnchantmentDust::isInstanceOf($item)) {
                        if (!$session->getItem() instanceof Pickaxe) {
                            $player->sendTitleTo(TextFormat::BOLD . TextFormat::RED . "NOTICE", TextFormat::GRAY . "You're not enchanting a pickaxe");
                            $player->playErrorSound();
                            return;
                        }
                        $dust = EnchantmentDust::fromItem($item);
                        $add = $dust->getSuccess();
                        $changed = false;
                        $entities = $session->getEntities();
                        $newEntities = [];
                        foreach ($entities as $entity) {
                            if ($entity->getSuccess() < 100) {
                                $entity->updateTag(min(100, $entity->getSuccess() + $add));
                                $changed = true;
                            }
                            $newEntities[] = $entity;
                        }
                        if ($changed === false) {
                            $player->sendTitleTo(TextFormat::BOLD . TextFormat::RED . "NOTICE", TextFormat::GRAY . "All enchants have maxed success rates");
                            $player->playErrorSound();
                            return;
                        }
                        $session->setEntities($entities);
                        $chances = [];
                        foreach ($newEntities as $newEntity) {
                            $chances[EnchantmentIdMap::getInstance()->toId($newEntity->getEnchantment()->getType())] = $newEntity->getSuccess();
                        }
                        $session->setChances($chances);
                        $player->getInventory()->removeItem($item->setCount(1));
                        $player->getArmorInventory()->removeItem($item->setCount(1));
                        $player->getCursorInventory()->removeItem($item->setCount(1));
                        $player->getCraftingGrid()->removeItem($item->setCount(1));
                        $player->playOrbSound();
                        return;
                    }
                    if (EnchantmentOrb::isInstanceOf($item)) {
                        $pickaxe = $session->getItem();
                        if (!$pickaxe instanceof Pickaxe) {
                            $player->sendTitleTo(TextFormat::BOLD . TextFormat::RED . "NOTICE", TextFormat::GRAY . "You're not enchanting a pickaxe");
                            $player->playErrorSound();
                            return;
                        }
                        $orb = EnchantmentOrb::fromItem($item);
                        $enchantment = $orb->getEnchantment();
                        if (($enchantment->getLevel() - $pickaxe->getEnchantmentLevel($enchantment->getType())) == 1) {
                            if (!EnchantmentManager::canEnchant($pickaxe, $enchantment->getType())) {
                                $player->sendTitleTo(TextFormat::BOLD . TextFormat::RED . "NOTICE", TextFormat::GRAY . "Cannot be applied to this tool");
                                $player->playErrorSound();
                                return;
                            }
                            $ev = new EnchantmentOrbUseEvent($player, $enchantment);
                            $ev->call();
                            $session->enchant($enchantment, $orb->getSuccess());
                            $player->getInventory()->removeItem($item->setCount(1));
                            $player->getArmorInventory()->removeItem($item->setCount(1));
                            $player->getCursorInventory()->removeItem($item->setCount(1));
                            $player->getCraftingGrid()->removeItem($item->setCount(1));
                        } else {
                            $level = $enchantment->getLevel() - 1;
                            $player->sendTitleTo(TextFormat::BOLD . TextFormat::RED . "NOTICE", TextFormat::GRAY . "You must have " . $enchantment->getType()->getName() . " " . EnchantmentManager::getRomanNumber($level));
                            $player->playErrorSound();
                        }
                        return;
                    }
                    $player->sendTitleTo(TextFormat::BOLD . TextFormat::RED . "NOTICE", TextFormat::GRAY . "Invalid item");
                    $player->playErrorSound();
                    return;
                }
                if (!$item instanceof Pickaxe and !Satchel::isInstanceOf($item)) {
                    if ((!$item instanceof Armor) and (!$item instanceof Sword) and (!$item instanceof Axe)) {
                        if (EnchantmentBook::isInstanceOf($item)) {
                            $book = EnchantmentBook::fromItem($item);
                            $energy = $book->getEnergy();
                            /** @var Enchantment $enchantment */
                            $enchantment = $book->getEnchantment()->getType();
                            $level = $book->getEnchantment()->getLevel();
                            $exec = EnchantmentManager::getExecutiveEnchantmentByPremature($enchantment);
                            $checkPermission = $player->getDataSession()->getExecutiveBoostTimeLeft() <= 0 && $rarityLimit < Enchantment::EXECUTIVE;
                            if ($exec === null or $checkPermission) {
                                if ($level >= $enchantment->getMaxLevel()) {
                                    // TODO: Executive overload here
                                    $player->sendTitleTo(TextFormat::BOLD . TextFormat::RED . "NOTICE", TextFormat::GRAY . "Your book is at the max level");
                                    $player->playErrorSound();
                                    return;
                                }
                            }
                            $enchantmentInstance = new EnchantmentInstance($enchantment, $level);
                            $needed = EnchantmentManager::getNeededEnergy($enchantmentInstance);
                            if ($energy >= $needed) {
                                $player->getInventory()->removeItem($item->setCount(1));
                                $player->getArmorInventory()->removeItem($item->setCount(1));
                                $player->getCursorInventory()->removeItem($item->setCount(1));
                                $player->getCraftingGrid()->removeItem($item->setCount(1));
                                $wormholeManager->addSession(new WormholeSession($player, $wormhole, $item, $rarityLimit));
                            } else {
                                $player->sendTitleTo(TextFormat::BOLD . TextFormat::RED . "NOTICE", TextFormat::GRAY . "Your book must be fully charged");
                                $player->playErrorSound();
                            }
                            return;
                        }
                        $player->sendTitleTo(TextFormat::BOLD . TextFormat::RED . "NOTICE", TextFormat::GRAY . "Invalid item");
                        $player->playErrorSound();
                        return;
                    }
                    if ($item->getDamage() === 0) {
                        $player->sendTitleTo(TextFormat::BOLD . TextFormat::RED . "NOTICE", TextFormat::GRAY . "Full durability");
                        $player->playErrorSound();
                        return;
                    }
                }
                if (Satchel::isInstanceOf($item)) {
                    $satchel = Satchel::fromItem($item);
                    $levels = -1;
                    $maxed = 0;
                    foreach ($satchel->getEnchantments() as $enchantment) {
                        if ($enchantment->getLevel() >= $enchantment->getType()->getMaxLevel()) {
                            ++$maxed;
                        }
                        $levels += $enchantment->getLevel();
                    }
                    $points = (XPUtils::xpToLevel($satchel->getEnergy(), RPGManager::SATCHEL_MODIFIER)) - (50 + $levels);
                    if ($points <= 0 or $maxed >= 5) {
                        $player->playErrorSound();
                        $player->sendTitleTo(TextFormat::BOLD . TextFormat::RED . "NOTICE", TextFormat::GRAY . "You have no points");
                        return;
                    }
                }
                if ($item instanceof Pickaxe) {
                    if ($item->getPoints() <= 0) {
                        $player->playErrorSound();
                        $player->sendTitleTo(TextFormat::BOLD . TextFormat::RED . "NOTICE", TextFormat::GRAY . "You have no points");
                        return;
                    }
                }
                $player->getInventory()->removeItem($item->setCount(1));
                $player->getArmorInventory()->removeItem($item->setCount(1));
                $player->getCursorInventory()->removeItem($item->setCount(1));
                $player->getCraftingGrid()->removeItem($item->setCount(1));
                $wormholeManager->addSession(new WormholeSession($player, $wormhole, $item, $rarityLimit));
                return;
            }
        }
    }

    /**
     * @priority NORMAL
     * @param PlayerQuitEvent $event
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $wormholeManager = $this->core->getGameManager()->getWormholeManager();
        $session = $wormholeManager->getSession($player);
        if($session === null) {
            return;
        }
        $session->close();
    }

    /**
     * @priority NORMAL
     * @param EntityDamageByEntityEvent $event
     */
    public function onHurtEnderman(EntityDamageByEntityEvent $event) {
        $entity = $event->getEntity();
        $damager = $event->getDamager();
        if($entity instanceof ExecutiveEnderman && $damager instanceof NexusPlayer) {
            foreach ($event->getModifiers() as $type => $damage) {
                $event->setModifier(0, $type);
            }
            $event->setBaseDamage(1);
            if($entity->getHealth() - $event->getFinalDamage() <= 0) {
                $damager->getDataSession()->addToXP(mt_rand(15000, 20000));
                // I don't think enderman drops energy
                return;
            }
            if(mt_rand(1, 3) == 1) {
                $entity->generateRandomPosition();
                $entity->getWorld()->addParticle($entity->getPosition(), new EndermanTeleportParticle());
                $entity->getWorld()->addParticle($entity->getRandomPosition(), new EndermanTeleportParticle());
                $entity->teleport($entity->getRandomPosition());
            }
        }
    }

}