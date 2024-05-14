<?php

declare(strict_types = 1);

namespace core\command\forms;

use core\command\task\CheckVoteTask;
use core\player\NexusPlayer;
use core\translation\Translation;
use libs\form\FormIcon;
use libs\form\MenuForm;
use libs\form\MenuOption;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class VoteMenuForm extends MenuForm {

    /**
     * VoteMenuForm constructor.
     */
    public function __construct() {
        $title = TextFormat::BOLD . TextFormat::AQUA . "Vote Menu";
        $text = "What would you like to do?.";
        $options = [];
        $options[] = new MenuOption("Check for vote", new FormIcon("http://www.aethic.games/images/check_mark.png", FormIcon::IMAGE_TYPE_URL));
        $options[] = new MenuOption("Vote Shop", new FormIcon("http://www.aethic.games/images/coin.png", FormIcon::IMAGE_TYPE_URL));
        parent::__construct($title, $text, $options);
    }

    /**
     * @param Player $player
     * @param int $selectedOption
     */
    public function onSubmit(Player $player, int $selectedOption): void {
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $option = $this->getOption($selectedOption);
        switch($option->getText()) {
            case "Check for vote":
                if($player->hasVoted()) {
                    $player->sendMessage(Translation::getMessage("alreadyVoted"));
                    return;
                }
                if($player->isCheckingForVote()) {
                    $player->sendMessage(Translation::getMessage("checkingVote"));
                    return;
                }
                Server::getInstance()->getAsyncPool()->submitTaskToWorker(new CheckVoteTask($player->getName()), 1);
                break;
            case "Vote Shop":
                $player->sendForm(new VoteShopForm($player));
                break;
        }
    }
}