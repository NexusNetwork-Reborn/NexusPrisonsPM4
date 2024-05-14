<?php
declare(strict_types=1);

namespace core\player\gang\command\subCommands;

use core\command\utils\args\RawStringArgument;
use core\command\utils\SubCommand;
use core\player\gang\PermissionManager;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class UnallySubCommand extends SubCommand {

    /**
     * UnallySubCommand constructor.
     */
    public function __construct() {
        parent::__construct("unally", "/gang unally <gang>");
        $this->registerArgument(0, new RawStringArgument("gang"));
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
        $senderGang = $sender->getDataSession()->getGang();
        if($senderGang === null) {
            $sender->sendTranslatedMessage("beInGang");
            return;
        }
        if(!$senderGang->getPermissionManager()->hasPermission($sender, PermissionManager::PERMISSION_ALLY)) {
            $sender->sendTranslatedMessage("noPermission");
            return;
        }
        if(!isset($args[1])) {
            $sender->sendTranslatedMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]);
            return;
        }
        $gang = $this->getCore()->getPlayerManager()->getGangManager()->getGang($args[1]);
        if($gang === null or $senderGang->getName() === $gang->getName()) {
            $sender->sendTranslatedMessage("invalidGang");
            return;
        }
        $gang->removeAlly($senderGang);
        $senderGang->removeAlly($gang);
        foreach($senderGang->getOnlineMembers() as $member) {
            if(!$member->isLoaded()) {
                continue;
            }
            $member->getDataSession()->updateNameTag($gang->getOnlineMembers());
            $member->sendTranslatedMessage("unally", [
                "gang" => TextFormat::AQUA . $gang->getName()
            ]);
        }
        foreach($gang->getOnlineMembers() as $member) {
            if(!$member->isLoaded()) {
                continue;
            }
            $member->getDataSession()->updateNameTag($senderGang->getOnlineMembers());
            $member->sendTranslatedMessage("unally", [
                "gang" => TextFormat::AQUA . $senderGang->getName()
            ]);
        }
    }
}