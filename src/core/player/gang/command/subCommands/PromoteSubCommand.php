<?php
declare(strict_types=1);

namespace core\player\gang\command\subCommands;

use core\command\utils\args\TargetArgument;
use core\command\utils\SubCommand;
use core\player\gang\Gang;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class PromoteSubCommand extends SubCommand {

    /**
     * PromoteSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("promote", "/gang promote <player>");
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
        if($sender->getDataSession()->getGang() === null) {
            $sender->sendTranslatedMessage("beInGang");
            return;
        }
        if($sender->getDataSession()->getGangRole() !== Gang::LEADER) {
            $sender->sendTranslatedMessage("noPermission");
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
        if($player->getName() === $sender->getName()) {
            $sender->sendTranslatedMessage("noPermission");
            return;
        }
        if(!$sender->getDataSession()->getGang()->isInGang($player->getName())) {
            $sender->sendTranslatedMessage("notGangMember", [
                "name" => TextFormat::RED . $player->getName()
            ]);
            return;
        }
        if($player->getDataSession()->getGangRole() === Gang::OFFICER) {
            $sender->sendTranslatedMessage("cannotPromote", [
                "name" => TextFormat::RED . $player->getName()
            ]);
            return;
        }
        foreach($sender->getDataSession()->getGang()->getOnlineMembers() as $member) {
            $member->sendTranslatedMessage("promoted", [
                "name" => TextFormat::AQUA . $player->getName(),
                "sender" => TextFormat::LIGHT_PURPLE . $sender->getName()
            ]);
        }
        $player->getDataSession()->setGangRole($player->getDataSession()->getGangRole() + 1);
    }
}