<?php

namespace core\game\plots\command\forms;

use core\game\gamble\task\RollCoinFlipTask;
use core\game\plots\command\session\PlotCreateSession;
use core\game\plots\plot\Plot;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\form\ModalForm;
use pocketmine\block\VanillaBlocks;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class PlotCreateConfirmationForm extends ModalForm {

    /** @var PlotCreateSession */
    private $session;

    /**
     * PlotCreateConfirmationForm constructor.
     *
     * @param PlotCreateSession $session
     */
    public function __construct(PlotCreateSession $session) {
        $this->session = $session;
        $title = TextFormat::BOLD . TextFormat::YELLOW . "Plot Create";
        $text = "Are you ready to create this plot?";
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
        if($this->session->isConfirmed() === true) {
            $player->sendMessage("Plot has already been confirmed!");
            return;
        }
        if($choice == true and $this->session->isConfirmed() === false) {
            $this->session->setConfirmed(true);
            $manager = $player->getCore()->getGameManager()->getPlotManager();
            $id = count($manager->getPlots()) + 1;
            $player->sendMessage("Created Plot #$id");
            $world = $this->session->getSpawn()->getWorld();
            $plot = new Plot($id, $this->session->getFirstPosition(), $this->session->getSecondPosition(), $world, $this->session->getSpawn(), 0);
            $world->setBlock($plot->getFirstPosition(), VanillaBlocks::AIR());
            $world->setBlock($plot->getSecondPosition(), VanillaBlocks::AIR());
            $world->setBlock($plot->getSpawn(), VanillaBlocks::AIR());
            //if($plot->getWorld()->getFolderName() !== "merchant") {
            $player->teleport($plot->getSpawn());
           // } else {

            //}
            $manager->addPlot($plot);
            $manager->removePlotCreateSession($player);
        }
    }
}