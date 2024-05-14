<?php

declare(strict_types = 1);

namespace core\player\vault\forms;

use core\Nexus;
use core\player\NexusPlayer;
use core\player\vault\Vault;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\form\FormIcon;
use libs\form\MenuForm;
use libs\form\MenuOption;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class VaultListForm extends MenuForm {

    /** @var Vault[] */
    private $vaults;

    /**
     * VaultListForm constructor.
     *
     * @param NexusPlayer $player
     */
    public function __construct(NexusPlayer $player) {
        $title = TextFormat::BOLD . TextFormat::AQUA . "Vaults";
        $text = "Select a vault.";
        $options = [];
        $this->vaults = Nexus::getInstance()->getPlayerManager()->getVaultManager()->getVaultsFor($player->getName());
        $vaultLimit = $player->getDataSession()->getRank()->getVaultsLimit($player);
        for($i = 1; $i <= $vaultLimit; $i++) {
            if(isset($this->vaults[$i])) {
                $options[] = new MenuOption(TextFormat::YELLOW . TextFormat::BOLD . "PV #$i" . "\n" . TextFormat::RESET . TextFormat::GREEN . "(Used)", new FormIcon("http://www.aethic.games/images/open_lock.png", FormIcon::IMAGE_TYPE_URL));
                continue;
            }
            $options[] = new MenuOption(TextFormat::YELLOW . TextFormat::BOLD . "PV #$i" . "\n" . TextFormat::RESET . TextFormat::GRAY . "(Unused)", new FormIcon("http://www.aethic.games/images/lock.png", FormIcon::IMAGE_TYPE_URL));
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
        $number = $selectedOption + 1;
        if(!isset($this->vaults[$number])) {
            $vault = $player->getDataSession()->getVaultById($number);
        }
        else {
            $vault = $this->vaults[$number];
        }
        if($vault === null) {
            $player->sendMessage(Translation::GREEN . "Creating vault...");
            return;
        }
        $player->sendDelayedWindow($vault->getMenu());
    }
}