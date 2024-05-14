<?php

declare(strict_types = 1);

namespace core\command\forms;

use core\game\item\slotbot\SlotBotTicket;
use core\game\item\types\custom\Energy;
use core\game\item\types\custom\Lootbox;
use core\game\item\types\custom\MoneyNote;
use core\game\item\types\custom\MysteryEnchantmentBook;
use core\game\item\types\Rarity;
use core\game\rewards\RewardsManager;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\form\MenuForm;
use libs\form\MenuOption;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class VoteShopForm extends MenuForm {

    /**
     * VoteShopForm constructor.
     *
     * @param NexusPlayer $player
     */
    public function __construct(NexusPlayer $player) {
        $title = TextFormat::BOLD . TextFormat::AQUA . "Vote Shop";
        $text = "Vote points: " . $player->getDataSession()->getVotePoints();
        $options = [];
        $options[] = new MenuOption("Vote Lootbox (1 Points)");
        $options[] = new MenuOption("Cosmo-SlotBot Ticket (5 Points)");
        $options[] = new MenuOption("100,000 Energy (10 Points)");
        $options[] = new MenuOption("x5 Godly Enchant (15 Points)");
        $options[] = new MenuOption("500,000 Energy (20 Points)");
        $options[] = new MenuOption("Crash Landing Lootbox (30 Points)");
        parent::__construct($title, $text, $options);
    }

    /**
     * @param Player $player
     * @param int $selectedOption
     *
     * @throws TranslationException
     */
    public function onSubmit(Player $player, int $selectedOption): void {
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $option = $this->getOption($selectedOption);
        if($player->getInventory()->getSize() === count($player->getInventory()->getContents())) {
            $player->sendMessage(Translation::getMessage("fullInventory"));
            return;
        }
        switch($option->getText()) {
            case "Vote Lootbox (1 Points)":
                $points = 1;
                $item = (new Lootbox(RewardsManager::VOTE))->toItem()->setCount(1);
                break;
            case "Cosmo-SlotBot Ticket (5 Points)":
                $points = 5;
                $item = (new SlotBotTicket("Normal"))->toItem()->setCount(1);
                break;
            case "100,000 Energy (10 Points)":
                $points = 10;
                $item = (new Energy(100000))->toItem()->setCount(1);
                break;
            case "x5 Godly Enchant (15 Points)":
                $points = 15;
                $item = (new MysteryEnchantmentBook(Rarity::GODLY))->toItem()->setCount(5);
                break;
            case "500,000 Energy (20 Points)":
                $points = 20;
                $item = (new Energy(500000))->toItem()->setCount(1);
                break;
            case "Crash Landing Lootbox (30 Points)":
                $points = 30;
                $item = (new Lootbox(RewardsManager::CRASH_LANDING, time() + 604800))->toItem()->setCount(1);
                break;
            default:
                return;
        }
        if($player->getDataSession()->getVotePoints() < $points) {
            $player->sendMessage(Translation::getMessage("notEnoughPoints"));
            return;
        }
        $player->getDataSession()->subtractVotePoints($points);
        $player->sendMessage(Translation::getMessage("buy", [
            "amount" => TextFormat::AQUA . "x1",
            "item" => TextFormat::AQUA . $item->getCustomName(),
            "price" => TextFormat::LIGHT_PURPLE . "$points vote points",
        ]));
        $player->getInventory()->addItem($item);
    }
}