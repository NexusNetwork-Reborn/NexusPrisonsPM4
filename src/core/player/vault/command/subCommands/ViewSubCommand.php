<?php

declare(strict_types = 1);

namespace core\player\vault\command\subCommands;

use core\command\utils\SubCommand;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\command\CommandSender;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class ViewSubCommand extends SubCommand {

    /**
     * ViewSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("view", "/pv view <player>");
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
        if((!$sender->hasPermission("permission.mod")) and (!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR))) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        if(!isset($args[1])) {
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]));
            return;
        }
        $target = $args[1];
        $vaults = $this->getCore()->getPlayerManager()->getVaultManager()->getVaultsFor($target);
        if(empty($vaults)) {
            $sender->sendMessage(Translation::getMessage("invalidPlayer"));
            return;
        }
        $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
        foreach($vaults as $id => $vault) {
            $menu->getInventory()->setItem($id - 1, ItemFactory::getInstance()->get(ItemIds::CHEST, 0, 1)->setCustomName(TextFormat::RESET . TextFormat::YELLOW . "PV $id"));
        }
        $menu->setListener(InvMenu::readonly(function(DeterministicInvMenuTransaction $transaction) use($vaults): void {
            $player = $transaction->getPlayer();
            $itemClicked = $transaction->getItemClicked();
            $number = $transaction->getAction()->getSlot() + 1;
            if(!$player instanceof NexusPlayer) {
                return;
            }
            if($itemClicked->getId() !== ItemIds::AIR) {
                $vault = $vaults[$number];
                $player->removeCurrentWindow();
                $player->sendDelayedWindow($vault->getMenu());
            }
            return;
        }));
        $menu->send($sender);
    }
}