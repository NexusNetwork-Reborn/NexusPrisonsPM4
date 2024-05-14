<?php
declare(strict_types=1);

namespace core\player\gang\command\subCommands;

use core\command\utils\args\RawStringArgument;
use core\command\utils\SubCommand;
use core\player\gang\Gang;
use core\player\gang\PermissionManager;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class AllySubCommand extends SubCommand {

    /**
     * AllySubCommand constructor.
     */
    public function __construct() {
        parent::__construct("ally", "/gang ally <gang>");
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
        if(!isset($args[1])) {
            $sender->sendTranslatedMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]);
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
        $gang = $this->getCore()->getPlayerManager()->getGangManager()->getGang($args[1]);
        if($gang === null or $senderGang->getName() === $gang->getName()) {
            $sender->sendTranslatedMessage("invalidGang");
            return;
        }
        if(count($gang->getAllies()) >= Gang::MAX_ALLIES) {
            $sender->sendTranslatedMessage("gangMaxAllies", [
                "gang" => TextFormat::RED . $gang->getName()
            ]);
            return;
        }
        if($senderGang->isAlly($gang)) {
            $sender->sendMessage(Translation::getMessage("alreadyAllied"));
            return;
        }
        if($senderGang->isEnemy($gang)) {
            $sender->sendMessage(Translation::getMessage("cantAlly"));
            return;
        }
        if($gang->isAllying($senderGang)) {
            $senderGang->addAlly($gang);
            $gang->addAlly($senderGang);
            foreach($gang->getOnlineMembers() as $member) {
                if(!$member->isLoaded()) {
                    continue;
                }
                $member->getDataSession()->updateNameTag($senderGang->getOnlineMembers());
                $member->sendTranslatedMessage("allyAdd", [
                    "gang" => TextFormat::DARK_AQUA . $senderGang->getName()
                ]);
            }
            foreach($senderGang->getOnlineMembers() as $member) {
                if(!$member->isLoaded()) {
                    continue;
                }
                $member->getDataSession()->updateNameTag($gang->getOnlineMembers());
                $member->sendTranslatedMessage("allyAdd", [
                    "gang" => TextFormat::DARK_AQUA . $gang->getName()
                ]);
            }
        }
        else {
            $senderGang->addAllyRequest($gang);
            foreach($gang->getOnlineMembers() as $member) {
                $member->sendTranslatedMessage("allyRequest", [
                    "senderGang" => TextFormat::AQUA . $senderGang->getName(),
                    "gang" => TextFormat::DARK_AQUA . $gang->getName()
                ]);
            }
            foreach($senderGang->getOnlineMembers() as $member) {
                $member->sendTranslatedMessage("allyRequest", [
                    "senderGang" => TextFormat::AQUA . $senderGang->getName(),
                    "gang" => TextFormat::DARK_AQUA . $gang->getName()
                ]);
            }
        }
    }
}