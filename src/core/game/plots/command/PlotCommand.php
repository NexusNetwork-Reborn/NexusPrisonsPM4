<?php

namespace core\game\plots\command;

use core\command\utils\Command;
use core\game\plots\command\inventory\PlotMenuInventory;
use core\game\plots\command\subCommands\FloorSubCommand;
use core\game\plots\command\subCommands\InfoSubCommand;
use core\game\plots\command\subCommands\InviteSubCommand;
use core\game\plots\command\subCommands\KickSubCommand;
use core\game\plots\command\subCommands\ManageSubCommand;
use core\game\plots\command\subCommands\WarpSubCommand;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;

class PlotCommand extends Command {

    /**
     * PlotCommand constructor.
     */
    public function __construct() {
        parent::__construct("plot", "Open plot menu", "/plot", ["p"]);
//        $this->addSubCommand(new CreateSubCommand());
//        $this->addSubCommand(new DeleteSubCommand());
//        $this->addSubCommand(new InvalidateSubCommand());
        $this->addSubCommand(new InviteSubCommand());
        $this->addSubCommand(new KickSubCommand());
        $this->addSubCommand(new ManageSubCommand());
        $this->addSubCommand(new WarpSubCommand());
        $this->addSubCommand(new InfoSubCommand());
        $this->addSubCommand(new FloorSubCommand());
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
            if(isset($args[0])) {
                $subCommand = $this->getSubCommand($args[0]);
                if($subCommand !== null) {
                    $subCommand->execute($sender, $commandLabel, $args);
                    return;
                }
            }
            $inventory = new PlotMenuInventory();
            $sender->sendDelayedWindow($inventory);
            return;
        }
        $sender->sendMessage(Translation::getMessage("noPermission"));
    }
}