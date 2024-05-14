<?php

namespace core\game\auction\command;

use core\command\utils\Command;
use core\game\auction\command\subCommands\SellSubCommand;
use core\game\auction\inventory\AuctionPageInventory;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;

class AuctionHouseCommand extends Command {

    /**
     * AuctionHouseCommand constructor.
     */
    public function __construct() {
        parent::__construct("auctionhouse", "Open auction house menu", "/ah sell <price> <amount>", ["ah"]);
        $this->addSubCommand(new SellSubCommand());
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
            if(!Nexus::getInstance()->getServerManager()->getAreaManager()->canDamage($sender->getPosition())) {
                if(isset($args[0])) {
                    $subCommand = $this->getSubCommand($args[0]);
                    if($subCommand !== null) {
                        $subCommand->execute($sender, $commandLabel, $args);
                        return;
                    }
                    $sender->sendTranslatedMessage("usageMessage", [
                        "usage" => $this->getUsage()
                    ]);
                    return;
                }
                $inventory = new AuctionPageInventory();
                $inventory->send($sender);
                return;
            }
            $sender->sendTranslatedMessage("inWarzone");
            return;
        }
        $sender->sendMessage(Translation::getMessage("noPermission"));
    }
}