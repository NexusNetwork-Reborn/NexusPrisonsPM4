<?php

declare(strict_types = 1);

namespace core\game\item\enchantment;

use core\game\item\enchantment\types\armor\GodlyOverloadEnchantment;
use core\game\item\enchantment\types\armor\OverloadEnchantment;
use core\game\item\mask\Mask;
use core\game\item\sets\Set;
use core\game\item\sets\SetManager;
use core\game\item\sets\utils\SetUtils;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Axe;
use core\game\item\types\vanilla\Bow;
use core\game\item\types\vanilla\Pickaxe;
use core\game\item\types\vanilla\Sword;
use core\level\LevelManager;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityEffectAddEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\InventoryContentPacket;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

class EnchantmentListener implements Listener {

    /** @var Nexus */
    private $core;

    /** @var int */
    private $lastAttack = [];

    /**
     * EnchantmentListener constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
    }

    /**
     * @priority HIGH
     * @param EntityDamageEvent $event
     */
    public function onEntityDamage(EntityDamageEvent $event) {
        if($event->isCancelled()) {
            return;
        }
        $entity = $event->getEntity();
        $damage = $event->getBaseDamage();
        if($event instanceof EntityDamageByEntityEvent) {
            $damager = $event->getDamager();
            if(!$damager instanceof NexusPlayer) {
                return;
            }
            if($damager->isLoaded()) {
                $damagerUuid = $damager->getUniqueId()->toString();
                if(isset($this->lastAttack[$damagerUuid])) {
                    $ticks = $this->lastAttack[$damagerUuid];
                    if(($this->core->getServer()->getTick() - $ticks) < 10) {
                        $event->cancel();
                        return;
                    }
                    else {
                        $this->lastAttack[$damagerUuid] = $this->core->getServer()->getTick();
                    }
                }
                else {
                    $this->lastAttack[$damagerUuid] = $this->core->getServer()->getTick();
                }
                $helmet = $damager->getArmorInventory()->getHelmet();
                if($helmet instanceof Armor) {
                    if($helmet->hasMask(Mask::PRISONER)) {
                        $damage *= 1.05;
                    }
                }
                if($damager->getCESession()->isDominated()) {
                    $damage *= 0.85;
                }
                if($entity instanceof NexusPlayer) {
                    if($entity->isLoaded()) {
                        if($entity->getCESession()->isCursed()) {
                            $damage *= 1.25;
                        }
                    }
                }
                $enchantments = $damager->getCESession()->getActiveEnchantments();
                if(isset($enchantments[Enchantment::DAMAGE])) {
                    /** @var EnchantmentInstance $enchantment */
                    foreach($enchantments[Enchantment::DAMAGE] as $enchantment) {
                        if($event->isCancelled()) {
                            return;
                        }
                        /** @var Enchantment $type */
                        $type = $enchantment->getType();
                        $callable = $type->getCallable();
                        $callable($event, $enchantment->getLevel(), $damage);
                    }
                }
            }
            if($entity instanceof NexusPlayer) {
                if($entity->isLoaded()) {
                    if($entity->getCESession()->getFrenzyHits() > 0) {
                        $entity->getCESession()->resetFrenzyHits();
                    }

                    $enchantments = $entity->getCESession()->getActiveEnchantments();
                    if(isset($enchantments[Enchantment::DAMAGE_BY])) {
                        /** @var EnchantmentInstance $enchantment */
                        foreach($enchantments[Enchantment::DAMAGE_BY] as $enchantment) {
                            if($event->isCancelled()) {
                                return;
                            }

                            $skip = false;

                            if($damager instanceof NexusPlayer && SetUtils::isWearingFullSet($damager, "plaguedoctor") && $damager->getInventory()->getItemInHand()->getNamedTag()->getString(SetManager::SET, "") === "plaguedoctor") {
                                $hits = $damager->getCESession()->getBypassHits();
                                $cap = 37.5;
                                $percent = round($hits * 0.015);
                                $percent = $percent < $cap ? $percent : $cap;

                                if(mt_rand(1, 100) <= $percent) {
                                    $damager->sendPopupTo(TextFormat::GRAY . TextFormat::BOLD . "* PLAGUE DOCTOR [" . TextFormat::RESET . TextFormat::RED . "Blocked " . $enchantment->getType()->getName() . TextFormat::BOLD . TextFormat::GRAY . "] *");
                                    $skip = true;
                                }
                            }

                            if($damager instanceof NexusPlayer && $damager->getInventory()->getItemInHand()->getEnchantmentLevel(EnchantmentManager::getEnchantment(EnchantmentIdentifiers::PERFECT_STRIKE)) > 0) {
                                $random = mt_rand(1, 500);
                                $chance = $damager->getInventory()->getItemInHand()->getEnchantmentLevel(EnchantmentManager::getEnchantment(EnchantmentIdentifiers::PERFECT_STRIKE)) * $damager->getCESession()->getItemLuckModifier();

                                if($chance > $random) {
                                    $damager->sendPopupTo(TextFormat::GRAY . TextFormat::BOLD . "* PERFECT STRIKE [" . TextFormat::RESET . TextFormat::RED . "Blocked " . $enchantment->getType()->getName() . TextFormat::BOLD . TextFormat::GRAY . "] *");
                                    $skip = true;
                                }
                            }

                            if($skip) break;

                            /** @var Enchantment $type */
                            $type = $enchantment->getType();
                            $callable = $type->getCallable();
                            $callable($event, $enchantment->getLevel(), $damage);
                        }
                    }
                }
            }
        }
        if($entity instanceof NexusPlayer) {
            if($entity->isLoaded()) {
                if($entity->getCESession()->isWeakened()) {
                    $damage *= 1.05;
                }
                $event->setBaseDamage($damage);
                $enchantments = $entity->getCESession()->getActiveEnchantments();
                if(isset($enchantments[Enchantment::DAMAGE_BY_ALL])) {
                    /** @var EnchantmentInstance $enchantment */
                    foreach($enchantments[Enchantment::DAMAGE_BY_ALL] as $enchantment) {
                        if($event->isCancelled()) {
                            return;
                        }
                        /** @var Enchantment $type */
                        $type = $enchantment->getType();
                        $callable = $type->getCallable();
                        $callable($event, $enchantment->getLevel(), $damage);
                    }
                }
                $event->setBaseDamage($damage);
            }
        }
        if($event->isCancelled()) {
            return;
        }
    }

