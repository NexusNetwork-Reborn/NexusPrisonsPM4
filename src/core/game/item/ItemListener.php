<?php
declare(strict_types=1);

namespace core\game\item;

use core\command\forms\ItemInformationForm;
use core\game\boop\task\DupeLogTask;
use core\game\boss\task\BossSummonTask;
use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\event\ShardFoundEvent;
use core\game\item\mask\Mask;
use core\game\item\sets\SetManager;
use core\game\item\sets\utils\SetUtils;
use core\game\item\types\custom\Lootbox;
use core\game\item\types\custom\Satchel;
use core\game\item\types\custom\Shard;
use core\game\item\types\custom\Trinket;
use core\game\item\types\CustomItem;
use core\game\item\types\Interactive;
use core\game\item\types\Rarity;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Pickaxe;
use core\game\plots\PlotManager;
use core\level\block\CrudeOre;
use core\level\block\Ore;
use core\level\block\OreGenerator;
use core\level\block\RushOreBlock;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rank\Rank;
use core\provider\event\PlayerLoadEvent;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\utils\Utils;
use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockFormEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\transaction\action\DropItemAction;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\sound\BlazeShootSound;
use pocketmine\world\sound\BlockBreakSound;

class ItemListener implements Listener {

    /** @var Nexus */
    private $core;

    /** @var int */
    private $itemCooldowns = [];

    /**
     * ItemListener constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
    }

    /**
     * @priority NORMAL
     * @param PlayerLoadEvent $event
     */
    public function onPlayerLoad(PlayerLoadEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $player->getCESession()->setActiveArmorEnchantments();
        $player->getCESession()->setActiveHeldItemEnchantments();
    }

