<?php
declare(strict_types=1);

namespace core\game\quest;

use core\display\animation\entity\MeteorBaseEntity;
use core\game\combat\guards\BaseGuard;
use core\game\combat\task\KillLogTask;
use core\game\item\event\EarnEnergyEvent;
use core\game\item\event\PrestigePickaxeEvent;
use core\game\item\event\SatchelLevelUpEvent;
use core\game\item\event\ShardFoundEvent;
use core\game\wormhole\event\EnchantmentOrbUseEvent;
use core\level\entity\types\Lightning;
use core\level\entity\types\PummelBlock;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\utils\TextFormat;

class QuestListener implements Listener {

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
     * @param EntityDamageEvent $event
     */
    public function onEntityDamage(EntityDamageEvent $event): void {
        if($event instanceof EntityDamageByEntityEvent) {
            $damager = $event->getDamager();
            $player = $event->getEntity();
            if($player instanceof NexusPlayer and $player->isLoaded() and $damager instanceof MeteorBaseEntity) {
                $quests = $player->getDataSession()->getQuests();
                foreach($quests as $name => $progress) {
                    $pass = $this->core->getGameManager()->getQuestManager()->getQuest($name);
                    if($pass->getEventType() === Quest::DAMAGE_BY_METEOR) {
                        $callable = $pass->getCallable();
                        $callable($event);
                    }
                }
            }
        }
    }

    /**
     * @param PlayerDeathEvent $event
     *
     * @throws \core\translation\TranslationException
     */
    public function onPlayerDeath(PlayerDeathEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $cause = $player->getLastDamageCause();
        if($cause instanceof EntityDamageByEntityEvent) {
            $killer = $cause->getDamager();
            if($killer instanceof Lightning) {
                $killer = $killer->getOwningEntity();
            }
            if($killer instanceof PummelBlock) {
                $killer = $killer->getOwningEntity();
            }
            if($killer instanceof NexusPlayer and $killer->isLoaded()) {
                $quests = $killer->getDataSession()->getQuests();
                foreach($quests as $name => $progress) {
                    $pass = $this->core->getGameManager()->getQuestManager()->getQuest($name);
                    if($pass->getEventType() === Quest::KILL) {
                        $callable = $pass->getCallable();
                        $callable($event);
                    }
                }
            }
        }
    }

    /**
     * @priority HIGHEST
     * @param SatchelLevelUpEvent $event
     */
    public function onSatchelLevelUp(SatchelLevelUpEvent $event): void {
        $player = $event->getPlayer();
        if($player instanceof NexusPlayer and $player->isLoaded()) {
            $quests = $player->getDataSession()->getQuests();
            foreach($quests as $name => $progress) {
                $pass = $this->core->getGameManager()->getQuestManager()->getQuest($name);
                if($pass->getEventType() === Quest::SATCHEL_LEVEL_UP) {
                    $callable = $pass->getCallable();
                    $callable($event);
                }
            }
        }
    }

