<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\args\TargetArgument;
use core\command\utils\args\TextArgument;
use core\command\utils\Command;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\PopSound;

class TellCommand extends Command {

    /**
     * TellCommand constructor.
     */
    public function __construct() {
        parent::__construct("tell", "Send a message to a player.", "/tell <player> <message>", ["whisper, w, message, msg"]);
        $this->registerArgument(0, new TargetArgument("player"));
        $this->registerArgument(1, new TextArgument("message"));
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if($sender instanceof NexusPlayer && Nexus::getInstance()->getGameManager()->getBOOPManager()->isMuted($sender->getName())) {
            $sender->sendMessage(TextFormat::GRAY . "You are currently muted!");
            // TODO: Translation const?
            return;
        }
        if(count($args) < 2) {
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]));
            return;
        }
        $name = array_shift($args);
        $player = $this->getCore()->getServer()->getPlayerByPrefix($name);
        if($sender === $player or $player === null) {
            $sender->sendMessage(Translation::getMessage("invalidPlayer"));
            return;
        }
        $message = implode(" ", $args);
        $sender->sendMessage(TextFormat::GRAY . "(Outgoing) " . TextFormat::DARK_GRAY . "[" . TextFormat::AQUA . $player->getName() . TextFormat::DARK_GRAY . "] " . TextFormat::RESET . TextFormat::GOLD . $message);
        $player->sendMessage(TextFormat::GRAY . "(Incoming) " . TextFormat::DARK_GRAY . "[" . TextFormat::AQUA . $sender->getName() . TextFormat::DARK_GRAY . "] " . TextFormat::RESET . TextFormat::GOLD . $message);
        $level = $player->getWorld();
        if($level !== null) {
            $level->addSound($player->getPosition(), new PopSound(), [$player]);
        }
        if($player instanceof NexusPlayer) {
            $player->setLastTalked($sender);
        }
        if($sender instanceof NexusPlayer) {
            $sender->setLastTalked($player);
        }
    }
}