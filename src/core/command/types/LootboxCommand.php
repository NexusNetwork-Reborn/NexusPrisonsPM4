<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\inventory\LootboxInventory;
use core\command\inventory\WarpMenuInventory;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class LootboxCommand extends Command {

    /**
     * BankBlockCommand constructor.
     */
    public function __construct() {
        parent::__construct("lootbox", "View the most recent lootbox.", "/lootbox", ["lb"]);
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
        $warps = new LootboxInventory($sender);
        $warps->send($sender);
    }
}