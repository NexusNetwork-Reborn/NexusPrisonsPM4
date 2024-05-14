<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\inventory\LevelCapInventory;
use core\command\utils\args\IntegerArgument;
use core\command\utils\Command;
use core\game\auction\inventory\AuctionPageInventory;
use core\game\item\enchantment\EnchantmentManager;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rpg\XPUtils;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class LevelCapCommand extends Command {

    /**
     * LevelCapCommand constructor.
     */
    public function __construct() {
        parent::__construct("levelcap", "View level cap progress.", "/levelcap", ["lvlcap"]);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if($sender instanceof NexusPlayer) {
            $inventory = new LevelCapInventory();
            $inventory->send($sender);
            return;
        }
        $sender->sendMessage(Translation::getMessage("noPermission"));
    }

}