<?php
declare(strict_types=1);

namespace core\game\blackAuction\command\subCommands;

use core\command\utils\args\IntegerArgument;
use core\command\utils\SubCommand;
use core\game\auction\AuctionEntry;
use core\game\blackAuction\forms\SubmitBidForm;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class BidSubCommand extends SubCommand {

    /**
     * SellSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("bid", "/bah bid");
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
            $manager = Nexus::getInstance()->getGameManager()->getBlackAuctionManager();
            $active = $manager->getActiveAuction();
            if($active !== null) {
                $sender->sendDelayedForm(new SubmitBidForm($active, $active->getNextBidPrice()));
                return;
            }
            $sender->sendMessage(Translation::RED . "There are no active biddings!");
            return;
        }
        $sender->sendMessage(Translation::getMessage("noPermission"));
    }
}