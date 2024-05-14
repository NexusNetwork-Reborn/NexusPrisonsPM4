<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\forms\KitListForm;
use core\command\utils\Command;
use core\game\fund\FundManager;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class GKitCommand extends Command {

    /**
     * GKitCommand constructor.
     */
    public function __construct() {
        parent::__construct("gkit", "Manage your god kits");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if(!$sender instanceof NexusPlayer) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        if(!Nexus::getInstance()->getGameManager()->getFundManager()->isUnlocked(FundManager::PHASE_FIVE)) {
            $sender->sendTranslatedMessage("fundDisabled", [
                "feature" => TextFormat::RED . "/gkit"
            ]);
            return;
        }
        $sender->sendForm(new KitListForm($sender, $this->getCore()->getGameManager()->getKitManager()->getGodKits()));
    }
}