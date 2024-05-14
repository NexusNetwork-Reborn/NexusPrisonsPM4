<?php

declare(strict_types = 1);

namespace core\game\fund\command\forms;

use core\game\fund\FundManager;
use core\game\kit\Kit;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\TranslationException;
use libs\form\MenuForm;
use libs\form\MenuOption;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class FundMainForm extends MenuForm {

    /**
     * KitListForm constructor.
     *
     * @param NexusPlayer $player
     * @param Kit[] $kits
     */
    public function __construct() {
        $title = TextFormat::BOLD . TextFormat::AQUA . "Fund";
        $fundManager = Nexus::getInstance()->getGameManager()->getFundManager();
        $text = "Global Balance: $" . number_format($fundManager->getGlobalBalance());
        $options = [];
        foreach(FundManager::PHASES as $phase) {
            $status = $fundManager->isUnlocked($phase);
            if($status) {
                $status = TextFormat::GREEN . "UNLOCKED" . TextFormat::RESET;
            }
            else {
                $progress = max($fundManager->getFundProgressBalance($phase), $fundManager->getFundProgressRanks($phase));
                $progress = number_format($progress, 3);
                $status = TextFormat::RED . "LOCKED " . TextFormat::RESET . TextFormat::GRAY . "($progress" . "%)";
            }
            $options[] = new MenuOption($phase . "\n" . TextFormat::DARK_GRAY . "[" . TextFormat::BOLD . $status . TextFormat::DARK_GRAY . "]");
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
        $player->sendForm(new FundProgressForm(FundManager::PHASES[$selectedOption]));
    }
}