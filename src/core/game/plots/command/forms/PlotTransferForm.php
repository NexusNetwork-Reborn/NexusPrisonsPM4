<?php

declare(strict_types = 1);

namespace core\game\plots\command\forms;

use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use libs\form\CustomForm;
use libs\form\CustomFormResponse;
use libs\form\element\Input;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class PlotTransferForm extends CustomForm {

    /**
     * PlotTransferForm constructor.
     */
    public function __construct() {
        $title = TextFormat::BOLD . TextFormat::YELLOW . "Transfer Ownership";
        $elements = [];
        $elements[] = new Input("Username", "Enter the name of an online user");
        parent::__construct($title, $elements);
    }

    /**
     * @param Player $player
     * @param CustomFormResponse $data
     */
    public function onSubmit(Player $player, CustomFormResponse $data): void {
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $search = $data->getString("Username");
        $transfer = Server::getInstance()->getPlayerByPrefix($search);
        if(!$transfer instanceof NexusPlayer) {
            $player->sendMessage(Translation::getMessage("invalidPlayer"));
            return;
        }
        $plotManager = Nexus::getInstance()->getGameManager()->getPlotManager();
        foreach($transfer->getAlias() as $alias) {
            if($plotManager->getPlotByOwner($alias) !== null) {
                $player->playErrorSound();
                $player->sendMessage(Translation::RED . $transfer->getName() . " already owns a plot!");
                return;
            }
        }
        $plot = $plotManager->getPlotByOwner($player->getName());
        if($plot === null) {
            $player->playErrorSound();
            $player->sendMessage(Translation::RED . "You must own a plot to transfer ownership!");
            return;
        }
        $player->sendDelayedForm(new PlotTransferConfirmationForm($plot, $transfer));
    }
}