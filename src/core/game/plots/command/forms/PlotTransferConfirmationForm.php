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

class PlotTransferConfirmationForm extends ModalForm {

    /** @var Plot */
    private $plot;

    /** @var NexusPlayer */
    private $player;

    /**
     * PlotTransferConfirmationForm constructor.
     *
     * @param Plot $plot
     * @param NexusPlayer $player
     */
    public function __construct(Plot $plot, NexusPlayer $player) {
        $this->plot = $plot;
        $this->player = $player;
        $title = TextFormat::BOLD . TextFormat::YELLOW . "Plot Transfer";
        $text = "Are you sure you would like to transfer Plot {$plot->getId()} to {$player->getName()}?";
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
        if($owner !== null and $owner->getUsername() !== $player->getName()) {
            $player->sendMessage(Translation::RED . "You must own this plot to transfer ownership!");
            return;
        }
        if($this->player->isClosed() or (!$this->player->isOnline())) {
            $player->sendMessage(Translation::RED . "The person you want to transfer ownership to is now offline!");
            return;
        }
        if($choice == true) {
            $this->player->sendTitle(TextFormat::BOLD . TextFormat::GREEN . "NOTICE", TextFormat::GRAY . "You now own " . TextFormat::WHITE . "Plot " . TextFormat::BOLD . TextFormat::AQUA . $this->plot->getId());
            $this->plot->setOwner(new PlotOwner($this->player->getName(), []));
            $this->plot->getOwner()->scheduleUpdate();
            $this->player->playLaunchSound();
        }
    }
}