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

class DemoteSubCommand extends SubCommand {

    /**
     * DemoteSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("demote", "/gang demote <player>");
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
        if(!isset($args[1])) {
            $sender->sendTranslatedMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]);
            return;
        }
        $player = $this->getCore()->getServer()->getPlayerByPrefix($args[1]);
        if(!$player instanceof NexusPlayer) {
            $name = $args[1];
            $stmt = $this->getCore()->getMySQLProvider()->getDatabase()->prepare("SELECT gang, gangRole FROM stats WHERE username = ?");
            $stmt->bind_param("s", $name);
            $stmt->execute();
            $stmt->bind_result($gang, $gangRole);
            $stmt->fetch();
            $stmt->close();
            if($gang === null and $gangRole === null) {
                $sender->sendTranslatedMessage("invalidPlayer");
                return;
            }
        }
        else {
            if($player->getName() === $sender->getName()) {
                $sender->sendTranslatedMessage("invalidPlayer");
                return;
            }
            $gang = $player->getDataSession()->getGang()->getName();
            $gangRole = $player->getDataSession()->getGangRole();
            $name = $player->getName();
        }
        if($gang !== $sender->getDataSession()->getGang()->getName()) {
            $sender->sendTranslatedMessage("notGangMember", [
                "name" => TextFormat::RED . $name
            ]);
            return;
        }
        if($gangRole >= $sender->getDataSession()->getGangRole() or $gangRole <= Gang::RECRUIT) {
            $sender->sendTranslatedMessage("cannotDemote", [
                "name" => TextFormat::RED . $name
            ]);
            return;
        }
        if(!$player instanceof NexusPlayer) {
            $stmt = $this->getCore()->getMySQLProvider()->getDatabase()->prepare("UPDATE players SET gangRole = gangRole - 1 WHERE username = ?");
            var_dump("Demotion Command Error");
            if(!is_bool($stmt)) {
                $stmt->bind_param("s", $name);
                $stmt->execute();
                $stmt->close();
            }
        }
        else {
            $player->getDataSession()->setGangRole($gangRole - 1);
        }
        foreach($sender->getDataSession()->getGang()->getOnlineMembers() as $member) {
            $member->sendTranslatedMessage("demoted", [
                "name" => TextFormat::AQUA . $name,
                "sender" => TextFormat::LIGHT_PURPLE . $sender->getName()
            ]);
        }
    }
}