    /**
     * @priority HIGHEST
     * @param PlayerDeathEvent $event
     */
    public function onPlayerDeath(PlayerDeathEvent $event): void {
        /** @var NexusPlayer $player */
        $player = $event->getPlayer();
        if(BossSummonTask::getBossFight() !== null && BossSummonTask::getBossFight()->inArena($player)){
            $event->setKeepInventory(true);
            BossSummonTask::getBossFight()->removePlayer($player);
            $event->getPlayer()->sendMessage(Translation::getMessage("defeatedByHades"));
        }
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $drops = $event->getDrops();
        if($player->isLoaded()) {
            $lastDeath = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
            $lastDeath->setName(TextFormat::AQUA . $player->getName() . " died at " . date("[n/j/Y][G:i:s]", time()));
            $lastDeath->setListener(InvMenu::readonly(function(DeterministicInvMenuTransaction $transaction): void {
                $itemClicked = $transaction->getItemClicked();
                /** @var NexusPlayer $player */
                $player = $transaction->getPlayer();
                if(!$itemClicked->isNull()) {
                    $player->removeCurrentWindow();
                    $player->sendDelayedForm(new ItemInformationForm($itemClicked));
                }
            }));
            $lastDeath->getInventory()->setContents($drops);
            $player->getCESession()->setLastDeath($lastDeath);
        }
        $inventory = $player->getInventory();
        $scroll = false;
        foreach($drops as $index => $drop) {
            if(Trinket::isInstanceOf($drop) and !$event->getKeepInventory()) {
                $trinket = Trinket::fromItem($drop);
                if($trinket->isWhitescroll()) {
                    unset($drops[$index]);
                    $trinket->setEnergy(0);
                    $trinket->setWhitescroll(false);
                    $this->core->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($inventory, $trinket): void {
                        $inventory->addItem($trinket->toItem());
                    }), 1);
                    $scroll = true;
                }
            }
            if($drop instanceof Pickaxe and $drop->isWhitescrolled() and !$event->getKeepInventory()) {
                $drop->setWhitescrolled(false);
                if($player->getDataSession()->getRank()->getIdentifier() >= Rank::EMPEROR_HEROIC) {
                    if(mt_rand(1, 5) === 1) {
                        $drop->setWhitescrolled(true);
                    }
                }
                $drop->subtractEnergy($drop->getMaxSubtractableEnergy());
                unset($drops[$index]);
                $this->core->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use($inventory, $drop): void {
                    $inventory->addItem($drop);
                }), 1);
                $scroll = true;
            }
            if(Satchel::isInstanceOf($drop)) {
                $satchel = Satchel::fromItem($drop);
                if($satchel->isWhitescroll() and !$event->getKeepInventory()) {
                    unset($drops[$index]);
                    $satchel->setAmount(0);
                    $satchel->setEnergy($satchel->getEnergy() - $satchel->getMaxSubtractableEnergy());
                    $satchel->setWhitescroll(false);
                    $this->core->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($inventory, $satchel): void {
                        $inventory->addItem($satchel->toItem());
                    }), 1);
                    $scroll = true;
                }
            }
        }
        $event->setDrops($drops);
        if($scroll) {
            $player->sendTranslatedMessage("savedByWhitescroll");
        }
    }

    /**
     * @priority LOWEST
     *
     * @param PlayerChatEvent $event
     */
    public function onPlayerChat(PlayerChatEvent $event) {
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $item = $player->getInventory()->getItemInHand();
        $name = TextFormat::RESET . TextFormat::WHITE . $item->getName();
        if($item->hasCustomName()) {
            $name = $item->getCustomName();
        }
        $message = $event->getMessage();
        if(strpos("[item]", $message) !== false) {
            $player->setItem($item);
        }
        if(strpos("[brag]", $message) !== false) {
            $brag = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
            $brag->setName(TextFormat::AQUA . $player->getName() . "'s Inventory");
            $brag->setListener(InvMenu::readonly(function(DeterministicInvMenuTransaction $transaction): void {
                $itemClicked = $transaction->getItemClicked();
                /** @var NexusPlayer $player */
                $player = $transaction->getPlayer();
                if(!$itemClicked->isNull()) {
                    $player->removeCurrentWindow();
                    $player->sendDelayedForm(new ItemInformationForm($itemClicked));
                }
            }));
            $inventory = [];
            foreach($player->getInventory()->getContents(true) as $slot => $item) {
                $inventory[$slot] = $item;
            }
            $map = [
                0 => 47,
                1 => 48,
                2 => 50,
                3 => 51
            ];
            foreach($player->getArmorInventory()->getContents() as $slot => $item) {
                $inventory[$map[$slot]] = $item;
            }
            $placeHolder = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::GRAY());
            $inventory[45] = $inventory[53] = $placeHolder->asItem()->setCustomName(" ");
            $inventory[46] = $placeHolder->asItem()->setCustomName(TextFormat::RESET . TextFormat::GRAY . "Helmet ->");
            $inventory[49] = $placeHolder->asItem()->setCustomName(TextFormat::RESET . TextFormat::GRAY . "<- Chestplate | Leggings ->");
            $inventory[52] = $placeHolder->asItem()->setCustomName(TextFormat::RESET . TextFormat::GRAY . "<- Boots");
            $brag->getInventory()->setContents($inventory);
            $player->setBrag($brag);
        }
        $replace = TextFormat::DARK_GRAY . "»" . $name . TextFormat::RESET . TextFormat::GRAY . TextFormat::BOLD . " * " . TextFormat::RESET . TextFormat::WHITE . $item->getCount() . TextFormat::DARK_GRAY . "«" . TextFormat::RESET . $player->getDataSession()->getRank()->getChatColor();
        $message = str_replace("[item]", $replace, $message);
        $replace = TextFormat::DARK_GRAY . "»" . TextFormat::WHITE . $player->getDataSession()->getTotalXPLevel() . TextFormat::RED . " Mining" . TextFormat::DARK_GRAY . "«" . TextFormat::RESET . $player->getDataSession()->getRank()->getChatColor();
        $message = str_replace("[mining]", $replace, $message);
        $replace = TextFormat::DARK_GRAY . "»" . TextFormat::AQUA . $player->getName() . "'s Inventory" . TextFormat::DARK_GRAY . "«" . TextFormat::RESET . $player->getDataSession()->getRank()->getChatColor();
        $message = str_replace("[brag]", $replace, $message);
        $replace = TextFormat::DARK_GRAY . "»" . TextFormat::GREEN . "$" . number_format($player->getDataSession()->getBalance(), 2) . TextFormat::DARK_GRAY . "«" . TextFormat::RESET . $player->getDataSession()->getRank()->getChatColor();
        $message = str_replace("[balance]", $replace, $message);
        $replace = TextFormat::DARK_GRAY . "»" . TextFormat::GOLD . $player->getDataSession()->getBlocksMined() . " Blocks" . TextFormat::DARK_GRAY . "«" . TextFormat::RESET . $player->getDataSession()->getRank()->getChatColor();
        $message = str_replace("[blocks]", $replace, $message);
        $event->setMessage($message);
    }

    /**
     * @priority NORMAL
     * @param PlayerItemHeldEvent $event
     */
    public function onPlayerItemHeld(PlayerItemHeldEvent $event) {
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if(!$player->isLoaded()) {
            return;
        }
        $player->getCESession()->resetActiveHeldItemEnchantments();
        $item = $event->getItem();
        if(!ItemManager::canUseTool($player, $item)) {
            $name = $item->hasCustomName() ? $item->getCustomName() : $item->getName();
            $level = ItemManager::getLevelToUseTool($item);
            $player->sendAlert(Translation::RED . "You need to be Level $level to use $name", 10);
            return;
        }
        if($item->getBlock() instanceof OreGenerator) {
            if($this->core->getGameManager()->getItemManager()->getItem($item) === null) {
                $player->getInventory()->removeItem($item);
                return;
            }
        }
        $player->getCESession()->setActiveHeldItemEnchantments();
    }

    /**
     * @priority HIGHEST
     *
     * @handleCancelled
     * @param PlayerInteractEvent $event
     *
     * @throws TranslationException
     */
    public function onPlayerInteract(PlayerInteractEvent $event): void {
        $item = $event->getItem();
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if(!$player->isLoaded()) {
            return;
        }
        $inventory = $player->getInventory();
        $tag = $item->getNamedTag()->getCompoundTag(CustomItem::CUSTOM);
        if($tag === null) {
            return;
        }
        if($tag instanceof CompoundTag) {
            if(isset($this->itemCooldowns[$player->getUniqueId()->toString()]) and (time() - $this->itemCooldowns[$player->getUniqueId()->toString()]) < 1) {
                $event->cancel();
                return;
            }
            $matchedItem = $this->core->getGameManager()->getItemManager()->getItem($item);
            if($matchedItem !== null and $matchedItem instanceof Interactive) {
                $uuid = $matchedItem->getUniqueId();
                if($uuid !== null and $this->core->getGameManager()->getItemManager()->isRedeemed($uuid)) {
//                    $player->kickDelay(Translation::getMessage("kickMessage", [
//                        "name" => TextFormat::RED . "BOOP",
//                        "reason" => TextFormat::YELLOW . "Possibility of being duped! Please trash this item!"
//                    ]));
                    $this->itemCooldowns[$player->getUniqueId()->toString()] = time();
                    if(isset($this->itemCooldowns[$player->getUniqueId()->toString()]) and (time() - $this->itemCooldowns[$player->getUniqueId()->toString()]) < 1) {
                        Server::getInstance()->getAsyncPool()->submitTaskToWorker(new DupeLogTask("[Prison]" . date("[n/j/Y][G:i:s]", time()) . " {$player->getName()} has a possibly duped item: " . TextFormat::clean($matchedItem->getName())), 1);
                    }
                    if($matchedItem instanceof Lootbox) {
                        $event->cancel();
                        return;
                    }
                }
                $event->cancel();
                $matchedItem->onInteract($player, $inventory, $item, $event->getFace(), $event->getBlock());
                $this->itemCooldowns[$player->getUniqueId()->toString()] = time();
            }
        }
    }

    /**
     * @priority HIGHEST
     *
     * @param PlayerItemUseEvent $event
     *
     * @throws TranslationException
     */
    public function onPlayerItemUse(PlayerItemUseEvent $event): void {
        $item = $event->getItem();
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if(!$player->isLoaded()) {
            return;
        }
        $inventory = $player->getInventory();
        $tag = $item->getNamedTag()->getCompoundTag(CustomItem::CUSTOM);
        if($tag === null) {
            return;
        }
        if($tag instanceof CompoundTag) {
            if(isset($this->itemCooldowns[$player->getUniqueId()->toString()]) and (time() - $this->itemCooldowns[$player->getUniqueId()->toString()]) < 1) {
                $event->cancel();
                return;
            }
            $matchedItem = $this->core->getGameManager()->getItemManager()->getItem($item);
            if($matchedItem !== null and $matchedItem instanceof Interactive) {
                $uuid = $matchedItem->getUniqueId();
                if($uuid !== null and $this->core->getGameManager()->getItemManager()->isRedeemed($uuid)) {
//                    $player->kickDelay(Translation::getMessage("kickMessage", [
//                        "name" => TextFormat::RED . "BOOP",
//                        "reason" => TextFormat::YELLOW . "Possibility of being duped! Please trash this item!"
//                    ]));
                    $this->itemCooldowns[$player->getUniqueId()->toString()] = time();
                    if(isset($this->itemCooldowns[$player->getUniqueId()->toString()]) and (time() - $this->itemCooldowns[$player->getUniqueId()->toString()]) < 1) {
                        Server::getInstance()->getAsyncPool()->submitTaskToWorker(new DupeLogTask("[Prison]" . date("[n/j/Y][G:i:s]", time()) . " {$player->getName()} has a possibly duped item: " . TextFormat::clean($matchedItem->getName())), 1);
                    }
                    if($matchedItem instanceof Lootbox) {
                        $event->cancel();
                        return;
                    }
                }
                $event->cancel();
                $matchedItem->execute($player, $inventory, $item);
                $this->itemCooldowns[$player->getUniqueId()->toString()] = time();
            }
        }
    }

