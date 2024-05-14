<?php

declare(strict_types = 1);

namespace core\game\item\sets\command;

use core\command\utils\args\RawStringArgument;
use core\command\utils\args\TargetArgument;
use core\command\utils\Command;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\permission\DefaultPermissions;

class GiveSetCommand extends Command
{

    public function __construct()
    {
        parent::__construct("giveset", "Give a player a set.", "/giveset <player> <set>");
        $this->registerArgument(0, new TargetArgument("player"));
        $this->registerArgument(1, new RawStringArgument("set"));
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return void
     * @throws \core\translation\TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if(!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }

        if(!isset($args[0]) || !isset($args[1])) {
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]));
            return;
        }

        $player = $this->getCore()->getServer()->getPlayerByPrefix($args[0]);

        if(!$player instanceof NexusPlayer) {
            $sender->sendMessage(Translation::getMessage("invalidPlayer"));
            return;
        }

        $set = Nexus::getInstance()->getGameManager()->getItemManager()->getSetManager()->getSet($args[1]);

        if($set === null) {
            $sender->sendMessage(Translation::getMessage("invalidSet"));
            return;
        }

        foreach ($set->compileSet() as $item) {
            $player->getInventory()->addItem($item);
        }

        $sender->sendMessage(Translation::getMessage("successAbuse"));
    }
}