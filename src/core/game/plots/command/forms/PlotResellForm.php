<?php

namespace core\game\plots\command\forms;

use core\game\plots\plot\Plot;
use core\game\plots\plot\PlotOwner;
use core\game\plots\PlotManager;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\form\ModalForm;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class PlotResellForm extends ModalForm {

    /** @var Plot */
    private $plot;

    /**
     * PlotCreateConfirmationForm constructor.
     *
     * @param Plot $plot
     */
    public function __construct(Plot $plot) {
        $this->plot = $plot;
        $title = TextFormat::BOLD . TextFormat::YELLOW . "Plot Resell";
        $price = PlotManager::getPlotPrice($plot) * 0.1;
        $text = "Are you sure you would like to resell Plot {$plot->getId()}?\n \nPrice: $" . number_format($price) . "\nSecurity: " . ucfirst($plot->getWorld()->getFolderName());
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
        $owner = $this->plot->getOwner();
        if($owner === null or $owner->getUsername() !== $player->getName()) {
            $player->sendMessage(Translation::RED . "You no longer own this plot!");
            return;
        }
        if($choice == true) {
            $price = PlotManager::getPlotPrice($this->plot) * 0.1;
            $player->sendTitle(TextFormat::BOLD . TextFormat::GREEN . "SUCCESS", TextFormat::GRAY . "You resold " . TextFormat::WHITE . "Plot " . TextFormat::BOLD . TextFormat::AQUA . $this->plot->getId());
            $player->getDataSession()->addToBalance($price);
            $this->plot->delete();
            $this->plot->clearPlot();
            $this->plot->setOwner(null);
            $player->playLaunchSound();
        }
    }
}