//    /**
//     * @priority HIGHEST
//     *
//     * @handleCancelled
//     * @param BlockPlaceEvent $event
//     *
//     * @throws TranslationException
//     */
//    public function onPlayerBlockPlace(BlockPlaceEvent $event) {
//        $item = $event->getItem();
//        $player = $event->getPlayer();
//        if(!$player instanceof NexusPlayer) {
//            return;
//        }
//        if(!$player->isLoaded()) {
//            return;
//        }
//        $inventory = $player->getInventory();
//        $tag = $item->getNamedTag()->getCompoundTag(CustomItem::CUSTOM);
//        if($tag === null) {
//            return;
//        }
//        if($tag instanceof CompoundTag) {
//            if(isset($this->itemCooldowns[$player->getUniqueId()->toString()]) and (time() - $this->itemCooldowns[$player->getUniqueId()->toString()]) < 1) {
//                $event->cancel();
//                return;
//            }
//            $matchedItem = $this->core->getGameManager()->getItemManager()->getItem($item);
//            if($matchedItem !== null and $matchedItem instanceof Interactive) {
//                $uuid = $matchedItem->getUniqueId();
//                if($uuid !== null and $this->core->getGameManager()->getItemManager()->isRedeemed($uuid)) {
////                    $player->kickDelay(Translation::getMessage("kickMessage", [
////                        "name" => TextFormat::RED . "BOOP",
////                        "reason" => TextFormat::YELLOW . "Possibility of being duped! Please trash this item!"
////                    ]));
//                    $this->itemCooldowns[$player->getUniqueId()->toString()] = time();
//                    if(isset($this->itemCooldowns[$player->getUniqueId()->toString()]) and (time() - $this->itemCooldowns[$player->getUniqueId()->toString()]) < 1) {
//                        Server::getInstance()->getAsyncPool()->submitTaskToWorker(new DupeLogTask("[Prison]" . date("[n/j/Y][G:i:s]", time()) . " {$player->getName()} has a possibly duped item: " . TextFormat::clean($matchedItem->getName())), 1);
//                    }
//                    if($matchedItem instanceof Lootbox) {
//                        $event->cancel();
//                        return;
//                    }
//                }
//                $event->cancel();
//                $matchedItem->onInteract($player, $inventory, $item, 0, $event->getBlockReplaced());
//                $this->itemCooldowns[$player->getUniqueId()->toString()] = time();
//            }
//        }
//    }

    /**
     * @priority NORMAL
     * @param PlayerExhaustEvent $event
     */
    public function onPlayerExhaust(PlayerExhaustEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if(!$player->isTagged()) {
            $event->cancel();
            return;
        }
    }

    /**
     * @priority HIGHEST
     * @param BlockBreakEvent $event
     */
    public function onBlockBreak(BlockBreakEvent $event): void {
        if($event->isCancelled()) {
            return;
        }
        $block = $event->getBlock();
        if(!($block instanceof Ore) && !($block instanceof CrudeOre)) {
            return;
        }
        $item = $event->getItem();
        $chance = 850;
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if($item->hasEnchantment(EnchantmentManager::getEnchantment(Enchantment::SHARD_DISCOVERER))) {
            $chance -= (($item->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::SHARD_DISCOVERER)) * 25) * $player->getCESession()->getItemLuckModifier());
        }
        $chance = (int)$chance;
        if($player->getCESession()->hasExplode()) {
            $chance *= 2;
        }
        if($item instanceof Pickaxe) {
            $chance /= 1 + $item->getAttribute(Pickaxe::SHARD_MASTERY);
        }
        if(PlotManager::isPlotWorld($player->getWorld())) {
            $chance *= 1.25;
        }
        $helmet = $player->getArmorInventory()->getHelmet();
        if($helmet instanceof Armor) {
            if($helmet->hasMask(Mask::PILGRIM)) {
                $chance *= 0.5;
            }
        }
        $chance = (int)round($chance);
        if(4 === mt_rand(1, $chance)) {
            $level = $player->getDataSession()->getTotalXPLevel();
            if($level < 10) {
                $rarity = Rarity::SIMPLE;
            }
            elseif($level < 30) {
                $rarity = Rarity::UNCOMMON;
            }
            elseif($level < 50) {
                $rarity = Rarity::ELITE;
            }
            elseif($level < 70) {
                $rarity = Rarity::ULTIMATE;
            }
            elseif($level < 90) {
                $rarity = Rarity::LEGENDARY;
            }
            else {
                $rarity = Rarity::GODLY;
            }
            $item = (new Shard($rarity))->toItem();
            if($item->hasEnchantment(EnchantmentManager::getEnchantment(Enchantment::SHARD_DISCOVERER))) {
                $max = (int)ceil(2 * (1 + ($item->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::SHARD_DISCOVERER)) * 0.25)));
                $item->setCount(mt_rand(1, $max));
            }
            else {
                $item->setCount(1);
            }
            $name = TextFormat::RESET . TextFormat::WHITE . $item->getName();
            if($item->hasCustomName()) {
                $name = $item->getCustomName();
            }
            $event = new ShardFoundEvent($player, $item->getCount());
            $event->call();
            $name .= TextFormat::RESET . TextFormat::GRAY . " * " . TextFormat::WHITE . $item->getCount();
            $player->sendTitle(TextFormat::GOLD . "Discovered", $name);
            $player->playBlastSound();
            $player->getInventory()->addItem($item);
        }
    }

    /**
     * @priority HIGHEST
     * @param BlockBreakEvent $event
     */
    public function onBlockBreak2(BlockBreakEvent $event): void {
        if($event->isCancelled()) {
            return;
        }
        $block = $event->getBlock();
        if($block instanceof Ore) {
            return;
        }
        $item = $event->getItem();
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if($item instanceof Pickaxe) {
            if($item->getAttribute(Pickaxe::ORE_EXTRACTOR) <= 0) {
                return;
            }
            $chance = $item->getAttribute(Pickaxe::ORE_EXTRACTOR) * 1000;
        }
        else {
            return;
        }
        $chance = (int)round($chance);
        if($chance === mt_rand(1, 1000000)) {
            $item = (new types\custom\OreGenerator($block))->toItem()->setCount(1);
            $name = TextFormat::RESET . TextFormat::WHITE . $item->getName();
            if($item->hasCustomName()) {
                $name = $item->getCustomName();
            }
            $name .= TextFormat::RESET . TextFormat::GRAY . " * " . TextFormat::WHITE . $item->getCount();
            $player->sendTitle(TextFormat::GOLD . "Discovered", $name);
            $player->playBlastSound();
            $player->getInventory()->addItem($item);
        }
    }

