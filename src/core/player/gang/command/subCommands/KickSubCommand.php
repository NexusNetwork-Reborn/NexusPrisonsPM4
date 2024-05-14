<?php
declare(strict_types=1);

namespace core\player\gang\command\subCommands;

use core\command\utils\args\TargetArgument;
use core\command\utils\SubCommand;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class KickSubCommand extends SubCommand {

    /**
     * KickSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("kick", "/gang kick <player>");
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
        $player = $this->getCore()->getServer()->getPlayerByPrefix($args[1]) !== null ? $this->getCore()->getServer()->getPlayerByPrefix($args[1]) : $args[1];

        if($player instanceof NexusPlayer && $player->getDataSession() != null) {
            if($player->getName() === $sender->getName()) {
                $sender->sendTranslatedMessage("invalidPlayer");
                return;
            }
            $role = $player->getDataSession()->getGangRole();
            $name = $player->getName();
        }
        else {
            $stmt = $this->getCore()->getMySQLProvider()->getDatabase()->prepare("SELECT gangRole FROM stats WHERE username = ?");
            $stmt->bind_param("s", $player);
            $stmt->execute();
            $stmt->bind_result($role);
            $stmt->fetch();
            $stmt->close();
            $name = $args[1];
        }
        if(!$sender->getDataSession()->getGang()->isInGang($name)) {
            $sender->sendTranslatedMessage("invalidPlayer");
            return;
        }
        if($sender->getDataSession()->getGangRole() <= $role) {
            $sender->sendTranslatedMessage("noPermission");
            return;
        }
        foreach($sender->getDataSession()->getGang()->getOnlineMembers() as $member) {
            $member->sendTranslatedMessage("gangLeave", [
                "name" => TextFormat::AQUA . $name
            ]);
        }
        $sender->getDataSession()->getGang()->removeMember($name);
    }
}