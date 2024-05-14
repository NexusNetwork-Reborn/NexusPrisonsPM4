<?php

declare(strict_types = 1);

namespace core\command\forms;

use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\form\CustomForm;
use libs\form\CustomFormResponse;
use libs\form\element\Input;
use libs\form\element\Label;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class BankBlockDepositForm extends CustomForm {

    /**
     * BankBlockDepositForm constructor.
     *
     * @param NexusPlayer $player
     */
    public function __construct(NexusPlayer $player) {
        $title = TextFormat::BOLD . TextFormat::AQUA . "Deposit";
        $session = $player->getDataSession();
        $balance = $session->getBalance();
        $bb = $session->getBankBlock();
        $cap = $session->getBankBlockLimit();
        $elements[] = new Label("Info", "Balance: $" . number_format($balance) . "\nBank Block: $" . number_format($bb) . "\nCap: $" . number_format($cap));
        $elements[] = new Input("Amount", "How much like to deposit?");
        parent::__construct($title, $elements);
    }

    /**
     * @param Player $player
     * @param CustomFormResponse $data
     *
     * @throws TranslationException
     */
    public function onSubmit(Player $player, CustomFormResponse $data): void {
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $session = $player->getDataSession();
        $bb = $session->getBankBlock();
        $amount = $data->getString("Amount");
        if(!is_numeric($amount)) {
            $player->sendMessage(Translation::getMessage("invalidAmount"));
            return;
        }
        $amount = (int)$amount;
        if($amount <= 0) {
            $player->sendMessage(Translation::getMessage("invalidAmount"));
            return;
        }
        $cap = $session->getBankBlockLimit();
        $result = $amount + $bb;
        if($result > $cap) {
            $player->sendMessage(Translation::getMessage("invalidAmount"));
            return;
        }
        if($session->getBalance() < $amount) {
            $player->sendMessage(Translation::getMessage("notEnoughMoney"));
            return;
        }
        $player->sendMessage(Translation::GREEN . "You deposited " . TextFormat::YELLOW . "$" . number_format($amount) . TextFormat::GRAY . " into your bank block.");
        $session->subtractFromBalance($amount);
        $session->setBankBlock($result);
    }
}