    /**
     * @priority HIGHEST
     * @param EntityEffectAddEvent $event
     */
    public function onEntityEffectAdd(EntityEffectAddEvent $event) {
        $entity = $event->getEntity();
        if(!$entity instanceof NexusPlayer) {
            return;
        }
        if($event->isCancelled()) {
            return;
        }
        if(!$entity->isLoaded()) {
            return;
        }
        $enchantments = $entity->getCESession()->getActiveEnchantments();
        if(!isset($enchantments[Enchantment::EFFECT_ADD])) {
            return;
        }
        /** @var EnchantmentInstance $enchantment */
        foreach($enchantments[Enchantment::EFFECT_ADD] as $enchantment) {
            /** @var Enchantment $type */
            $type = $enchantment->getType();
            $callable = $type->getCallable();
            $callable($event, $enchantment->getLevel());
        }
    }

    /**
     * @priority HIGHEST
     * @param EntityShootBowEvent $event
     */
    public function onEntityShootBow(EntityShootBowEvent $event) {
        if($event->isCancelled()) {
            return;
        }
        $entity = $event->getEntity();
        if(!$entity instanceof NexusPlayer) {
            return;
        }
        if(!$entity->isLoaded()) {
            return;
        }
        $enchantments = $entity->getCESession()->getActiveEnchantments();
        if(!isset($enchantments[Enchantment::SHOOT])) {
            return;
        }
        /** @var EnchantmentInstance $enchantment */
        foreach($enchantments[Enchantment::SHOOT] as $enchantment) {
            /** @var Enchantment $type */
            $type = $enchantment->getType();
            $callable = $type->getCallable();
            $callable($event, $enchantment->getLevel());
        }
    }

