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

class InfoSubCommand extends SubCommand {

    /**
     * InfoSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("info", "/gang info [gang/player]", ["who", "f"]);
        $this->registerArgument(0, new TargetArgument("gang/player"));
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
            $gang = $sender->getDataSession()->getGang();
            if($gang === null) {
                $sender->sendTranslatedMessage("beInGang");
                return;
            }
        }
        else {
            $player = $this->getCore()->getServer()->getPlayerByPrefix($args[1]);
            if($player instanceof NexusPlayer) {
                if(!$player->isLoaded()) {
                    $sender->sendMessage(Translation::getMessage("errorOccurred"));
                    return;
                }
                $gang = $player->getDataSession()->getGang();
            }
            else {
                $gang = $this->getCore()->getPlayerManager()->getGangManager()->getGang($args[1]);
            }
            if($gang === null) {
                $sender->sendTranslatedMessage("invalidGang");
                return;
            }
        }
        $sender->sendMessage(TextFormat::LIGHT_PURPLE . TextFormat::BOLD . $gang->getName() . TextFormat::RESET . TextFormat::DARK_GRAY . " [" . TextFormat::GRAY . count($gang->getMembers()) . "/" . Gang::MAX_MEMBERS . TextFormat::DARK_GRAY . "]");
        $role = Gang::LEADER;
        $name = $gang->getName();
        $stmt = $this->getCore()->getMySQLProvider()->getDatabase()->prepare("SELECT username FROM stats WHERE gang = ? and gangRole = ?");
        $stmt->bind_param("si", $name, $role);
        $stmt->execute();
        $stmt->bind_result($leader);
        $stmt->fetch();
        $stmt->close();
        $members = [];
        foreach($gang->getMembers() as $member) {
            /** @var NexusPlayer $player */
            if(($player = $this->getCore()->getServer()->getPlayerByPrefix($member)) !== null) {
                if($player->isDisguise()) {
                    $members[] = TextFormat::WHITE . $player->getName();
                    continue;
                }
                $members[] = TextFormat::GREEN . $player->getName();
                continue;
            }
            $members[] = TextFormat::WHITE . $member;
        }
        $sender->sendMessage(TextFormat::AQUA . " Leader: " . TextFormat::WHITE . $leader);
        $sender->sendMessage(TextFormat::AQUA . " Members: " . implode(TextFormat::GRAY . ", ", $members));
        $sender->sendMessage(TextFormat::AQUA . " Allies: " . TextFormat::WHITE . implode(", ", $gang->getAllies()));
        $sender->sendMessage(TextFormat::AQUA . " Enemies: " . TextFormat::WHITE . implode(", ", $gang->getEnemies()));
        $sender->sendMessage(TextFormat::AQUA . " Assets: " . TextFormat::WHITE . "$" . number_format($gang->getBalance()));
        $sender->sendMessage(TextFormat::AQUA . " Reputation: " . TextFormat::WHITE  . number_format($gang->getValue()));
    }
}