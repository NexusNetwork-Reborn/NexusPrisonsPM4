<?php

declare(strict_types = 1);

namespace core\command\forms;

use core\player\NexusPlayer;
use libs\form\FormIcon;
use libs\form\MenuForm;
use libs\form\MenuOption;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class BankBlockMainForm extends MenuForm {

    /**
     * BankBlockMainForm constructor.
     *
     * @param NexusPlayer $player
     */
    public function __construct(NexusPlayer $player) {
        if($player->isLoaded()) {
            $session = $player->getDataSession();
            $bb = $session->getBankBlock();
            $cap = $session->getBankBlockLimit();
            $title = TextFormat::BOLD . TextFormat::AQUA . "Bank Block";
            $text = "What would you like to do?\nBank Block: $" . number_format($bb, 2) . "\nLimit: $" . number_format($cap, 2) . "\nInterest Rate: +3.25%%%";
            $options = [];
            $options[] = new MenuOption("Withdraw", new FormIcon("http://www.aethic.games/images/coin.png", FormIcon::IMAGE_TYPE_URL));
            $options[] = new MenuOption("Deposit", new FormIcon("http://www.aethic.games/images/envelope.png", FormIcon::IMAGE_TYPE_URL));
            parent::__construct($title, $text, $options);
        }
    }

    /**
     * @param Player $player
     * @param int $selectedOption
     */
    public function onSubmit(Player $player, int $selectedOption): void {
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $option = $this->getOption($selectedOption);
        $text = $option->getText();
        switch($text) {
            case "Withdraw":
                $player->sendForm(new BankBlockWithdrawForm($player));
                break;
            case "Deposit":
                $player->sendForm(new BankBlockDepositForm($player));
                break;
        }
    }
}