//    /**
//     * @priority HIGHEST
//     * @param BlockBreakEvent $event
//     */
//    public function onBreakOreRush(BlockBreakEvent $event) {
//        $block = $event->getBlock();
//        if($block instanceof RushOreBlock) {
//            $event->cancel();
//            $event->getPlayer()->getWorld()->addSound($block->getPosition(), new BlockBreakSound($block));
//            $event->getPlayer()->getWorld()->addParticle($block->getPosition(), new BlockBreakParticle($block));
//            $block->onBreak($event->getItem(), $event->getPlayer());
//        }
//    }

    /**
     * @priority LOW
     * @param BlockFormEvent $event
     */
    public function onBlockForm(BlockFormEvent $event): void {
        $block = $event->getNewState();
        if($block->getId() === BlockLegacyIds::OBSIDIAN) {
            return;
        }
        if($block->getId() === BlockLegacyIds::COBBLESTONE) {
            $event->cancel();
        }
    }

    /**
     * @priority NORMAL
     *
     * @param InventoryTransactionEvent $event
     */
    public function onInventoryTransaction(InventoryTransactionEvent $event) : void{
        $transaction = $event->getTransaction();
        $player = $transaction->getSource();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        foreach($transaction->getActions() as $action) {
            if($action instanceof SlotChangeAction) {
                $sourceItem = $action->getSourceItem();
                $targetItem = $action->getTargetItem();
                if($action->getInventory() instanceof ArmorInventory) {
                    if(!ItemManager::canUseTool($player, $targetItem)) {
                        $name = $targetItem->hasCustomName() ? $targetItem->getCustomName() : $targetItem->getName();
                        $level = ItemManager::getLevelToUseTool($targetItem);
                        $player->sendAlert(Translation::RED . "You need to be Level $level to use $name", 10);
                        $event->cancel();
                        return;
                    }
                    else {
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
                $tag = $sourceItem->getNamedTag()->getCompoundTag(CustomItem::CUSTOM);
                if(!isset($itemClickedWith)) {
                    if($tag instanceof CompoundTag) {
                        $itemClickedWith = $sourceItem;
                        $itemClickedWithAction = $action;
                    }
                    continue;
                }
                $itemChanged = $sourceItem;
                $itemChangedAction = $action;
            }
        }
        if(isset($itemClickedWith, $itemClickedWithAction, $itemChanged, $itemChangedAction)) {
            $item = $itemClickedWith;
            $tag = $item->getNamedTag()->getCompoundTag(CustomItem::CUSTOM);
            if($tag === null) {
                return;
            }
            if($tag instanceof CompoundTag) {
                $matchedItem = $this->core->getGameManager()->getItemManager()->getItem($item);
                if($matchedItem !== null and $matchedItem instanceof Interactive) {
                    $uuid = $matchedItem->getUniqueId();
                    if($uuid !== null and $this->core->getGameManager()->getItemManager()->isRedeemed($uuid)) {
//                        $player->kickDelay(Translation::getMessage("kickMessage", [
//                            "name" => TextFormat::RED . "BOOP",
//                            "reason" => TextFormat::YELLOW . "Possibility of being duped! Please trash this item!"
//                        ]));
                        Server::getInstance()->getAsyncPool()->submitTaskToWorker(new DupeLogTask("[Prison]" . date("[n/j/Y][G:i:s]", time()). " {$player->getName()} has a possibly duped item: " . TextFormat::clean($matchedItem->getName())), 1);
                        return;
                    }
                    if(!$matchedItem->onCombine($player, $itemClickedWith, $itemChanged, $itemClickedWithAction, $itemChangedAction)) {
                        $event->cancel();
                    }
                }
            }
        }
    }
}