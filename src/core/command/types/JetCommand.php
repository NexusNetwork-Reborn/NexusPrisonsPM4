<?php

namespace core\command\types;

use core\command\task\JetBlastOffTask;
use core\command\utils\Command;
use core\player\NexusPlayer;
use core\player\rank\Rank;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\utils\TextFormat;

class JetCommand extends Command {

    /**
     * JetCommand constructor.
     */
    public function __construct() {
        parent::__construct("jet", "Enable get pack");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if((!$sender instanceof NexusPlayer) or ((!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) and (!$sender->hasPermission("permission.tier5")))) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        $cd = 30;
        if($sender->getDataSession()->getRank()->getIdentifier() >= Rank::EMPEROR_HEROIC) {
            $cd = 15;
        }
        $lastJet = $cd - (time() - $sender->getLastJet());
        if($lastJet > 0) {
            $sender->sendTranslatedMessage("actionCooldown", [
                "amount" => TextFormat::RED . $lastJet
            ]);
            return;
        }
        $sender->setLastJet();
        $this->getCore()->getScheduler()->scheduleRepeatingTask(new JetBlastOffTask($sender), 20);
    }
}