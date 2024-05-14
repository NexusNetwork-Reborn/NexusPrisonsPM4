<?php

declare(strict_types = 1);

namespace core\command\forms;

use core\game\item\types\Rarity;
use core\game\quest\Quest;
use core\player\NexusPlayer;
use libs\form\CustomForm;
use libs\form\element\Label;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class QuestInfoForm extends CustomForm {

    /**
     * QuestInfoForm constructor.
     *
     * @param Quest $quest
     * @param int $progress
     */
    public function __construct(Quest $quest, int $progress) {
        $color = Rarity::RARITY_TO_COLOR_MAP[$quest->getRarity()];
        $title = TextFormat::BOLD . $color . $quest->getName();
        $elements = [];
        $elements[] = new Label("Info",  TextFormat::BOLD . TextFormat::YELLOW . "MISSION\n" . TextFormat::RESET . TextFormat::GRAY . $quest->getDescription() . TextFormat::BOLD . TextFormat::YELLOW . "\n\nPROGRESS\n" . TextFormat::WHITE . number_format($progress) . TextFormat::RESET . TextFormat::GRAY . "/" . number_format($quest->getTargetValue()) . TextFormat::BOLD . TextFormat::YELLOW . "\n\nREWARD\n" . TextFormat::WHITE . Quest::RARITY_TO_TOKENS[$quest->getRarity()] . TextFormat::AQUA . " Tokens");
        parent::__construct($title, $elements);
    }

    /**
     * @param Player $player
     */
    public function onClose(Player $player): void {
        if($player instanceof NexusPlayer) {
            $player->sendForm(new QuestForm($player));
        }
    }
}