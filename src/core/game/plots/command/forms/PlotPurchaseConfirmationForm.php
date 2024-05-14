<?php

namespace core\game\plots\command\forms;

use core\command\task\TeleportTask;
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

class PlotPurchaseConfirmationForm extends ModalForm {

    /** @var Plot */
    private $plot;

    /**
     * PlotCreateConfirmationForm constructor.
     *
     * @param Plot $plot
     */
    public function __construct(Plot $plot) {
        $this->plot = $plot;
        $title = TextFormat::BOLD . TextFormat::YELLOW . "Plot Purchase";
        $price = PlotManager::getPlotPrice($plot);
        $text = "Are you sure you would like to purchase Plot {$plot->getId()}?\n \nPrice: $" . number_format($price) . "\nSecurity: " . ucfirst($plot->getWorld()->getFolderName());
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
        if($this->plot->getOwner() !== null and $this->plot->getExpiration() > 0) {
            $player->sendMessage(Translation::RED . "This plot has already been purchased by another user!");
            return;
        }
        if($choice == true) {
            $price = PlotManager::getPlotPrice($this->plot);
            if($player->getDataSession()->getBalance() < $price) {
                $player->sendTranslatedMessage("notEnoughMoney");
                return;
            }
            $level = $player->getDataSession()->getTotalXPLevel();
            $prestige = $player->getDataSession()->getPrestige();
            switch($this->plot->getWorld()->getFolderName()) {
                case "citizen":
                    if($level < 45 and $prestige < 1) {
                        $color = PlotManager::getPlotColor($this->plot);
                        $player->sendMessage(Translation::RED . "You need to be Level " . TextFormat::BOLD . TextFormat::WHITE . "45" . TextFormat::RESET . TextFormat::GRAY . " or Prestige" . TextFormat::BOLD . TextFormat::LIGHT_PURPLE . " <" . TextFormat::AQUA . "I" . TextFormat::LIGHT_PURPLE . ">" . TextFormat::RESET . TextFormat::GRAY . " to purchase a " . TextFormat::BOLD . $color . "Citizen" . TextFormat::RESET . TextFormat::GRAY . " Plot");
                        return;
                    }
                    break;
                case "merchant":
                    if($level < 100 and $prestige < 1) {
                        $color = PlotManager::getPlotColor($this->plot);
                        $player->sendMessage(Translation::RED . "You need to be Level " . TextFormat::BOLD . TextFormat::WHITE . "100" . TextFormat::RESET . TextFormat::GRAY . " or Prestige" . TextFormat::BOLD . TextFormat::LIGHT_PURPLE . " <" . TextFormat::AQUA . "I" . TextFormat::LIGHT_PURPLE . ">" . TextFormat::RESET . TextFormat::GRAY . " to purchase a " . TextFormat::BOLD . $color . "Merchant" . TextFormat::RESET . TextFormat::GRAY . " Plot");
                        return;
                    }
                    break;
                case "king":
                    if($prestige < 3) {
                        $color = PlotManager::getPlotColor($this->plot);
                        $player->sendMessage(Translation::RED . "You need to be Prestige" . TextFormat::BOLD . TextFormat::LIGHT_PURPLE . " <" . TextFormat::AQUA . "III" . TextFormat::LIGHT_PURPLE . ">" . TextFormat::RESET . TextFormat::GRAY . " to purchase a " . TextFormat::BOLD . $color . "King" . TextFormat::RESET . TextFormat::GRAY . " Plot");
                        return;
                    }
                    break;
            }
            //if($plot->getWorld()->getFolderName() !== "merchant") {
            Nexus::getInstance()->getScheduler()->scheduleRepeatingTask(new TeleportTask($player, $this->plot->getSpawn(), 10), 20);
            $player->sendTitle(TextFormat::BOLD . TextFormat::GREEN . "SUCCESS", TextFormat::GRAY . "You now own " . TextFormat::WHITE . "Plot " . TextFormat::BOLD . TextFormat::AQUA . $this->plot->getId());
            if($this->plot->getOwner() !== null) {
                $this->plot->delete();
            }
            $this->plot->clearPlot();
            $player->getDataSession()->subtractFromBalance($price);
            $this->plot->setOwner(new PlotOwner($player->getName(), []));
            $this->plot->getOwner()->scheduleUpdate();
            $player->playLaunchSound();
        }
    }
}