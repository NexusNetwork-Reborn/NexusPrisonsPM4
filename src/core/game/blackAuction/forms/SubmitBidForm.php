<?php

namespace core\game\blackAuction\forms;

use core\command\task\TeleportTask;
use core\game\blackAuction\BlackAuctionEntry;
use core\game\plots\plot\Plot;
use core\game\plots\plot\PlotOwner;
use core\game\plots\PlotManager;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\form\ModalForm;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class SubmitBidForm extends ModalForm {

    /** @var BlackAuctionEntry */
    private $entry;

    /** @var int */
    private $bid;

    /**
     * SubmitBidForm constructor.
     *
     * @param BlackAuctionEntry $entry
     * @param int $bid
     */
    public function __construct(BlackAuctionEntry $entry, int $bid) {
        $this->entry = $entry;
        $this->bid = $bid;
        $item = $entry->getItem();
        $title = TextFormat::BOLD . TextFormat::GOLD . "Black Market Auction";
        $text = "Are you sure you would like to bid $" . number_format($bid) . " for: \n \n" . implode("\n", array_merge([$entry->getItemName()], $item->getLore()));
        parent::__construct($title, $text);
    }

    /**
     * @param Player $player
     * @param bool $choice
     *
     * @throws TranslationException
     */
    public function onSubmit(Player $player, bool $choice): void {
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if($this->entry->getTimeLeft() <= 0) {
            $player->sendMessage(Translation::RED . "This bidding has already ended!");
            return;
        }
        if($choice == true) {
            if($player->getDataSession()->getBalance() < $this->bid) {
                $player->sendMessage(Translation::getMessage("notEnoughMoney"));
                return;
            }
            $this->entry->placeBid($player, $this->bid);
        }
    }
}