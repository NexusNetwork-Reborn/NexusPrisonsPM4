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

class PlotManageUsersForm extends MenuForm {

    /** @var Plot */
    private $plot;

    /**
     * PlotSettingsForm constructor.
     *
     * @param NexusPlayer $player
     */
    public function __construct(NexusPlayer $player) {
        $title = TextFormat::BOLD . TextFormat::YELLOW . "Plot Settings";
        $text = "Which user would you like to manage?";
        $options = [];
        $this->plot = Nexus::getInstance()->getGameManager()->getPlotManager()->getPlotByOwner($player->getName());
        if($this->plot !== null) {
            $owner = $this->plot->getOwner();
            if($owner !== null) {
                foreach($owner->getUsers() as $user) {
                    $options[] = new MenuOption($user->getUsername());
                }
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
        if($this->plot !== null) {
            $owner = $this->plot->getOwner();
            if($owner !== null) {
                $user = $owner->getUser($this->getOption($selectedOption)->getText());
                if($user !== null) {
                    $player->sendDelayedForm(new PlotPermissionsForm($user));
                }
            }
        }
    }
}