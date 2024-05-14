<?php

declare(strict_types = 1);

namespace core\game\plots\forms;

use core\game\plots\plot\Plot;
use core\player\NexusPlayer;
use core\translation\TranslationException;
use libs\form\MenuForm;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class PlotInfoForm extends MenuForm {

    /**
     * VaultListForm constructor.
     *
     * @param NexusPlayer $player
     */
    public function __construct(Plot $plot) {
        $title = TextFormat::BOLD . TextFormat::AQUA . "Plot Info";
        $helpers = "";
        $owner = "";
        if($plot->getOwner() !== null){
            $owner = $plot->getOwner()->getUsername();
            foreach($plot->getOwner()->getUsers() as $user){
                $helpers .= $user->getUsername() . ", ";
            }
            if(count($plot->getOwner()->getUsers()) > 0){
                $helpers = substr($helpers, 0, -2);
            }
        }
        $text = TextFormat::AQUA . "Plot: " . TextFormat::GRAY . $plot->getId();
        $text .= "\n" . TextFormat::RED . "Owner: " . TextFormat::GRAY . $owner;
        $text .= "\n" . TextFormat::YELLOW . "Helpers; " . TextFormat::GRAY . $helpers;
        parent::__construct($title, $text, []);
    }

    /**
     * @param Player $player
     * @param int $selectedOption
     *
     * @throws TranslationException
     */
    public function onSubmit(Player $player, int $selectedOption): void {
    }
}