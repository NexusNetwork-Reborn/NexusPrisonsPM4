<?php

declare(strict_types = 1);

namespace core\game\fund\command\forms;

use core\game\fund\FundManager;
use core\Nexus;
use libs\form\CustomForm;
use libs\form\element\Label;
use pocketmine\utils\TextFormat;

class FundProgressForm extends CustomForm {

    /**
     * FundProgressForm constructor.
     *
     * @param string $phase
     */
    public function __construct(string $phase) {
        $title = TextFormat::BOLD . TextFormat::AQUA . "$phase";
        $elements = [];
        $text = [];
        $fundManager = Nexus::getInstance()->getGameManager()->getFundManager();
        $balanceProgress = $fundManager->getFundProgressBalance($phase);
        $text[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Global Balance";
        $times = (int)round(($balanceProgress / 100) * 40);
        $text[] = TextFormat::RESET . TextFormat::DARK_GRAY . "[" . TextFormat::GREEN . str_repeat("|", $times) . TextFormat::GRAY . str_repeat("|", 40 - $times) . TextFormat::DARK_GRAY . "]";
        $text[] = TextFormat::RESET . TextFormat::GRAY . "(" . number_format($fundManager->getGlobalBalance(), 2) . "/" . number_format(FundManager::BALANCE_REQUIREMENTS[$phase], 2) . ")";
        $elements[] = new Label("Balance", implode("\n", $text));
        $text = [];
        $rankProgress = $fundManager->getFundProgressRanks($phase);
        $text[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Global Ranks (Level " . FundManager::LEVEL_REQUIREMENTS[$phase] . ")";
        $times = (int)round(($rankProgress / 100) * 40);
        $text[] = TextFormat::RESET . TextFormat::DARK_GRAY . "[" . TextFormat::GREEN . str_repeat("|", $times) . TextFormat::GRAY . str_repeat("|", 40 - $times) . TextFormat::DARK_GRAY . "]";
        $text[] = TextFormat::RESET . TextFormat::GRAY . "(" . number_format($rankProgress / 10) . "/10)";
        $elements[] = new Label("Ranks", implode("\n", $text));
        parent::__construct($title, $elements);
    }
}