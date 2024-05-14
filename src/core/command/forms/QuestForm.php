<?php

declare(strict_types = 1);

namespace core\command\forms;

use core\game\item\types\Rarity;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\TranslationException;
use libs\form\MenuForm;
use libs\form\MenuOption;
use libs\utils\Utils;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class QuestForm extends MenuForm {

    /** @var int[] */
    private $quests;

    /** @var string[] */
    private $questMap;

    /**
     * QuestForm constructor.
     *
     * @param NexusPlayer $player
     */
    public function __construct(NexusPlayer $player) {
        $title = TextFormat::BOLD . TextFormat::GOLD . "Quests";
        $seconds = 604800 - (time() - $player->getDataSession()->getLastQuestReroll());
        $text = TextFormat::RED . "Next re-roll is in " . TextFormat::BOLD . Utils::secondsToTime($seconds);
        $options = [];
        $this->quests = $player->getDataSession()->getQuests();
        foreach($this->quests as $name => $progress) {
            $this->questMap[] = $name;
            $quest = Nexus::getInstance()->getGameManager()->getQuestManager()->getQuest($name);
            $color = Rarity::RARITY_TO_COLOR_MAP[$quest->getRarity()];
            $options[] = new MenuOption(TextFormat::BOLD . $color . $quest->getName() . TextFormat::RESET . TextFormat::GRAY . "\n(" . number_format($progress) . "/" . number_format($quest->getTargetValue()) . ")");
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
        $quest = $this->questMap[$selectedOption];
        $questInstance = Nexus::getInstance()->getGameManager()->getQuestManager()->getQuest($quest);
        $progress = $this->quests[$quest];
        $player->sendForm(new QuestInfoForm($questInstance, $progress));
    }
}