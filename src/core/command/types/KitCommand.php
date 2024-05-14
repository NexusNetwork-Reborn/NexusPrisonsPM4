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

class KitCommand extends Command {

    /**
     * KitCommand constructor.
     */
    public function __construct() {
        parent::__construct("kit", "Manage your kits");
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
        if(!Nexus::getInstance()->getGameManager()->getFundManager()->isUnlocked(FundManager::PHASE_TWO)) {
            $sender->sendTranslatedMessage("fundDisabled", [
                "feature" => TextFormat::RED . "/kit"
            ]);
            return;
        }
        $sender->sendForm(new KitListForm($sender, $this->getCore()->getGameManager()->getKitManager()->getKits()));
    }
}