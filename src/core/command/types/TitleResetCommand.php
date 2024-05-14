<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\args\RawStringArgument;
use core\command\utils\Command;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rank\Rank;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\utils\TextFormat;

class TitleResetCommand extends Command {

    /**
     * NickResetCommand constructor.
     */
    public function __construct() {
        parent::__construct("titlereset", "Reset title.", "/titlereset");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if($sender instanceof NexusPlayer) {
            $tag = $sender->getDataSession()->getCurrentTag();
            if(!empty($tag)) {
                $sender->getDataSession()->setCurrentTag("");
                $sender->sendMessage(Translation::GREEN . "Your title has been reset!");
                return;
            }
            $sender->sendMessage(Translation::RED . "You must have a title equipped before resetting one!");
            return;
        }
        $sender->sendMessage(Translation::getMessage("noPermission"));
    }
}