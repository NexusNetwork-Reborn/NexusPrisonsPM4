<?php

declare(strict_types = 1);

namespace core\command\forms;

use core\game\kit\Kit;
use core\player\NexusPlayer;
use core\translation\TranslationException;
use libs\form\FormIcon;
use libs\form\MenuForm;
use libs\form\MenuOption;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class KitListForm extends MenuForm {

    /** @var Kit[] */
    private $kits = [];

    /**
     * KitListForm constructor.
     *
     * @param NexusPlayer $player
     * @param Kit[] $kits
     */
    public function __construct(NexusPlayer $player, array $kits) {
        $title = TextFormat::BOLD . TextFormat::AQUA . "Kits";
        $text = "Select a kit.";
        $options = [];
        foreach($kits as $kit) {
            $this->kits[] = $kit;
            $options[] = new MenuOption($kit->getColoredName() . "\n" . TextFormat::GRAY . "(Click to preview)", new FormIcon("http://www.aethic.games/images/chest.png", FormIcon::IMAGE_TYPE_URL));
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
        $player->sendForm(new KitChoiceForm($player, $this->kits[$selectedOption]));
    }
}