    /**
     * @priority HIGH
     * @param PlayerDeathEvent $event
     */
    public function onPlayerDeath(PlayerDeathEvent $event) {
        $player = $event->getPlayer();
        $cause = $player->getLastDamageCause();
        if($cause instanceof EntityDamageByEntityEvent) {
            $damager = $cause->getDamager();
            if($player instanceof NexusPlayer) {
                if($player->isLoaded()) {
                    $enchantments = $player->getCESession()->getActiveEnchantments();
                    if(isset($enchantments[Enchantment::DEATH])) {
                        /** @var EnchantmentInstance $enchantment */
                        foreach($enchantments[Enchantment::DEATH] as $enchantment) {
                            /** @var Enchantment $type */
                            $type = $enchantment->getType();
                            $callable = $type->getCallable();
                            $callable($event, $enchantment->getLevel());
                        }
                    }
                }
            }
            if($damager instanceof NexusPlayer) {
                if($damager->isLoaded()) {
                    $enchantments = $damager->getCESession()->getActiveEnchantments();
                    if(isset($enchantments[Enchantment::KILL])) {
                        /** @var EnchantmentInstance $enchantment */
                        foreach($enchantments[Enchantment::KILL] as $enchantment) {
                            /** @var Enchantment $type */
                            $type = $enchantment->getType();
                            $callable = $type->getCallable();
                            $callable($event, $enchantment->getLevel());
                        }
                    }
                }
            }
        }
    }

    /**
     * @priority HIGHEST
     * @param PlayerMoveEvent $event
     */
    public function onPlayerMove(PlayerMoveEvent $event) {
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
        $enchantments = $player->getCESession()->getActiveEnchantments();
        if(!isset($enchantments[Enchantment::MOVE]) && !SetUtils::isWearingFullSet($player, "yeti") && !SetUtils::isWearingFullSet($player, "demolition") && !SetUtils::isWearingFullSet($player, "plaguedoctor")) {
            if($player->getMaxHealth() !== 20) {
                $player->setMaxHealth(20);
            }
            return;
        }
        $overload = false;
        /** @var Armor $helm */
        $helm = $event->getPlayer()->getArmorInventory()->getHelmet();
        if(isset($enchantments[Enchantment::MOVE])) {
            /** @var EnchantmentInstance $enchantment */
            foreach($enchantments[Enchantment::MOVE] as $enchantment) {
                if(EnchantmentIdMap::getInstance()->toId($enchantment->getType()) === Enchantment::OVERLOAD and (!$player->getCESession()->isHexCursed())) {
                    $level = min(4, $enchantment->getLevel());
                    $player = $event->getPlayer();
                    $expect = (20 + ($level * 4));
                    if($helm instanceof Armor) $expect += ($helm->hasMask(Mask::BUFF) ? 2 : 0);
                    if(SetUtils::isWearingFullSet($player, "yeti") || SetUtils::isWearingFullSet($player, "demolition")) $expect += 8;
                    if(SetUtils::isWearingFullSet($player, "plaguedoctor")) $expect += 5;
                    if($player->getMaxHealth() !== $expect) {
                        $player->setMaxHealth($expect);
                    }
                    $overload = true;
                }
                if(EnchantmentIdMap::getInstance()->toId($enchantment->getType()) === Enchantment::GODLY_OVERLOAD and (!$player->getCESession()->isHexCursed())) {
                    $level = min(3, $enchantment->getLevel());
                    $player = $event->getPlayer();
                    $expect = (36 + ($level * 4));
                    if($helm instanceof Armor) $expect += ($helm->hasMask(Mask::BUFF) ? 2 : 0);
                    if(SetUtils::isWearingFullSet($player, "yeti") || SetUtils::isWearingFullSet($player, "demolition")) $expect += 8;
                    if(SetUtils::isWearingFullSet($player, "plaguedoctor")) $expect += 5;
                    if($player->getMaxHealth() !== $expect) {
                        $player->setMaxHealth($expect);
                    }
                    $overload = true;
                }
                /** @var Enchantment $type */
                $type = $enchantment->getType();
                $callable = $type->getCallable();
                $callable($event, $enchantment->getLevel());
            }
        }
        if((SetUtils::isWearingFullSet($player, "yeti") || SetUtils::isWearingFullSet($player, "demolition")) && (!$overload)) $player->setMaxHealth(28);
        if(SetUtils::isWearingFullSet($player, "plaguedoctor") && (!$overload)) $player->setMaxHealth(25);
        if(($player->getMaxHealth() !== 20 and (!$overload)) && !SetUtils::isWearingFullSet($player, "yeti") && !SetUtils::isWearingFullSet($player, "demolition") && !SetUtils::isWearingFullSet($player, "plaguedoctor")) {
            $player->setMaxHealth(20);
        }
    }

