<?php

declare(strict_types = 1);

namespace core\command\forms;

use core\command\task\TeleportTask;
use core\game\zone\Mine;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\form\FormIcon;
use libs\form\MenuForm;
use libs\form\MenuOption;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class MinesForm extends MenuForm {

    const MINE_TO_PATH_NAME = [
        "Coal" => "coal.png",
        "Iron" => "iron_ingot.png",
        "Lapis" => "dye_powder_blue.png",
        "Redstone" => "redstone_dust.png",
        "Gold" => "gold_ingot.png",
        "Diamond" => "diamond.png",
        "Emerald" => "emerald.png"
    ];

    /** @var Mine[] */
    private $mines = [];

    /**
     * MinesForm constructor.
     *
     * @param NexusPlayer $player
     */
    public function __construct(NexusPlayer $player) {
        $title = TextFormat::BOLD . TextFormat::AQUA . "Mines";
        $text = "Teleport to a mine.";
        $this->mines = Nexus::getInstance()->getGameManager()->getZoneManager()->getMines();
        $options = [];
        foreach($this->mines as $mine) {
            if($mine->canAccess($player)) {
                $options[] = new MenuOption($mine->getName() . "\n" . TextFormat::GREEN . TextFormat::BOLD . "UNLOCKED", new FormIcon("textures/items/" . self::MINE_TO_PATH_NAME[$mine->getName()], FormIcon::IMAGE_TYPE_PATH));
            }
            else {
                $options[] = new MenuOption($mine->getName() . "\n" . TextFormat::RED . TextFormat::BOLD . "LOCKED", new FormIcon("http://www.aethic.games/images/forbidden.png", FormIcon::IMAGE_TYPE_URL));
            }
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
        $mine = $this->mines[$selectedOption];
        if($mine->canAccess($player)) {
            Nexus::getInstance()->getScheduler()->scheduleRepeatingTask(new TeleportTask($player, $mine->getPosition(), 10), 20);
        }
        else {
            $player->sendMessage(Translation::getMessage("noPermission"));
        }
    }
}