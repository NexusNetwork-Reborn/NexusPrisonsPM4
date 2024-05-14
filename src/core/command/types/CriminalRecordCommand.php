<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\args\IntegerArgument;
use core\command\utils\args\TargetArgument;
use core\command\utils\Command;
use core\game\item\enchantment\EnchantmentManager;
use core\player\NexusPlayer;
use core\player\rpg\XPUtils;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class CriminalRecordCommand extends Command {

    /**
     * CriminalRecordCommand constructor.
     */
    public function __construct() {
        parent::__construct("criminalrecord", "Check a player's criminal record.", "/criminalrecord [player]", ["crimrec", "crec", "crecord"]);
        $this->registerArgument(0, new TargetArgument("player", true));
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
        $name = "Your";
        $killed = $sender->getDataSession()->getGuardsKilled();
        if(isset($args[0])) {
            $player = $this->getCore()->getServer()->getPlayerByPrefix($args[0]);
            if(!$player instanceof NexusPlayer) {
                $sender->sendMessage(Translation::getMessage("invalidPlayer"));
                return;
            }
            else {
                $name = $player->getName() . "'s";
                $killed = $player->getDataSession()->getGuardsKilled();
            }
        }
        $sender->sendMessage(Translation::getMessage("criminalRecord", [
            "name" => $name,
            "amount" => TextFormat::YELLOW . number_format($killed),
            "multiplier" => TextFormat::WHITE . "x" . number_format( 1 + ($killed * 0.5), 1)
        ]));
    }
}