    /**
     * @param PlayerRespawnEvent $event
     */
    public function onPlayerRespawn(PlayerRespawnEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if($player->isLoaded()) {
            $this->core->getScheduler()->scheduleDelayedTask(new class($player) extends Task {

                /** @var NexusPlayer */
                private $player;

                /**
                 *  constructor.
                 *
                 * @param NexusPlayer $player
                 */
                public function __construct(NexusPlayer $player) {
                    $this->player = $player;
                }

                /**
                 * @param int $currentTick
                 */
                public function onRun(): void {
                    if($this->player->isOnline() === false) {
                        return;
                    }
                    if($this->player->isLoaded()) {
                        $this->player->getCESession()->setActiveArmorEnchantments();
                        $this->player->getCESession()->reset();
                    }
                }
            }, 5);
        }
    }

    /**
     * @priority HIGHEST
     * @param PlayerItemUseEvent $event
     */
    public function onPlayerItemUse(PlayerItemUseEvent $event) {
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
        $enchantments = $player->getCESession()->getActiveEnchantments();
        if(!isset($enchantments[Enchantment::INTERACT])) {
            return;
        }
        /** @var EnchantmentInstance $enchantment */
        foreach($enchantments[Enchantment::INTERACT] as $enchantment) {
            /** @var Enchantment $type */
            $type = $enchantment->getType();
            $callable = $type->getCallable();
            $callable($event, $enchantment->getLevel());
        }
    }

    /**
     * @priority HIGHEST
     * @param BlockBreakEvent $event
     */
    public function onBlockBreak(BlockBreakEvent $event):void {
        if($event->isCancelled()) {
            return;
        }
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if(isset(LevelManager::$blockBreaks[$player->getXuid()]) && LevelManager::$blockBreaks[$player->getXuid()]->equals($event->getBlock()->getPosition())){
            unset(LevelManager::$blockBreaks[$player->getXuid()]);
            return;
        }
        if(!$player->isLoaded()) {
            return;
        }
        $enchantments = $player->getCESession()->getActiveEnchantments();
        if(!isset($enchantments[Enchantment::BREAK])) {
            return;
        }
        /** @var EnchantmentInstance $enchantment */
        foreach($enchantments[Enchantment::BREAK] as $enchantment) {
            /** @var Enchantment $type */
            $type = $enchantment->getType();
            $callable = $type->getCallable();
            $callable($event, $enchantment->getLevel());
        }
    }

    public function onDataPacketReceive(DataPacketReceiveEvent $event): void {
        $packet = $event->getPacket();
        if($packet instanceof InventoryTransactionPacket) {
            $transaction = $packet->trData;
            foreach($transaction->getActions() as $key => $action) {
                $action->oldItem = new ItemStackWrapper($action->oldItem->getStackId(), self::filterDisplayedEnchants($action->oldItem->getItemStack()));
                $action->newItem = new ItemStackWrapper($action->oldItem->getStackId(), self::filterDisplayedEnchants($action->newItem->getItemStack()));
            }
        }
        if($packet instanceof MobEquipmentPacket) {
            $packet->item = new ItemStackWrapper($packet->item->getStackId(), self::filterDisplayedEnchants($packet->item->getItemStack()));
        }
    }

    public function onDataPacketSend(DataPacketSendEvent $event): void {
        $packets = $event->getPackets();
        foreach($packets as $packet) {
            if($packet instanceof InventorySlotPacket) {
                $packet->item = new ItemStackWrapper($packet->item->getStackId(), self::displayEnchants($packet->item->getItemStack()));
            }
            if($packet instanceof InventoryContentPacket) {
                foreach($packet->items as $i => $item) {
                    $packet->items[$i] = new ItemStackWrapper($item->getStackId(), self::displayEnchants($item->getItemStack()));
                }
            }
        }
    }

    public static function displayEnchants(ItemStack $itemStack): ItemStack {
        /** @var TypeConverter $converter */
        $converter = TypeConverter::getInstance();
        $item = $converter->netItemStackToCore($itemStack);
        if($item instanceof Pickaxe or $item instanceof Armor or $item instanceof Bow or $item instanceof Sword or $item instanceof Axe) {
            if($item->getNamedTag()->getTag(Item::TAG_DISPLAY)) {
                $item->getNamedTag()->setTag("OriginalDisplayTag", $item->getNamedTag()->getTag(Item::TAG_DISPLAY)->safeClone());
            }
            $item = $item->setLore($item->getLoreForItem());
            $item = $item->setCustomName($item->getCustomNameForItem());
        }
        return $converter->coreItemStackToNet($item);
    }

