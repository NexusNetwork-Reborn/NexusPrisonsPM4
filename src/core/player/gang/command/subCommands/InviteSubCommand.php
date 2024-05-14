<?php
declare(strict_types=1);

namespace core\player\gang\command\subCommands;

use core\command\utils\args\TargetArgument;
use core\command\utils\SubCommand;
use core\player\gang\Gang;
use core\player\gang\PermissionManager;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class InviteSubCommand extends SubCommand {

    /**
     * InviteSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("invite", "/gang invite <player>");
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
        if(!$sender instanceof NexusPlayer) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        $gang = $sender->getDataSession()->getGang();
        if($gang === null) {
            $sender->sendTranslatedMessage("beInGang");
            return;
        }
        if(!$gang->getPermissionManager()->hasPermission($sender, PermissionManager::PERMISSION_INVITE)) {
            $sender->sendTranslatedMessage("noPermission");
            return;
        }
        if(count($sender->getDataSession()->getGang()->getMembers()) >= Gang::MAX_MEMBERS) {
            $sender->sendTranslatedMessage("gangMaxMembers", [
                "gang" => TextFormat::RED . $sender->getDataSession()->getGang()->getName()
            ]);
            return;
        }
        if(!isset($args[1])) {
            $sender->sendTranslatedMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]);
            return;
        }
        $player = $this->getCore()->getServer()->getPlayerByPrefix($args[1]);
        if(!$player instanceof NexusPlayer) {
            $sender->sendTranslatedMessage("invalidPlayer");
            return;
        }
        if(!$player->isLoaded()) {
            $sender->sendTranslatedMessage("invalidPlayer");
            return;
        }
        if($player->getDataSession()->getGang() !== null) {
            $sender->sendMessage(Translation::getMessage("invalidPlayer"));
            return;
        }
        $gang->addInvite($player);
        $sender->sendTranslatedMessage("inviteSentSender", [
            "name" => TextFormat::AQUA . $player->getName()
        ]);
        $player->sendTranslatedMessage("inviteSentPlayer", [
            "gang" => TextFormat::AQUA . $gang->getName()
        ]);
    }
}