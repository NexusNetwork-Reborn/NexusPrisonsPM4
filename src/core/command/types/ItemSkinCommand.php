<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\inventory\ItemSkinListSelectionInventory;
use core\player\NexusPlayer;
use core\translation\Translation;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class ItemSkinCommand extends Command {

    public function __construct() {
        parent::__construct("itemskin", "Change your current item skin", "/is", ["is"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if(!$sender instanceof NexusPlayer) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        $sender->sendDelayedWindow(new ItemSkinListSelectionInventory($sender, true));
    }
}