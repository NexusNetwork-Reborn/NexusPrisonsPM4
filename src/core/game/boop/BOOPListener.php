<?php

declare(strict_types = 1);

namespace core\game\boop;

use core\game\boop\task\PlayerCommandLogTask;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rank\Rank;
use core\provider\event\PlayerLoadEvent;
use core\translation\Translation;
use core\translation\TranslationException;
use core\game\boop\task\CommandLogTask;
use core\game\boop\task\ProxyCheckTask;
use libs\utils\Utils;
use muqsit\invmenu\InvMenu;
use pocketmine\block\tile\Container;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class BOOPListener implements Listener {

    /** @var Nexus */
    private $core;

    /** @var string[] */
    private $keys = [
        "NTMzNDpwdm91VkpJTkJ1Mk1BN0ZTdDR0aG1KMGxvQVI3NVFSTg==",
        "NTMzNTpCcERObUd0MG5qMGxVYmpFbm5xck41dU9NelNyUmJadw==",
        "NTMzNjpza2pUWUV1MlIyNzUzdFE1Q3lyUGE1SjVud1BZUndZVA==",
        "NTMzNzpEdGVlaW82RDJNeWJMYVdnWktENlFMeEVSQUlwWm84Sw==",
        "NTMzODpaaTJEclByM09pbllLbDU2UmlDWmtRZEU4dlBjYjFxeg=="
    ];

    /** @var int */
    private $count = 0;

    /**
     * BOOPListener constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
    }

    /**
     * @param PlayerQuitEvent $event
     */
    public function onPlayerQuit(PlayerQuitEvent $event) : void{
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if($player->isImmobile() and $player->isFrozen()) {
            $name = $player->getName();
            $reason = "Leaving while being frozen";
            $time = 604800;
            $this->core->getServer()->dispatchCommand(new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage()), "tempban $name $time $reason");
        }
    }

    /**
     * @priority LOW
     * @param PlayerPreLoginEvent $event
     *
     * @throws TranslationException
     */
    public function onPlayerPreLogin(PlayerPreLoginEvent $event): void {
        $player = $event->getPlayerInfo();
        if($this->core->getGameManager()->getBOOPManager()->isBanned($player->getUsername())) {
            $ban = $this->core->getGameManager()->getBOOPManager()->getBan($player->getUsername());
            if($ban->getExpiration() === 0) {
                $timeString = "Forever";
            }
            else {
                $expiration = ($ban->getTime() + $ban->getExpiration()) - time();
                $timeString = Utils::secondsToTime((int)$expiration);
            }
            $message = Translation::getMessage("banMessage", [
                "name" => TextFormat::RED . $ban->getEffector(),
                "reason" => TextFormat::YELLOW . $ban->getReason(),
                "time" => TextFormat::RED . $timeString
            ]);
            $event->setKickReason(PlayerPreLoginEvent::KICK_REASON_BANNED, $message);
        }
    }

    /**
     * @priority LOWEST
     * @param PlayerLoadEvent $event
     */
    public function onPlayerLoad(PlayerLoadEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $ipAddress = $player->getNetworkSession()->getIp();
        $uuid = $player->getUniqueId()->toString();
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("SELECT riskLevel FROM ipAddress WHERE ipAddress = ? AND uuid = ?");
        $stmt->bind_param("ss", $ipAddress, $uuid);
        $stmt->execute();
        $stmt->bind_result($result);
        $stmt->fetch();
        $stmt->close();
        if($result === null) {
            ++$this->count;
            if($this->count > count($this->keys) - 1) {
                $this->count = 0;
            }
            $key = $this->keys[$this->count++];
            $this->core->getServer()->getAsyncPool()->submitTaskToWorker(new ProxyCheckTask($player->getName(), $ipAddress, $key), 0);
            return;
        }
        if($result === 1) {
            //$player->kickDelay(TextFormat::RED . "A malicious ip swapper was detected!");
            return;
        }
    }

    /**
     * @priority LOW
     * @param PlayerChatEvent $event
     *
     * @throws TranslationException
     */
    public function onPlayerChat(PlayerChatEvent $event): void {
        /** @var NexusPlayer $player */
        $player = $event->getPlayer();
        $rank = $player->getDataSession()->getRank();
        if($this->core->isGlobalMuted() and ($rank->getIdentifier() < Rank::TRAINEE or $rank->getIdentifier() > Rank::EXECUTIVE)) {
            $player->sendMessage(TextFormat::RED . "Chat is currently staff only!");
            $event->cancel();
            return;
        }
        if($this->core->getGameManager()->getBOOPManager()->isMuted($player->getName())) {
            $ban = $this->core->getGameManager()->getBOOPManager()->getMute($player->getName());
            $expiration =  abs(intval(($ban->getTime() + $ban->getExpiration()) - time()));
            $timeString = Utils::secondsToTime($expiration);
            $message = Translation::getMessage("muteMessage", [
                "name" => TextFormat::RED . $ban->getEffector(),
                "reason" => TextFormat::YELLOW . $ban->getReason(),
                "time" => TextFormat::RED . $timeString
            ]);
            $player->sendMessage($message);
            $event->cancel();
        }
    }

    /**
     * @priority LOWEST
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
        $message = $event->getMessage();
        if(strpos($message, "/") !== 0) {
            return;
        }
        if($this->core->getGameManager()->getBOOPManager()->isBlocked($player->getName())) {
            $ban = $this->core->getGameManager()->getBOOPManager()->getBlock($player->getName());
            $expiration = ($ban->getTime() + $ban->getExpiration()) - time();
            $timeString = Utils::secondsToTime($expiration);
            $message = Translation::getMessage("blockMessage", [
                "name" => TextFormat::RED . $ban->getEffector(),
                "reason" => TextFormat::YELLOW . $ban->getReason(),
                "time" => TextFormat::RED . $timeString
            ]);
            $player->sendMessage($message);
            $event->cancel();
            return;
        }
        if($player->isImmobile() and $player->isFrozen()) {
            $value = false;
            $commands = ["/msg", "/w", "/tell", "/whisper", "/message", "/pm", "/m"];
            foreach($commands as $command) {
                if(strpos($message, $command) !== false) {
                    $value = true;
                }
            }
            if($value === true) {
                $player->sendMessage(Translation::getMessage("frozen", [
                    "name" => "You are"
                ]));
            }
        }
        $rank = $player->getDataSession()->getRank();
        if($rank->getIdentifier() >= Rank::TRAINEE and $rank->getIdentifier() <= Rank::EXECUTIVE) {
            $value = false;
            $commands = ["/r", "/reply", "/msg", "/w", "/tell", "/whisper", "/message", "/pm", "/m", "/feed", "/fly", "/spawn", "/pvp", "/faction", "/f", "/cf", "/ceinfo", "/crates", "/changelog", "/inbox", "/trade", "/kit", "/pvphud", "/repair", "/rename", "/sell", "/shop", "/gkit", "/trash", "/vote", "/withdraw"];
            foreach($commands as $command) {
                if(strpos($message, $command) !== false) {
                    $value = true;
                }
            }
            if($value === false) {
                $this->core->getServer()->getAsyncPool()->submitTaskToWorker(new CommandLogTask("[Prison]" . date("[n/j/Y][G:i:s]", time()). " {$player->getName()}: $message"), 1);
            }
        }
        else {
            $this->core->getServer()->getAsyncPool()->submitTaskToWorker(new PlayerCommandLogTask("[Prison] " . date("[n/j/Y][G:i:s]", time()) . " {$player->getName()}: {$event->getMessage()}"), 1);
        }
    }

    /**
     * @priority HIGH
     * @param PlayerInteractEvent $event
     */
    public function onPlayerInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if(!$player->isLoaded()) {
            $event->cancel();
            return;
        }
        if($player->hasVanished()) {
            $container = $event->getBlock()->getPosition()->getWorld()->getTile($event->getBlock()->getPosition());
            if($container instanceof Container) {
                $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
                $menu->setListener(InvMenu::readonly());
                $menu->getInventory()->setContents($container->getInventory()->getContents());
                $menu->setName("Selected Inventory");
                $menu->send($player);
            }
            $event->cancel();
        }
    }

    /**
     * @priority LOWEST
     * @param EntityDamageEvent $event
     *
     * @throws TranslationException
     */
    public function onEntityDamage(EntityDamageEvent $event): void {
        $entity = $event->getEntity();
        if(!$entity instanceof NexusPlayer) {
            return;
        }
        if($entity->hasVanished()) {
            $event->cancel();
            return;
        }
        if($entity->isImmobile() and $entity->isFrozen()) {
            $event->cancel();
            $entity->sendMessage(Translation::getMessage("frozen", [
                "name" => "You are"
            ]));
            if($event instanceof EntityDamageByEntityEvent) {
                $damager = $event->getDamager();
                if(!$damager instanceof NexusPlayer) {
                    return;
                }
                $damager->sendMessage(Translation::getMessage("frozen", [
                    "name" => $entity->getName() . " is"
                ]));
            }
        }
    }
}
