<?php
declare(strict_types=1);

namespace core\game\item\cluescroll;

use core\game\combat\merchants\event\KillMerchantEvent;
use core\game\gamble\event\CoinFlipLoseEvent;
use core\game\gamble\event\CoinFlipWinEvent;
use core\game\item\event\ApplyItemEvent;
use core\game\item\event\DiscoverContrabandEvent;
use core\game\item\event\FailEnchantmentEvent;
use core\game\item\event\LevelUpPickaxeEvent;
use core\game\item\event\OpenItemEvent;
use core\game\item\event\SatchelLevelUpEvent;
use core\game\item\event\TinkerEquipmentEvent;
use core\game\item\event\TradeItemEvent;
use core\game\item\ItemManager;
use core\game\item\types\custom\ClueScroll;
use core\game\item\types\custom\MysteryClueScroll;
use core\game\item\types\Rarity;
use core\game\item\types\vanilla\Pickaxe;
use core\level\block\Ore;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat;

class ScrollListener implements Listener {

    /** @var Nexus */
    private $core;

    /**
     * QuestListener constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
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
        if(!$block instanceof Ore) {
            return;
        }
        $item = $event->getItem();
        $chance = 1250;
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $chance = (int)$chance;
        if($player->getCESession()->hasExplode()) {
            $chance *= 2;
        }
        if($item instanceof Pickaxe) {
            $chance *= (1 - ($item->getAttribute(Pickaxe::CLUE_SCROLL_MASTERY) / 100));
        }
        $chance = (int)round($chance);
        if(mt_rand(1, $chance) === mt_rand(1, $chance)) {
            $rarity = ItemManager::getRarityForXPByLevel(ItemManager::getLevelToMineOre($block));
            $item = (new MysteryClueScroll($rarity))->toItem()->setCount(1);
            $name = TextFormat::RESET . TextFormat::WHITE . $item->getName();
            if($item->hasCustomName()) {
                $name = $item->getCustomName();
            }
            $name .= TextFormat::RESET . TextFormat::GRAY . " * " . TextFormat::WHITE . $item->getCount();
            $player->sendTitle(TextFormat::GRAY . "Discovered", $name);
            $player->playBlastSound();
            $player->getInventory()->addItem($item);
        }
    }

    /**
     * @priority HIGHEST
     * @param TinkerEquipmentEvent $event
     */
    public function onTinkerEquipment(TinkerEquipmentEvent $event): void {
        $player = $event->getPlayer();
        if($player instanceof NexusPlayer and $player->isLoaded()) {
            $manager = $this->core->getGameManager()->getItemManager()->getScrollManager();
            $challenges = ScrollManager::getScrolls($player);
            foreach($challenges as $scroll) {
                $scrollInstance = ClueScroll::fromItem($scroll);
                if($scrollInstance instanceof ClueScroll) {
                    $challenge = $manager->getChallenge($scrollInstance->getCurrentChallenge());
                    if($challenge->getEventType() === Challenge::TINKER_EQUIPMENT) {
                        $callable = $challenge->getCallable();
                        $callable($event, $scroll);
                    }
                }
            }
        }
    }

    /**
     * @priority HIGHEST
     * @param DiscoverContrabandEvent $event
     */
    public function onDiscoverContraband(DiscoverContrabandEvent $event): void {
        $player = $event->getPlayer();
        if($player instanceof NexusPlayer and $player->isLoaded()) {
            $manager = $this->core->getGameManager()->getItemManager()->getScrollManager();
            $challenges = ScrollManager::getScrolls($player);
            foreach($challenges as $scroll) {
                $scrollInstance = ClueScroll::fromItem($scroll);
                if($scrollInstance instanceof ClueScroll) {
                    $challenge = $manager->getChallenge($scrollInstance->getCurrentChallenge());
                    if($challenge->getEventType() === Challenge::FIND_CONTRABAND) {
                        $callable = $challenge->getCallable();
                        $callable($event, $scroll);
                    }
                }
            }
        }
    }

    /**
     * @priority HIGHEST
     * @param CoinFlipWinEvent $event
     */
    public function onCoinFlipWin(CoinFlipWinEvent $event): void {
        $player = $event->getPlayer();
        if($player instanceof NexusPlayer and $player->isLoaded()) {
            $manager = $this->core->getGameManager()->getItemManager()->getScrollManager();
            $challenges = ScrollManager::getScrolls($player);
            foreach($challenges as $scroll) {
                $scrollInstance = ClueScroll::fromItem($scroll);
                if($scrollInstance instanceof ClueScroll) {
                    $challenge = $manager->getChallenge($scrollInstance->getCurrentChallenge());
                    if($challenge->getEventType() === Challenge::COINFLIP_WIN) {
                        $callable = $challenge->getCallable();
                        $callable($event, $scroll);
                    }
                }
            }
        }
    }

    /**
     * @priority HIGHEST
     * @param CoinFlipLoseEvent $event
     */
    public function onCoinFlipLose(CoinFlipLoseEvent $event): void {
        $player = $event->getPlayer();
        if($player instanceof NexusPlayer and $player->isLoaded()) {
            $manager = $this->core->getGameManager()->getItemManager()->getScrollManager();
            $challenges = ScrollManager::getScrolls($player);
            foreach($challenges as $scroll) {
                $scrollInstance = ClueScroll::fromItem($scroll);
                if($scrollInstance instanceof ClueScroll) {
                    $challenge = $manager->getChallenge($scrollInstance->getCurrentChallenge());
                    if($challenge->getEventType() === Challenge::COINFLIP_LOSE) {
                        $callable = $challenge->getCallable();
                        $callable($event, $scroll);
                    }
                }
            }
        }
    }