    /**
     * @priority HIGHEST
     * @param EnchantmentOrbUseEvent $event
     */
    public function onEnchantmentOrbUse(EnchantmentOrbUseEvent $event): void {
        $player = $event->getPlayer();
        if($player instanceof NexusPlayer and $player->isLoaded()) {
            $quests = $player->getDataSession()->getQuests();
            foreach($quests as $name => $progress) {
                $pass = $this->core->getGameManager()->getQuestManager()->getQuest($name);
                if($pass->getEventType() === Quest::USE_ENCHANT_ORB) {
                    $callable = $pass->getCallable();
                    $callable($event);
                }
            }
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
        $player = $event->getPlayer();
        if($player instanceof NexusPlayer and $player->isLoaded()) {
            $quests = $player->getDataSession()->getQuests();
            foreach($quests as $name => $progress) {
                $pass = $this->core->getGameManager()->getQuestManager()->getQuest($name);
                if($pass->getEventType() === Quest::MINE or $pass->getEventType() === Quest::MINE_METEORITE or $pass->getEventType() === Quest::MOMENTUM) {
                    $callable = $pass->getCallable();
                    $callable($event);
                }
            }
        }
    }

    /**
     * @priority HIGHEST
     * @param ShardFoundEvent $event
     */
    public function onShardFound(ShardFoundEvent $event): void {
        $player = $event->getPlayer();
        if($player instanceof NexusPlayer and $player->isLoaded()) {
            $quests = $player->getDataSession()->getQuests();
            foreach($quests as $name => $progress) {
                $pass = $this->core->getGameManager()->getQuestManager()->getQuest($name);
                if($pass->getEventType() === Quest::FIND_SHARDS) {
                    $callable = $pass->getCallable();
                    $callable($event);
                }
            }
        }
    }

    /**
     * @priority HIGHEST
     * @param EarnEnergyEvent $event
     */
    public function onEarnEnergy(EarnEnergyEvent $event): void {
        $player = $event->getPlayer();
        if($player instanceof NexusPlayer and $player->isLoaded()) {
            $quests = $player->getDataSession()->getQuests();
            foreach($quests as $name => $progress) {
                $pass = $this->core->getGameManager()->getQuestManager()->getQuest($name);
                if($pass->getEventType() === Quest::EARN_ENERGY) {
                    $callable = $pass->getCallable();
                    $callable($event);
                }
            }
        }
    }

    /**
     * @priority HIGHEST
     * @param PrestigePickaxeEvent $event
     */
    public function onPrestigePickaxe(PrestigePickaxeEvent $event): void {
        $player = $event->getPlayer();
        if($player instanceof NexusPlayer and $player->isLoaded()) {
            $quests = $player->getDataSession()->getQuests();
            foreach($quests as $name => $progress) {
                $pass = $this->core->getGameManager()->getQuestManager()->getQuest($name);
                if($pass->getEventType() === Quest::PRESTIGE_PICKAXE) {
                    $callable = $pass->getCallable();
                    $callable($event);
                }
            }
        }
    }
//
//    /**
//     * @priority HIGHEST
//     * @param BlockPlaceEvent $event
//     */
//    public function onBlockPlace(BlockPlaceEvent $event): void {
//        if($event->isCancelled()) {
//            return;
//        }
//        $player = $event->getPlayer();
//        if($player instanceof NexusPlayer and $player->isLoaded()) {
//            $tier = $player->getDataSession()->getCurrentTier();
//            if($tier > 80) {
//                return;
//            }
//            $pass = $this->core->getPassManager()->getTier($tier);
//            if($pass->getEventType() === Pass::PLACE) {
//                $callable = $pass->getCallable();
//                $callable($event);
//            }
//        }
//    }
//
//    /**
//     * @priority HIGHEST
//     * @param ItemSellEvent $event
//     */
//    public function onItemSell(ItemSellEvent $event): void {
//        $player = $event->getPlayer();
//        if($player instanceof NexusPlayer and $player->isLoaded()) {
//            $tier = $player->getDataSession()->getCurrentTier();
//            if($tier > 80) {
//                return;
//            }
//            $pass = $this->core->getPassManager()->getTier($tier);
//            if($pass->getEventType() === Pass::SELL) {
//                $callable = $pass->getCallable();
//                $callable($event);
//            }
//        }
//    }
//
//    /**
//     * @priority HIGHEST
//     * @param ItemBuyEvent $event
//     */
//    public function onItemBuy(ItemBuyEvent $event): void {
//        $player = $event->getPlayer();
//        if($player instanceof NexusPlayer and $player->isLoaded()) {
//            $tier = $player->getDataSession()->getCurrentTier();
//            if($tier > 80) {
//                return;
//            }
//            $pass = $this->core->getPassManager()->getTier($tier);
//            if($pass->getEventType() === Pass::BUY) {
//                $callable = $pass->getCallable();
//                $callable($event);
//            }
//        }
//    }
//
//    /**
//     * @priority HIGHEST
//     * @param EnvoyClaimEvent $event
//     */
//    public function onEnvoyClaim(EnvoyClaimEvent $event): void {
//        $player = $event->getPlayer();
//        if($player instanceof NexusPlayer and $player->isLoaded()) {
//            $tier = $player->getDataSession()->getCurrentTier();
//            if($tier > 80) {
//                return;
//            }
//            $pass = $this->core->getPassManager()->getTier($tier);
//            if($pass->getEventType() === Pass::CLAIM_ENVOY) {
//                $callable = $pass->getCallable();
//                $callable($event);
//            }
//        }
//    }
//
//    /**
//     * @priority HIGHEST
//     * @param CrateOpenEvent $event
//     */
//    public function onCrateOpen(CrateOpenEvent $event): void {
//        $player = $event->getPlayer();
//        if($player instanceof NexusPlayer and $player->isLoaded()) {
//            $tier = $player->getDataSession()->getCurrentTier();
//            if($tier > 80) {
//                return;
//            }
//            $pass = $this->core->getPassManager()->getTier($tier);
//            if($pass->getEventType() === Pass::OPEN_CRATE) {
//                $callable = $pass->getCallable();
//                $callable($event);
//            }
//        }
//    }
//
//    /**
//     * @priority HIGHEST
//     * @param CoinFlipWinEvent $event
//     */
//    public function onCoinFlipWin(CoinFlipWinEvent $event): void {
//        $player = $event->getPlayer();
//        if($player instanceof NexusPlayer and $player->isLoaded()) {
//            $tier = $player->getDataSession()->getCurrentTier();
//            if($tier > 80) {
//                return;
//            }
//            $pass = $this->core->getPassManager()->getTier($tier);
//            if($pass->getEventType() === Pass::COINFLIP_WIN) {
//                $callable = $pass->getCallable();
//                $callable($event);
//            }
//        }
//    }
//
//    /**
//     * @priority HIGHEST
//     * @param CoinFlipLoseEvent $event
//     */
//    public function onCoinFlipLose(CoinFlipLoseEvent $event): void {
//        $player = $event->getPlayer();
//        if($player instanceof NexusPlayer and $player->isLoaded()) {
//            $tier = $player->getDataSession()->getCurrentTier();
//            if($tier > 80) {
//                return;
//            }
//            $pass = $this->core->getPassManager()->getTier($tier);
//            if($pass->getEventType() === Pass::COINFLIP_LOSE) {
//                $callable = $pass->getCallable();
//                $callable($event);
//            }
//        }
//    }
//
//    /**
//     * @priority HIGHEST
//     * @param LotteryBuyEvent $event
//     */
//    public function onLotteryBuy(LotteryBuyEvent $event): void {
//        $player = $event->getPlayer();
//        if($player instanceof NexusPlayer and $player->isLoaded()) {
//            $tier = $player->getDataSession()->getCurrentTier();
//            if($tier > 80) {
//                return;
//            }
//            $pass = $this->core->getPassManager()->getTier($tier);
//            if($pass->getEventType() === Pass::BUY_LOTTERY) {
//                $callable = $pass->getCallable();
//                $callable($event);
//            }
//        }
//    }
//
//    /**
//     * @priority HIGHEST
//     * @param KOTHCaptureEvent $event
//     */
//    public function onKOTHCapture(KOTHCaptureEvent $event): void {
//        $player = $event->getPlayer();
//        if($player instanceof NexusPlayer and $player->isLoaded()) {
//            $tier = $player->getDataSession()->getCurrentTier();
//            if($tier > 80) {
//                return;
//            }
//            $pass = $this->core->getPassManager()->getTier($tier);
//            if($pass->getEventType() === Pass::KOTH_CAPTURE) {
//                $callable = $pass->getCallable();
//                $callable($event);
//            }
//        }
//    }
}