<?php
declare(strict_types=1);

namespace core\player\gang\command\subCommands;

use core\command\utils\SubCommand;
use core\player\gang\PermissionManager;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;

class VaultSubCommand extends SubCommand {

    /**
     * WithdrawSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("vault", "/gang vault");
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
        $gang = $sender->getDataSession()->getGang();
        if($gang === null) {
            $sender->sendTranslatedMessage("beInGang");
            return;
        }
        if(!$gang->getPermissionManager()->hasPermission($sender, PermissionManager::PERMISSION_USE_VAULT)) {
            $sender->sendTranslatedMessage("noPermission");
            return;
        }
        $sender->getDataSession()->getGang()->sendVault($sender);
    }
}