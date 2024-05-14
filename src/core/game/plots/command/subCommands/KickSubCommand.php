<?php
declare(strict_types=1);

namespace core\game\plots\command\subCommands;

use core\command\utils\args\TargetArgument;
use core\command\utils\SubCommand;
use core\Nexus;
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
        parent::__construct("kick", "/gang kick <player>", ["remove"]);
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
        $plot = Nexus::getInstance()->getGameManager()->getPlotManager()->getPlotByOwner($sender->getName());
        if($plot === null) {
            $sender->sendMessage(Translation::RED . "You must own a plot to kick someone!");
            return;
        }
        $owner = $plot->getOwner();
        if(!isset($args[1])) {
            $sender->sendTranslatedMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]);
            return;
        }
        $player = $this->getCore()->getServer()->getPlayerByPrefix($args[1]) !== null ? $this->getCore()->getServer()->getPlayerByPrefix($args[1])->getName() : $args[1];
        if($owner->getUser($player) === null) {
            $sender->sendTranslatedMessage("invalidPlayer");
            return;
        }
        if($player === $sender->getName()) {
            $sender->sendTranslatedMessage("invalidPlayer");
            return;
        }
        if($sender->getDataSession()->getGang() !== null) {
            foreach ($sender->getDataSession()->getGang()->getOnlineMembers() as $member) {
                if ($member->getName() === $player) {
                    $member->sendTranslatedMessage("plotAccessRevoked", [
                        "plot" => TextFormat::WHITE . "Plot " . TextFormat::BOLD . TextFormat::AQUA . $plot->getId()
                    ]);
                }
            }
        }
        $owner->removeUser($player);
    }
}