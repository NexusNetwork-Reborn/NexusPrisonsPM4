<?php
declare(strict_types=1);

namespace core\player\gang\command\forms;

use core\player\gang\GangException;
use core\player\NexusPlayer;
use libs\form\MenuForm;
use libs\form\MenuOption;
use libs\utils\UtilsException;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class FlagsSetForm extends MenuForm {

    /** @var string */
    private $permission;

    /**
     * FlagsSetForm constructor.
     *
     * @param string $permission
     */
    public function __construct(string $permission) {
        $title = TextFormat::BOLD . TextFormat::AQUA . $permission;
        $options = [];
        $this->permission = $permission;
        $options[] = new MenuOption("Recruit");
        $options[] = new MenuOption("Member");
        $options[] = new MenuOption("Officer");
        $options[] = new MenuOption("Leader");
        parent::__construct($title, "Which role would you like to allow?", $options);
    }

    /**
     * @param Player $player
     * @param int $selectedOption
     *
     * @throws GangException
     * @throws UtilsException
     */
    public function onSubmit(Player $player, int $selectedOption): void {
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $gang = $player->getDataSession()->getGang();
        if($gang === null) {
            $player->sendTranslatedMessage("beInGang");
            return;
        }
        $gang->getPermissionManager()->setValue($this->permission, $selectedOption);
        $player->sendTranslatedMessage("roleFlagSet", [
            "name" => TextFormat::YELLOW . $this->permission,
            "role" => TextFormat::LIGHT_PURPLE . $this->getOption($selectedOption)->getText()
        ]);
    }
}