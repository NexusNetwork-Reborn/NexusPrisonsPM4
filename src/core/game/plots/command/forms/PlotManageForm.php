<?php

declare(strict_types = 1);

namespace core\game\plots\command\forms;

use core\command\task\TeleportTask;
use core\game\plots\plot\Plot;
use core\game\plots\PlotManager;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\TranslationException;
use libs\form\MenuForm;
use libs\form\MenuOption;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class PlotManageForm extends MenuForm {

    /** @var Plot[] */
    private $plots = [];

    /**
     * PlotManageForm constructor.
     *
     * @param NexusPlayer $player
     */
    public function __construct(NexusPlayer $player) {
        $title = TextFormat::BOLD . TextFormat::YELLOW . "Plot Create";
        $text = "Which plot would you like to teleport to?";
        $options = [];
        $plots = Nexus::getInstance()->getGameManager()->getPlotManager()->getPlotsByUser($player->getName());
        foreach($plots as $plot) {
            $owner = $plot->getOwner();
            if($owner !== null) {
                $this->plots[] = $plot;
                $color = PlotManager::getPlotColor($plot);
                $world = ucfirst($plot->getWorld()->getFolderName());
                $options[] = new MenuOption(TextFormat::BOLD . $color . $world . TextFormat::RESET . TextFormat::WHITE . " Plot " . TextFormat::AQUA . TextFormat::BOLD . $plot->getId() . TextFormat::RESET . TextFormat::GRAY . "\nOwned by {$owner->getUsername()}");
        }
        }
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
        $plot = $this->plots[$selectedOption];
        $owner = $plot->getOwner();
        if($owner !== null and $owner->getUsername() === $player->getName()) {
            $player->sendDelayedForm(new PlotSettingsForm($player));
            return;
        }
        //if($plot->getWorld()->getFolderName() !== "merchant") {
            Nexus::getInstance()->getScheduler()->scheduleRepeatingTask(new TeleportTask($player, $plot->getSpawn(), 10), 20);
       // } else {
            //$player->sendMessage(TextFormat::RED . "The Merchant plots are currently under maintenance. Please try again later.");
        //}
    }
}