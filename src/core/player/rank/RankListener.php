<?php
declare(strict_types=1);

namespace core\player\rank;

use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\utils\TextFormat;

class RankListener implements Listener {

    /** @var Nexus */
    private $core;

    /**
     * GroupListener constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
    }

    /**
     * @priority HIGHEST
     *
     * @param PlayerChatEvent $event
     */
    public function onPlayerChat(PlayerChatEvent $event): void {
        if($event->isCancelled()) {
            return;
        }
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $event->cancel();
        $session = $player->getDataSession();
        $mode = $player->getChatMode();
        $gang = $session->getGang();
        if($gang === null and ($mode === NexusPlayer::GANG or $mode === NexusPlayer::ALLY)) {
            $mode = NexusPlayer::PUBLIC;
            $player->setChatMode($mode);
        }
        if($mode === NexusPlayer::PUBLIC) {
            $message = $event->getMessage();
            if($message === strtoupper($message)) {
                $message = strtolower($message);
            }
            if($player->getDisguiseRank() !== null) {
                /** @var NexusPlayer $onlinePlayer */
                foreach($this->core->getServer()->getOnlinePlayers() as $onlinePlayer) {
                    if(!$onlinePlayer->isLoaded()) {
                        continue;
                    }
                    $onlinePlayer->sendMessage($player->getDisguiseRank()->getChatFormatFor($player, $message, [
                        "gangRole" => "",
                        "gang" => "",
                        "level" => $player->getDataSession()->getPrestigeTag()
                    ]));
                }
                $this->core->getLogger()->info($session->getRank()->getChatFormatFor($player, $message, [
                    "gangRole" => "",
                    "gang" => "",
                    "level" => $player->getDataSession()->getPrestigeTag()
                ]));
            }
            else {
                /** @var NexusPlayer $onlinePlayer */
                foreach($this->core->getServer()->getOnlinePlayers() as $onlinePlayer) {
                    if(!$onlinePlayer->isLoaded()) {
                        continue;
                    }
                    $color = TextFormat::WHITE;
                    $pGang = $onlinePlayer->getDataSession()->getGang();
                    if($pGang !== null and $gang !== null) {
                        $color = $pGang->getRelationColor($gang);
                    }
                    $onlinePlayer->sendMessage($session->getRank()->getChatFormatFor($player, $message, [
                        "gangRole" => $color . $session->getGangRoleToString(),
                        "gang" => $gang !== null ? $color . $gang->getName() . " " : "",
                        "level" => $player->getDataSession()->getPrestigeTag()
                    ]));
                }
                $this->core->getLogger()->info($session->getRank()->getChatFormatFor($player, $message, [
                    "gangRole" => TextFormat::WHITE . $session->getGangRoleToString(),
                    "gang" => $gang !== null ? TextFormat::WHITE . $gang->getName() . " " : "",
                    "level" => $player->getDataSession()->getPrestigeTag()
                ]));
            }
            return;
        }
        if($mode === NexusPlayer::STAFF) {
            /** @var NexusPlayer $staff */
            foreach($this->core->getServer()->getOnlinePlayers() as $staff) {
                if(!$staff->isLoaded()) {
                    continue;
                }
                $rank = $staff->getDataSession()->getRank();
                if($rank->getIdentifier() >= Rank::TRAINEE and $rank->getIdentifier() <= Rank::EXECUTIVE) {
                    $staff->sendMessage(TextFormat::DARK_GRAY . "[" . TextFormat::BOLD . TextFormat::DARK_RED . "SC" . TextFormat::RESET . TextFormat::DARK_GRAY . "] " . $session->getRank()->getColoredName() . " " . TextFormat::RESET . TextFormat::WHITE . $player->getDisplayName() . TextFormat::DARK_AQUA . ": " . $event->getMessage());
                }
            }
            return;
        }
        if($player->getChatMode() === NexusPlayer::GANG) {
            $onlinePlayers = $gang->getOnlineMembers();
            foreach($onlinePlayers as $onlinePlayer) {
                if(!$onlinePlayer->isLoaded()) {
                    continue;
                }
                $onlinePlayer->sendMessage(TextFormat::DARK_GRAY . "[" . TextFormat::BOLD . TextFormat::GREEN . "GC" . TextFormat::RESET . TextFormat::DARK_GRAY . "] " . $session->getRank()->getColoredName() . " " . TextFormat::RESET . TextFormat::WHITE . $player->getDisplayName() . TextFormat::GREEN . ": " . $event->getMessage());
            }
        }
        else {
            $allies = $gang->getAllies();
            $onlinePlayers = $gang->getOnlineMembers();
            foreach($allies as $ally) {
                if(($ally = $this->core->getPlayerManager()->getGangManager()->getGang($ally)) === null) {
                    continue;
                }
                $onlinePlayers = array_merge($ally->getOnlineMembers(), $onlinePlayers);
            }
            foreach($onlinePlayers as $onlinePlayer) {
                if(!$onlinePlayer->isLoaded()) {
                    continue;
                }
                $onlinePlayer->sendMessage(TextFormat::DARK_GRAY . "[" . TextFormat::BOLD . TextFormat::AQUA . "AC" . TextFormat::RESET . TextFormat::DARK_GRAY . "] " . $session->getRank()->getColoredName() . " " . TextFormat::RESET . TextFormat::WHITE . $player->getDisplayName() . TextFormat::AQUA . ": " . $event->getMessage());
            }
        }
    }

    /**
     * @priority NORMAL
     *
     * @param EntityRegainHealthEvent $event
     */
    public function onEntityRegainHealth(EntityRegainHealthEvent $event): void {
        if($event->isCancelled()) {
            return;
        }
        $player = $event->getEntity();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if(!$player->isLoaded()) {
            return;
        }
        $hp = round($player->getHealth(), 1);
        if($player->getCESession()->isHidingHealth()) {
            $hp = TextFormat::OBFUSCATED . $hp . TextFormat::RESET;
        }
        $player->setScoreTag(TextFormat::WHITE . $hp . TextFormat::RED . TextFormat::BOLD . " HP");
    }

    /**
     * @priority NORMAL
     *
     * @param EntityDamageEvent $event
     */
    public function onEntityDamage(EntityDamageEvent $event): void {
        if($event->isCancelled()) {
            return;
        }
        $player = $event->getEntity();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if(!$player->isLoaded()) {
            return;
        }
        $hp = round($player->getHealth(), 1);
        if($player->getCESession()->isHidingHealth()) {
            $hp = TextFormat::OBFUSCATED . $hp . TextFormat::RESET;
        }
        $player->setScoreTag(TextFormat::WHITE . $hp . TextFormat::RED . TextFormat::BOLD . " HP");
    }
}