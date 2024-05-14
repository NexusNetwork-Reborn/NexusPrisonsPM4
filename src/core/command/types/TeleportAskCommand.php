<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\task\TeleportTask;
use core\command\utils\args\TargetArgument;
use core\command\utils\Command;
use core\level\LevelManager;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rank\Rank;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class TeleportAskCommand extends Command {

    /**
     * TeleportAskCommand constructor.
     */
    public function __construct() {
        parent::__construct("tpa", "Ask to teleport to someone.", "/tp[a/accept/deny] <player>", ["tpaccept", "tpdeny", "tpahere"]);
        $this->registerArgument(0, new TargetArgument("player"));
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if($commandLabel !== "tpaccept") {
            if((!$sender instanceof NexusPlayer) or (!$sender->hasPermission("permission.tier4"))) {
                $sender->sendMessage(Translation::getMessage("noPermission"));
                $rankRequired = Nexus::getInstance()->getPlayerManager()->getRankManager()->getRankByIdentifier(Rank::MAJESTY);
                $sender->sendMessage(Translation::RED . "You must have " . $rankRequired->getColoredName() . TextFormat::RESET . TextFormat::GRAY . " Rank or up to use this command!");
                return;
            }
        }
        if(!isset($args[0])) {
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]));
            return;
        }
        $player = $this->getCore()->getServer()->getPlayerByPrefix($args[0]);
        if(!$player instanceof NexusPlayer) {
            $sender->sendMessage(Translation::getMessage("invalidPlayer"));
            return;
        }
        if($sender->isTeleporting() === true) {
            $sender->sendMessage(Translation::getMessage("alreadyTeleporting", [
                "name" => "You are"
            ]));
            return;
        }
        if($player->isTeleporting() === true) {
            $sender->sendMessage(Translation::getMessage("alreadyTeleporting", [
                "name" => "{$player->getName()} is"
            ]));
            return;
        }
        switch($commandLabel) {
            case "tpa":
                if($sender->isRequestingTeleport($player)) {
                    $sender->sendMessage(Translation::getMessage("alreadyRequest"));
                    return;
                }
                $sender->addTeleportRequest($player);
                $sender->sendMessage(Translation::getMessage("requestTeleport", [
                    "name" => "You have",
                    "player" => TextFormat::YELLOW . $player->getName()
                ]));
                $player->sendMessage(Translation::getMessage("requestTeleport", [
                    "name" => TextFormat::YELLOW . $sender->getName() . TextFormat::GRAY . " has",
                    "player" => "you"
                ]));
                break;
            case "tpaccept":
                if(!$player->isRequestingTeleport($sender)) {
                    $sender->sendMessage(Translation::getMessage("didNotRequest"));
                    return;
                }
                $world = $sender->getWorld()->getFolderName();
                $setup = LevelManager::getSetup();
                if($world === $setup->getNested("boss.world") || $world === $setup->getNested("koth.world") || $world === "executive" || $world === "outpost" || $world === "lounge"){
                    $sender->sendMessage(Translation::getMessage("cantTeleportToLounge"));
                    return;
                }
                $player->removeTeleportRequest($sender);
                $player->sendMessage(Translation::getMessage("acceptRequest"));
                $this->getCore()->getScheduler()->scheduleRepeatingTask(new TeleportTask($player, $sender->getPosition(), 10), 20);
                break;
            case "tpdeny":
                if(!$player->isRequestingTeleport($sender)) {
                    $sender->sendMessage(Translation::getMessage("didNotRequest"));
                    return;
                }
                $player->removeTeleportRequest($sender);
                $player->sendMessage(Translation::getMessage("denyRequest"));
                break;
            default:
                $sender->sendMessage(Translation::getMessage("usageMessage", [
                    "usage" => $this->getUsage()
                ]));
                break;
        }
    }
}