    public static function filterDisplayedEnchants(ItemStack $itemStack): ItemStack {
        /** @var TypeConverter $converter */
        $converter = TypeConverter::getInstance();
        $item = $converter->netItemStackToCore($itemStack);
        if($item instanceof Pickaxe or $item instanceof Armor or $item instanceof Bow or $item instanceof Sword or $item instanceof Axe) {
            $tag = $item->getNamedTag();
            $tag->removeTag(Item::TAG_DISPLAY);
            if($tag->getTag("OriginalDisplayTag") instanceof CompoundTag) {
                $tag->setTag(Item::TAG_DISPLAY, $tag->getTag("OriginalDisplayTag"));
                $tag->removeTag("OriginalDisplayTag");
            }
            $item->setNamedTag($tag);
        }
        return $converter->coreItemStackToNet($item);
    }
//
//    /**
//     * @priority LOWEST
//     * @param InventoryTransactionEvent $event
//     *
//     * @throws TranslationException
//     */
//    public function onInventoryTransaction(InventoryTransactionEvent $event) {
//        $transaction = $event->getTransaction();
//        foreach($transaction->getActions() as $action) {
//            if($action instanceof SlotChangeAction and (!$action->getInventory() instanceof InvMenuInventory)) {
//                $sourceItem = $action->getSourceItem();
//                if($sourceItem->getId() === Item::ENCHANTED_BOOK) {
//                    $tag = $sourceItem->getNamedTag()->getCompoundTag(CustomItem::CUSTOM);
//                    if($tag instanceof CompoundTag) {
//                        $enchantmentBookAction = $action;
//                        $enchantment = Enchantment::getEnchantment($tag->getInt(EnchantmentBook::ENCHANTMENT));
//                    }
//                }
//                if($sourceItem->getId() === Item::END_CRYSTAL) {
//                    $tag = $sourceItem->getNamedTag()->getCompoundTag(CustomItem::CUSTOM);
//                    if($tag instanceof CompoundTag) {
//                        $enchantmentBookAction = $action;
//                        $enchantment = Enchantment::getEnchantment($tag->getInt(EnchantmentCrystal::ENCHANT));
//                        $level = $tag->getInt(EnchantmentCrystal::LEVEL);
//                        $success = $tag->getInt(EnchantmentCrystal::SUCCESS);
//                    }
//                }
//                elseif(!$sourceItem->isNull()) {
//                    $equipmentAction = $action;
//                }
//            }
//        }
//        $player = $transaction->getSource();
//        if(isset($enchantmentBookAction, $equipmentAction, $enchantment)) {
//            $book = $enchantmentBookAction->getSourceItem();
//            $equipment = $equipmentAction->getSourceItem();
//            if(ItemManager::canEnchant($equipment, $enchantment)) {
//                $enchantment = new EnchantmentInstance($enchantment);
//                if(isset($level)) {
//                    if($equipment->getEnchantmentLevel($enchantment->getId()) >= $level) {
//                        return;
//                    }
//                    $enchantment->setLevel($level);
//                }
//                else {
//                    if($equipment->hasEnchantment($enchantment->getId())) {
//                        $enchantment->setLevel($equipment->getEnchantmentLevel($enchantment->getId()) + 1);
//                    }
//                    else {
//                        $enchantment->setLevel(1);
//                    }
//                }
//                $event->setCancelled();
//                $enchantmentBookAction->getInventory()->removeItem($book);;
//                if(isset($success)) {
//                    if(mt_rand(1, 100) > $success) {
//                        $player->sendMessage(Translation::getMessage("enchantmentCrystalFail"));
//                        $player->getLevel()->addSound(new AnvilBreakSound($player));
//                        return;
//                    }
//                }
//                $player->getLevel()->addSound(new AnvilUseSound($player));
//                $equipmentAction->getInventory()->removeItem($equipment);
//                $equipment->addEnchantment($enchantment);
//                $equipmentAction->getInventory()->addItem(ItemManager::setLoreForItem($equipment));
//            }
//        }
//    }
//
//    /**
//     * @priority LOWEST
//     * @param InventoryTransactionEvent $event
//     */
//    public function onInventoryTransaction2(InventoryTransactionEvent $event) {
//        $transaction = $event->getTransaction();
//        foreach($transaction->getActions() as $action) {
//            if($action instanceof SlotChangeAction and (!$action->getInventory() instanceof InvMenuInventory)) {
//                $sourceItem = $action->getSourceItem();
//                if($sourceItem->getId() === Item::ENCHANTED_BOOK) {
//                    $tag = $sourceItem->getNamedTag()->getCompoundTag(CustomItem::CUSTOM);
//                    if($tag instanceof CompoundTag) {
//                        $enchantmentBookAction = $action;
//                        $enchantment = Enchantment::getEnchantment($tag->getInt(EnchantmentBook::ENCHANTMENT));
//                    }
//                }
//                elseif(!$sourceItem->isNull()) {
//                    $equipmentAction = $action;
//                }
//            }
//        }
//        /** @var NexusPlayer $player */
//        $player = $transaction->getSource();
//        if(isset($enchantmentBookAction, $equipmentAction, $enchantment)) {
//            $book = $enchantmentBookAction->getSourceItem();
//            $equipment = $equipmentAction->getSourceItem();
//            $tag = $equipment->getNamedTag()->getCompoundTag(CustomItem::CUSTOM);
//            if($tag instanceof CompoundTag) {
//                if($equipment->getId() === Item::END_CRYSTAL) {
//                    $equipmentEnchantment = $tag->getInt(EnchantmentCrystal::ENCHANT);
//                    if($enchantment->getId() === $equipmentEnchantment) {
//                        $equipmentLevel = $tag->getInt(EnchantmentCrystal::LEVEL);
//                        $equipmentSuccess = $tag->getInt(EnchantmentCrystal::SUCCESS);
//                        if($enchantment->getMaxLevel() <= $equipmentLevel) {
//                            return;
//                        }
//                        $event->setCancelled();
//                        $equipmentSuccess = ($equipmentSuccess > 10) ? ($equipmentSuccess - mt_rand(1, 10)) : 0;
//                        $enchantmentBookAction->getInventory()->removeItem($book);;
//                        $equipmentAction->getInventory()->removeItem($equipment);
//                        $equipmentAction->getInventory()->addItem((new EnchantmentCrystal($enchantment, $equipmentLevel + 1, $equipmentSuccess)));
//                        $player->playDingSound();
//                    }
//                }
//            }
//        }
//    }
//
//    /**
//     * @priority LOWEST
//     * @param InventoryTransactionEvent $event
//     */
//    public function onInventoryTransaction3(InventoryTransactionEvent $event) {
//        $transaction = $event->getTransaction();
//        foreach($transaction->getActions() as $action) {
//            if($action instanceof SlotChangeAction and (!$action->getInventory() instanceof InvMenuInventory)) {
//                $sourceItem = $action->getSourceItem();
//                if($sourceItem->getId() === Item::END_CRYSTAL) {
//                    $tag = $sourceItem->getNamedTag()->getCompoundTag(CustomItem::CUSTOM);
//                    if($tag instanceof CompoundTag) {
//                        $enchantmentBookAction = $action;
//                        $enchantment = Enchantment::getEnchantment($tag->getInt(EnchantmentCrystal::ENCHANT));
//                        $level = $tag->getInt(EnchantmentCrystal::LEVEL);
//                        $success = $tag->getInt(EnchantmentCrystal::SUCCESS);
//                    }
//                }
//                elseif(!$sourceItem->isNull()) {
//                    $equipmentAction = $action;
//                }
//            }
//        }
//        /** @var NexusPlayer $player */
//        $player = $transaction->getSource();
//        if(isset($enchantmentBookAction, $equipmentAction, $enchantment)) {
//            $book = $enchantmentBookAction->getSourceItem();
//            $equipment = $equipmentAction->getSourceItem();
//            $tag = $equipment->getNamedTag()->getCompoundTag(CustomItem::CUSTOM);
//            if($tag instanceof CompoundTag) {
//                if($equipment->getId() === Item::GLOWSTONE_DUST) {
//                    $equipmentGain = $tag->getInt(MythicalDust::GAIN);
//                    if($success >= 100) {
//                        return;
//                    }
//                    $event->setCancelled();
//                    $equipmentSuccess = (($success + $equipmentGain) >= 100) ? 100 : ($success + $equipmentGain);
//                    $enchantmentBookAction->getInventory()->removeItem($book);
//                    $equipmentAction->getInventory()->removeItem($equipment);
//                    $equipmentAction->getInventory()->addItem((new EnchantmentCrystal($enchantment, $level, $equipmentSuccess))->toItem());
//                    $player->playDingSound();
//                }
//            }
//        }
//    }
}