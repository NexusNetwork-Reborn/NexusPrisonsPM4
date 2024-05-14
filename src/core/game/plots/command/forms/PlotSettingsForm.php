<?php

declare(strict_types = 1);

namespace core\game\plots\command\forms;

use core\command\task\TeleportTask;
use core\game\plots\command\inventory\CellFloorInventory;
use core\game\plots\plot\Plot;
use core\game\plots\PlotManager;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\TranslationException;
use libs\form\MenuForm;
use libs\form\MenuOption;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class PlotSettingsForm extends MenuForm {

    /** @var Plot */
    private $plot;

    /**
     * PlotSettingsForm constructor.
     *
     * @param NexusPlayer $player
     */
    public function __construct(NexusPlayer $player) {
        $title = TextFormat::BOLD . TextFormat::YELLOW . "Plot Settings";
        $text = "What action would you like to do?";
        $options = [];
        $this->plot = Nexus::getInstance()->getGameManager()->getPlotManager()->getPlotByOwner($player->getName());
        if($this->plot !== null) {
            $options[] = new MenuOption("Teleport");
            $options[] = new MenuOption("Manage Player Permissions");
            $options[] = new MenuOption("Transfer Ownership");
            $options[] = new MenuOption("Resell");
            $options[] = new MenuOption("Floor");
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
        switch($selectedOption) {
            case 0:
                Nexus::getInstance()->getScheduler()->scheduleRepeatingTask(new TeleportTask($player, $this->plot->getSpawn(), 10), 20);
                break;
            case 1:
                $player->sendDelayedForm(new PlotManageUsersForm($player));
                break;
            case 2:
                $player->sendDelayedForm(new PlotTransferForm());
                break;
            case 3:
                $player->sendDelayedForm(new PlotResellForm($this->plot));
                break;
            case 4:
                $player->sendDelayedWindow(new CellFloorInventory($this->plot));
            break;
        }
    }
}