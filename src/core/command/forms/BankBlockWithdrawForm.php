<?php

declare(strict_types = 1);

namespace core\command\forms;

use core\game\item\types\custom\MoneyNote;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\form\CustomForm;
use libs\form\CustomFormResponse;
use libs\form\element\Input;
use libs\form\element\Label;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class BankBlockWithdrawForm extends CustomForm {

    /**
     * BankBlockWithdrawForm constructor.
     *
     * @param NexusPlayer $player
     */
    public function __construct(NexusPlayer $player) {
        $title = TextFormat::BOLD . TextFormat::AQUA . "Withdraw";
        $session = $player->getDataSession();
        $bb = $session->getBankBlock();
        $elements[] = new Label("Info", "Bank Block: $" . number_format($bb));
        $elements[] = new Label("Penalty", TextFormat::BOLD . TextFormat::RED . "PENALTY\n" . TextFormat::RESET . TextFormat::RED . "Players below level 100 will have a 25%%% withdrawal fee for taking out your investment too soon!");
        $elements[] = new Input("Amount", "How much like to withdraw?");
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
        if(count($player->getInventory()->getContents()) === $player->getInventory()->getSize()) {
            $player->sendMessage(Translation::getMessage("fullInventory"));
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
        if($amount <= 0 or $amount > $bb) {
            $player->sendMessage(Translation::getMessage("invalidAmount"));
            return;
        }
        $fee = 0;
        if($session->getTotalXPLevel() < 100) {
            $fee = (int)ceil($amount * 0.25);
        }
        $give = $amount - $fee;
        $session->setBankBlock($bb - $amount);
        if($fee > 0) {
            $player->sendMessage(TextFormat::GRAY . "(-25% tax)");
        }
        $player->getInventory()->addItem((new MoneyNote($give, $player->getName()))->toItem());
    }
}