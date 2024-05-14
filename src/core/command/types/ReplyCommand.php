<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\args\TextArgument;
use core\command\utils\Command;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\PopSound;

class ReplyCommand extends Command {

    /**
     * ReplyCommand constructor.
     */
    public function __construct() {
        parent::__construct("reply", "Reply to a player.", "/reply <message>", ["r"]);
        $this->registerArgument(0, new TextArgument("message"));
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if(count($args) < 1) {
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]));
            return;
        }
        if(!$sender instanceof NexusPlayer) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        $lastTalked = $sender->getLastTalked();
        if($lastTalked === null or ($lastTalked instanceof NexusPlayer and (!$lastTalked->isOnline()))) {
            $sender->sendMessage(Translation::getMessage("invalidPlayer"));
            return;
        }
        $message = implode(" ", $args);
        $sender->sendMessage(TextFormat::GRAY . "(Outgoing) " . TextFormat::DARK_GRAY . "[" . TextFormat::AQUA . $lastTalked->getName() . TextFormat::DARK_GRAY . "] " . TextFormat::RESET . TextFormat::GOLD . $message);
        $lastTalked->sendMessage(TextFormat::GRAY . "(Incoming) " . TextFormat::DARK_GRAY . "[" . TextFormat::AQUA . $sender->getName() . TextFormat::DARK_GRAY . "] " . TextFormat::RESET . TextFormat::GOLD . $message);
        $level = $lastTalked->getWorld();
        if($level !== null) {
            $level->addSound($lastTalked->getPosition(), new PopSound(), [$lastTalked]);
        }
    }
}