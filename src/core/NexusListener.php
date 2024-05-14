<?php
declare(strict_types=1);

namespace core;

use core\game\item\ItemManager;
use core\game\item\types\custom\SkinScroll;
use core\game\item\types\customies\DiamondSkinnedPickaxe;
use core\game\item\types\customies\DiamondSkinnedSword;
use core\game\item\types\customies\GoldSkinnedPickaxe;
use core\game\item\types\customies\GoldSkinnedSword;
use core\game\item\types\customies\IronSkinnedPickaxe;
use core\game\item\types\customies\IronSkinnedSword;
use core\game\item\types\customies\StoneSkinnedPickaxe;
use core\game\item\types\customies\StoneSkinnedSword;
use core\game\item\types\customies\WoodenSkinnedPickaxe;
use core\game\item\types\customies\WoodenSkinnedSword;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Axe;
use core\game\item\types\vanilla\Bow;
use core\game\item\types\vanilla\Pickaxe;
use core\game\item\types\vanilla\Sword;
use core\level\LevelException;
use core\player\NexusPlayer;
use core\player\rank\Rank;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\block\BlockLegacyIds;
use pocketmine\data\bedrock\EffectIdMap;
use pocketmine\data\bedrock\EffectIds;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\event\entity\EntityEffectRemoveEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\server\QueryRegenerateEvent;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Durable;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\ToolTier;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class NexusListener implements Listener {

    /** @var Nexus */
    private $core;

    /** @var int[] */
    private $chat = [];

    /** @var int[] */
    private $command = [];

    /** @var int[] */
    private $dropItem = [];

    /** @var string[] */
    private $lastMessage = [];

    /**
     * PlayerListener constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
    }

    /**
     * @priroity NORMAL
     *
     * @param PlayerDropItemEvent $event
     */
    public function onPlayerDropItem(PlayerDropItemEvent $event) {
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $wormholeManager = $this->core->getGameManager()->getWormholeManager();
        $wormholes = $wormholeManager->getAllWormholes();
        $item = $event->getItem();
        foreach($wormholes as $wormhole) {
            if ($wormhole->canUse($player, $item, false)) {
                if ($wormholeManager->getSession($player) === null) {
                    if ($item instanceof Pickaxe or $item instanceof Armor or $item instanceof Bow or $item instanceof Sword or $item instanceof Axe) {
                        return;
                    }
                } else {
                    return;
                }
            }
        }
        if(!isset($this->dropItem[$player->getUniqueId()->toString()])) {
            $this->dropItem[$player->getUniqueId()->toString()] = time();
            return;
        }
        if(time() - $this->dropItem[$player->getUniqueId()->toString()] >= 1) {
            $this->dropItem[$player->getUniqueId()->toString()] = time();
            return;
        }
        $event->cancel();
    }

    /**
     * @priroity LOWEST
     *
     * @param PlayerCommandPreprocessEvent $event
     *
     * @throws TranslationException
     */
    public function onPlayerCommandPreprocess(PlayerCommandPreprocessEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if(!$player->isLoaded()) {
            $event->cancel();
            return;
        }
        if($this->core->getServerManager()->getAnnouncementManager()->getRestarter()->getRestartProgress() > 5) {
            if($player->getDataSession()->getRank()->getIdentifier() === Rank::EXECUTIVE) {
                return;
            }
            if(strpos($event->getMessage(), "/") !== 0) {
                return;
            }
            if(!isset($this->command[$player->getUniqueId()->toString()])) {
                $this->command[$player->getUniqueId()->toString()] = time();
                return;
            }
            if(time() - $this->command[$player->getUniqueId()->toString()] >= 3) {
                $this->command[$player->getUniqueId()->toString()] = time();
                return;
            }
            $seconds = 3 - (time() - $this->command[$player->getUniqueId()->toString()]);
            $player->sendTranslatedMessage("actionCooldown", [
                "amount" => TextFormat::RED . $seconds
            ]);
            $event->cancel();
            return;
        }
        $event->cancel();
        $player->sendTranslatedMessage("restartingSoon");
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
        if(!$player->isLoaded()) {
            $event->cancel();
            return;
        }
        $message = $event->getMessage();
        if(isset($this->lastMessage[$player->getUniqueId()->toString()])) {
            $lastMessage = $this->lastMessage[$player->getUniqueId()->toString()];
            if(strtolower($message) === strtolower($lastMessage)) {
                $event->cancel();
                $player->sendTranslatedMessage("identicalMessage");
                return;
            }
        }
        if(!isset($this->chat[$player->getUniqueId()->toString()])) {
            $this->lastMessage[$player->getUniqueId()->toString()] = $message;
            $this->chat[$player->getUniqueId()->toString()] = time();
            return;
        }
        if(time() - $this->chat[$player->getUniqueId()->toString()] >= 2) {
            $this->lastMessage[$player->getUniqueId()->toString()] = $message;
            $this->chat[$player->getUniqueId()->toString()] = time();
            return;
        }
        $seconds = 2 - (time() - $this->chat[$player->getUniqueId()->toString()]);
        $player->sendTranslatedMessage("actionCooldown", [
            "amount" => TextFormat::RED . $seconds
        ]);
        $event->cancel();
    }

    /**
     * @param Armor $armor
     * @param NexusPlayer $player
     *
     * @throws LevelException
     */
    private function setArmorByType(Armor $armor, NexusPlayer $player): void {
        if(!ItemManager::canUseTool($player, $armor)) {
            $name = $armor->hasCustomName() ? $armor->getCustomName() : $armor->getName();
            $level = ItemManager::getLevelToUseTool($armor);
            $player->sendAlert(Translation::RED . "You need to be Level $level to use $name", 10);
            return;
        }
        if($armor->getArmorSlot() === Armor::SLOT_HEAD) {
            $copy = $player->getArmorInventory()->getHelmet();
            $player->getArmorInventory()->setHelmet($armor);
        }
        elseif($armor->getArmorSlot() === Armor::SLOT_CHESTPLATE) {
            $copy = $player->getArmorInventory()->getChestplate();
            $player->getArmorInventory()->setChestplate($armor);
        }
        elseif($armor->getArmorSlot() === Armor::SLOT_LEGGINGS) {
            $copy = $player->getArmorInventory()->getLeggings();
            $player->getArmorInventory()->setLeggings($armor);
        }
        elseif($armor->getArmorSlot() === Armor::SLOT_BOOTS) {
            $copy = $player->getArmorInventory()->getBoots();
            $player->getArmorInventory()->setBoots($armor);
        }
        if(isset($copy) and $copy) {
            $player->getInventory()->setItemInHand($copy);
        }
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

    /**
     * @param PlayerInteractEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onPlayerInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $action = $event->getAction();
        $item = $event->getItem();
        $block = $event->getBlock();
        if(($action === PlayerInteractEvent::RIGHT_CLICK_BLOCK) and ($item instanceof Armor) and $block->getId() !== BlockLegacyIds::ITEM_FRAME_BLOCK) {
            $this->setArmorByType($item, $player);
            $event->cancel();
        }
    }

    /**
     * @param PlayerItemUseEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onPlayerItemUse(PlayerItemUseEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $item = $event->getItem();
        if($item instanceof Armor) {
            $this->setArmorByType($item, $player);
            $event->cancel();
        }
    }

    /**
     * @priority HIGHEST
     *
     * @param EntityEffectRemoveEvent $event
     */
    public function onEntityEffectRemove(EntityEffectRemoveEvent $event): void {
        $effect = $event->getEffect();
        if(EffectIdMap::getInstance()->toId($effect->getType()) === EffectIds::NIGHT_VISION) {
            $event->cancel();
        }
        if(EffectIdMap::getInstance()->toId($effect->getType()) === EffectIds::MINING_FATIGUE) {
            $event->cancel();
        }
    }

    /**
     * @priority NORMAL
     *
     * @param EntityTeleportEvent $event
     */
    public function onEntityTeleport(EntityTeleportEvent $event): void {
        $entity = $event->getEntity();
        if(!$entity instanceof NexusPlayer) {
            return;
        }
        $world = $event->getTo()->getWorld();
        if($entity->isLoaded()) {
            Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new class($entity) extends Task {

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

                public function onRun(): void {
                    if($this->player === null or $this->player->isClosed()) {
                        return;
                    }
                    /** @var NexusPlayer[] $players */
                    $players = Server::getInstance()->getOnlinePlayers();
                    $this->player->getDataSession()->updateNameTag($players);
                    foreach($players as $player) {
                        if($player->isLoaded()) {
                            $player->getDataSession()->updateNameTag([$this->player]);
                        }
                    }
                }
            }, 20);
        }
        foreach($entity->getFloatingTexts() as $floatingText) {
            if($floatingText->isInvisible() and $world->getFolderName() === $floatingText->getLevel()->getFolderName()) {
                $floatingText->spawn($entity);
                continue;
            }
            if((!$floatingText->isInvisible()) and $world->getFolderName() !== $floatingText->getLevel()->getFolderName()) {
                $floatingText->despawn($entity);
                continue;
            }
        }
    }


    /**
     * @priority NORMAL
     *
     * @param QueryRegenerateEvent $event
     */
    public function onQueryRegenerate(QueryRegenerateEvent $event): void {
        $maxPlayers = $this->core->getServer()->getMaxPlayers();
        $maxSlots = $maxPlayers - Nexus::EXTRA_SLOTS;
        $info = $event->getQueryInfo();
        $players = count($this->core->getServer()->getOnlinePlayers());
        if($players === $maxPlayers) {
            $info->setMaxPlayerCount($maxPlayers);
            return;
        }
        if($maxSlots <= $players) {
            if($players === $maxSlots) {
                $info->setMaxPlayerCount($maxSlots + 1);
                return;
            }
            $info->setMaxPlayerCount($maxSlots + $players + 1);
            return;
        }
        $info->setMaxPlayerCount($maxSlots);
    }

    /**
     * @priority LOWEST
     *
     * @param LeavesDecayEvent $event
     */
    public function onLeavesDecay(LeavesDecayEvent $event) {
        $event->cancel();
    }

    // ITEM SKIN FUNCTIONALITY

    public static function parseInventory(NexusPlayer $player) {
        if ($player->getInventory() != null) {
            foreach ($player->getInventory()->getContents() as $slot => $item) {
                if ($item instanceof Durable && ($player->getDataSession() != null)) {
                    $currentSkins = $player->getDataSession()->getCurrentItemSkin();
                    if ($item instanceof Pickaxe && strlen($currentSkins[1]) > 0) {
                        $scroll = ItemManager::getSkinScroll($currentSkins[1]);
                        $player->getInventory()->setItem($slot, $scroll->makeNewItem($item));
                    } elseif ($item instanceof Sword && strlen($currentSkins[0]) > 0) {
                        $scroll = ItemManager::getSkinScroll($currentSkins[0]);
                        $player->getInventory()->setItem($slot, $scroll->makeNewItem($item));
                    } elseif (self::hasSkin($item)) {
                        $skinScroll = ItemManager::getSkinScroll(ItemManager::getIdentifier($item->getId()));
                        if ($currentSkins[0] !== $skinScroll->getSkinId() && $currentSkins[1] !== $skinScroll->getSkinId()) {
                            $player->getInventory()->setItem($slot, self::clearSkin($item));
                        }
                    }
                }
            }
        }
    }

    public function cleanInventory(Inventory $inv) {
        foreach ($inv->getContents() as $slot => $item) {
            if($item instanceof Durable && self::hasSkin($item)) {
                $inv->setItem($slot, self::clearSkin($item));
            }
        }
    }

//    public function onInventoryClose(InventoryCloseEvent $event) {
//        /** @var NexusPlayer $player */
//        $player = $event->getPlayer();
//        Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use($player) : void {
//            $this->parseInventory($player);
//        }), 5);
//    }

    public function onTransaction(InventoryTransactionEvent $event) {
        /** @var NexusPlayer $player */
        $player = $event->getTransaction()->getSource();
        $invs = $event->getTransaction()->getInventories();
        foreach($event->getTransaction()->getActions() as $action) {
            if($action->getTargetItem() instanceof Durable || $action->getSourceItem() instanceof Durable) {
                Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use($player, $invs) : void {
                    self::parseInventory($player);
                    foreach ($invs as $inv) {
                        if(!($inv instanceof PlayerInventory)) {
                            $this->cleanInventory($inv);
                        }
                    }
                }), 5);
            }
        }
    }

    public function onItemPickUp(EntityItemPickupEvent $event) {
        $item = $event->getItem();
        $player = $event->getEntity();
        if($player instanceof NexusPlayer && $item instanceof Durable) {
            $currentSkins = $player->getDataSession()->getCurrentItemSkin();
            if ($item instanceof Pickaxe && strlen($currentSkins[1]) > 0) {
                $scroll = ItemManager::getSkinScroll($currentSkins[1]);
                $event->setItem($scroll->makeNewItem($item));
            } elseif($item instanceof Sword && strlen($currentSkins[0]) > 0) {
                $scroll = ItemManager::getSkinScroll($currentSkins[0]);
                $event->setItem($scroll->makeNewItem($item));
            } elseif(self::hasSkin($item)) {
                $skinScroll = ItemManager::getSkinScroll(ItemManager::getIdentifier($item->getId()));
                if ($currentSkins[0] !== $skinScroll->getSkinId() && $currentSkins[1] !== $skinScroll->getSkinId()) {
                    $event->setItem(self::clearSkin($item));
                }
            }
        }
    }

    private const SKIN_CLASSES = [ // TODO: Axe
        WoodenSkinnedPickaxe::class, StoneSkinnedPickaxe::class, IronSkinnedPickaxe::class, GoldSkinnedPickaxe::class, DiamondSkinnedPickaxe::class,
        WoodenSkinnedSword::class, StoneSkinnedSword::class, IronSkinnedSword::class, GoldSkinnedSword::class, DiamondSkinnedSword::class
    ];

    public static function hasSkin(Durable $item) {
        return in_array(get_class($item), self::SKIN_CLASSES);
    }

    public static function clearSkin(Durable $item) : ?Durable {
        if($item instanceof Pickaxe) {
            $new = self::getPickaxeByTier($item->getTier());
            return $item->copyInto($new);
        }
        if($item instanceof Sword) {
            $new = self::getSwordByTier($item->getTier());
            return $item->copyInto($new);
        }
        // TODO: Sword and axe
        return null;
    }

    private static function getPickaxeByTier(ToolTier $tier) : ?Pickaxe {
        switch ($tier) {
            case ToolTier::WOOD():
                return ItemFactory::getInstance()->get(ItemIds::WOODEN_PICKAXE, 0, 1);
            case ToolTier::STONE():
                return ItemFactory::getInstance()->get(ItemIds::STONE_PICKAXE, 0, 1);
            case ToolTier::IRON():
                return ItemFactory::getInstance()->get(ItemIds::IRON_PICKAXE, 0, 1);
            case ToolTier::GOLD():
                return ItemFactory::getInstance()->get(ItemIds::GOLDEN_PICKAXE, 0, 1);
            case ToolTier::DIAMOND():
                return ItemFactory::getInstance()->get(ItemIds::DIAMOND_PICKAXE, 0, 1);
        }
        return null;
    }

    private static function getSwordByTier(ToolTier $tier) : ?Sword {
        switch ($tier) {
            case ToolTier::WOOD():
                return ItemFactory::getInstance()->get(ItemIds::WOODEN_SWORD, 0, 1);
            case ToolTier::STONE():
                return ItemFactory::getInstance()->get(ItemIds::STONE_SWORD, 0, 1);
            case ToolTier::IRON():
                return ItemFactory::getInstance()->get(ItemIds::IRON_SWORD, 0, 1);
            case ToolTier::GOLD():
                return ItemFactory::getInstance()->get(ItemIds::GOLDEN_SWORD, 0, 1);
            case ToolTier::DIAMOND():
                return ItemFactory::getInstance()->get(ItemIds::DIAMOND_SWORD, 0, 1);
        }
        return null;
    }
}