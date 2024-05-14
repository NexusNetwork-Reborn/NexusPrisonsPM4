<?php

declare(strict_types = 1);

namespace core\command\types;

use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\InvMenu;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class TrashCommand extends Command {

    /**
     * TrashCommand constructor.
     */
    public function __construct() {
        parent::__construct("trash", "Open trash bin");
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
        $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
        $menu->setName(TextFormat::RED . "Trash Bin");
        $menu->setInventoryCloseListener(function(Player $player, InvMenuInventory $inventory) {
            $inventory->clearAll();
        });
        $menu->send($sender);
    }
}