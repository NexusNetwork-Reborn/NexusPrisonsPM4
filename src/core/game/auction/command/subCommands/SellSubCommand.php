<?php
declare(strict_types=1);

namespace core\game\auction\command\subCommands;

use core\command\utils\args\IntegerArgument;
use core\command\utils\SubCommand;
use core\game\auction\AuctionEntry;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class SellSubCommand extends SubCommand {

    /**
     * SellSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("sell", "/ah sell <price> <amount>");
        $this->registerArgument(0, new IntegerArgument("price"));
        $this->registerArgument(1, new IntegerArgument("amount"));
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
                $manager = $this->getCore()->getGameManager()->getAuctionManager();
                $entries = $manager->getEntriesOf($sender);
                if(count($entries) >= $sender->getDataSession()->getRank()->getAuctionLimit()) {
                    $sender->sendTranslatedMessage("maxEntries");
                    return;
                }
                if(!isset($args[2])) {
                    $sender->sendTranslatedMessage("usageMessage", [
                        "usage" => $this->getUsage()
                    ]);
                    return;
                }
                $buyPrice = Utils::shortenToNumber($args[1]) !== null ? Utils::shortenToNumber($args[1]) : (int)$args[1];
                $amount = Utils::shortenToNumber($args[2]) !== null ? Utils::shortenToNumber($args[2]) : (int)$args[2];
                if($buyPrice <= 0) {
                    $sender->sendTranslatedMessage("invalidAmount");
                    $sender->sendTranslatedMessage("usageMessage", [
                        "usage" => $this->getUsage()
                    ]);
                    return;
                }
                if($amount <= 0) {
                    $sender->sendTranslatedMessage("invalidAmount");
                    $sender->sendTranslatedMessage("usageMessage", [
                        "usage" => $this->getUsage()
                    ]);
                    return;
                }
                $item = $sender->getInventory()->getItemInHand();
                if($item->isNull()) {
                    $sender->sendTranslatedMessage("invalidItem");
                    return;
                }
                if($item->getCount() < $amount) {
                    $sender->sendTranslatedMessage("invalidAmount");
                    $sender->sendTranslatedMessage("usageMessage", [
                        "usage" => $this->getUsage()
                    ]);
                    return;
                }
                $sender->getInventory()->setItemInHand($item->setCount($item->getCount() - $amount));
                $manager->addEntry(new AuctionEntry($item->setCount($amount), $sender->getName(), $this->getCore()->getGameManager()->getAuctionManager()->getNewIdentifier(), time(), $buyPrice));
                $name = $item->hasCustomName() ? $item->getCustomName() : $item->getName();
                $name .= TextFormat::RESET . TextFormat::GRAY . " * " . TextFormat::WHITE . $amount;
                $sender->sendTranslatedMessage("addAuctionEntry", [
                    "item" => $name,
                    "price" => TextFormat::LIGHT_PURPLE . "$" . number_format($buyPrice, 2),
                    "profit" => TextFormat::YELLOW . "$" . number_format($amount * $buyPrice, 2)
                ]);
                return;
            }
            $sender->sendTranslatedMessage("inWarzone");
            return;
        }
        $sender->sendMessage(Translation::getMessage("noPermission"));
    }
}