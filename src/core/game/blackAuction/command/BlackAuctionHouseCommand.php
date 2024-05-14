<?php

namespace core\game\blackAuction\command;

use core\command\utils\Command;
use core\game\blackAuction\command\subCommands\BidSubCommand;
use core\game\blackAuction\inventory\BlackAuctionMainInventory;
use core\game\fund\FundManager;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class BlackAuctionHouseCommand extends Command {

    /**
     * AuctionHouseCommand constructor.
     */
    public function __construct() {
        parent::__construct("bah", "Open black market auction house menu", "/bah");
        $this->addSubCommand(new BidSubCommand());
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if(!Nexus::getInstance()->getGameManager()->getFundManager()->isUnlocked(FundManager::PHASE_SIX)) {
            $sender->sendTranslatedMessage("fundDisabled", [
                "feature" => TextFormat::RED . "Black Market Auction"
            ]);
            return;
        }
        if($sender instanceof NexusPlayer) {
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
            $inventory = new BlackAuctionMainInventory();
            $inventory->send($sender);
            return;
        }
        $sender->sendMessage(Translation::getMessage("noPermission"));
    }
}