    /**
     * @priority HIGHEST
     * @param FailEnchantmentEvent $event
     */
    public function onFailEnchantment(FailEnchantmentEvent $event): void {
        $player = $event->getPlayer();
        if($player instanceof NexusPlayer and $player->isLoaded()) {
            $manager = $this->core->getGameManager()->getItemManager()->getScrollManager();
            $challenges = ScrollManager::getScrolls($player);
            foreach($challenges as $scroll) {
                $scrollInstance = ClueScroll::fromItem($scroll);
                if($scrollInstance instanceof ClueScroll) {
                    $challenge = $manager->getChallenge($scrollInstance->getCurrentChallenge());
                    if($challenge->getEventType() === Challenge::FAIL_ENCHANTMENT) {
                        $callable = $challenge->getCallable();
                        $callable($event, $scroll);
                    }
                }
            }
        }
    }

    /**
     * @priority HIGHEST
     * @param TradeItemEvent $event
     */
    public function onTradeItem(TradeItemEvent $event): void {
        $player = $event->getPlayer();
        if($player instanceof NexusPlayer and $player->isLoaded()) {
            $manager = $this->core->getGameManager()->getItemManager()->getScrollManager();
            $challenges = ScrollManager::getScrolls($player);
            foreach($challenges as $scroll) {
                $scrollInstance = ClueScroll::fromItem($scroll);
                if($scrollInstance instanceof ClueScroll) {
                    $challenge = $manager->getChallenge($scrollInstance->getCurrentChallenge());
                    if($challenge->getEventType() === Challenge::TRADE) {
                        $callable = $challenge->getCallable();
                        $callable($event, $scroll);
                    }
                }
            }
        }
    }

    /**
     * @priority HIGHEST
     * @param LevelUpPickaxeEvent $event
     */
    public function onLevelUpPickaxe(LevelUpPickaxeEvent $event): void {
        $player = $event->getPlayer();
        if($player instanceof NexusPlayer and $player->isLoaded()) {
            $manager = $this->core->getGameManager()->getItemManager()->getScrollManager();
            $challenges = ScrollManager::getScrolls($player);
            foreach($challenges as $scroll) {
                $scrollInstance = ClueScroll::fromItem($scroll);
                if($scrollInstance instanceof ClueScroll) {
                    $challenge = $manager->getChallenge($scrollInstance->getCurrentChallenge());
                    if($challenge->getEventType() === Challenge::LEVEL_UP_PICKAXE) {
                        $callable = $challenge->getCallable();
                        $callable($event, $scroll);
                    }
                }
            }
        }
    }

    /**
     * @priority HIGHEST
     *
     * @param ApplyItemEvent $event
     */
    public function onApplyItem(ApplyItemEvent $event): void {
        $player = $event->getPlayer();
        if($player instanceof NexusPlayer and $player->isLoaded()) {
            $manager = $this->core->getGameManager()->getItemManager()->getScrollManager();
            $challenges = ScrollManager::getScrolls($player);
            foreach($challenges as $scroll) {
                $scrollInstance = ClueScroll::fromItem($scroll);
                if($scrollInstance instanceof ClueScroll) {
                    $challenge = $manager->getChallenge($scrollInstance->getCurrentChallenge());
                    if($challenge->getEventType() === Challenge::APPLY_ITEM) {
                        $callable = $challenge->getCallable();
                        $callable($event, $scroll);
                    }
                }
            }
        }
    }

    /**
     * @priority HIGHEST
     *
     * @param OpenItemEvent $event
     */
    public function onOpenItem(OpenItemEvent $event): void {
        $player = $event->getPlayer();
        if($player instanceof NexusPlayer and $player->isLoaded()) {
            $manager = $this->core->getGameManager()->getItemManager()->getScrollManager();
            $challenges = ScrollManager::getScrolls($player);
            foreach($challenges as $scroll) {
                $scrollInstance = ClueScroll::fromItem($scroll);
                if($scrollInstance instanceof ClueScroll) {
                    $challenge = $manager->getChallenge($scrollInstance->getCurrentChallenge());
                    if($challenge->getEventType() === Challenge::OPEN_ITEM) {
                        $callable = $challenge->getCallable();
                        $callable($event, $scroll);
                    }
                }
            }
        }
    }

    /**
     * @priority HIGHEST
     *
     * @param KillMerchantEvent $event
     */
    public function onKillMerchant(KillMerchantEvent $event): void {
        $player = $event->getPlayer();
        if($player instanceof NexusPlayer and $player->isLoaded()) {
            $manager = $this->core->getGameManager()->getItemManager()->getScrollManager();
            $challenges = ScrollManager::getScrolls($player);
            foreach($challenges as $scroll) {
                $scrollInstance = ClueScroll::fromItem($scroll);
                if($scrollInstance instanceof ClueScroll) {
                    $challenge = $manager->getChallenge($scrollInstance->getCurrentChallenge());
                    if($challenge->getEventType() === Challenge::KILL_MERCHANT) {
                        $callable = $challenge->getCallable();
                        $callable($event, $scroll);
                    }
                }
            }
        }
    }
}