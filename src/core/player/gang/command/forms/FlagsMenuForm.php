<?php
declare(strict_types=1);

namespace core\player\gang\command\forms;

use core\player\gang\Gang;
use core\player\NexusPlayer;
use libs\form\MenuForm;
use libs\form\MenuOption;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class FlagsMenuForm extends MenuForm {

    /** @var string[] */
    private $permissions;

    /**
     * FlagsMenuForm constructor.
     *
     * @param Gang $gang
     */
    public function __construct(Gang $gang) {
        $title = TextFormat::BOLD . TextFormat::AQUA . $gang->getName();
        $options = [];
        $permissions = $gang->getPermissionManager()->getPermissions();
        foreach($permissions as $permission => $role) {
            switch($role) {
                case Gang::RECRUIT:
                    $role = "Recruit";
                    break;
                case Gang::MEMBER:
                    $role = "Member";
                    break;
                case Gang::OFFICER:
                    $role = "Officer";
                    break;
                case Gang::LEADER:
                    $role = "Leader";
                    break;
                default:
                    $role = "Unknown";
                    break;
            }
            $options[] = new MenuOption("$permission\n$role+");
            $this->permissions[] = $permission;
        }
        parent::__construct($title, "Choose a flag to edit.", $options);
    }

    /**
     * @param Player $player
     * @param int $selectedOption
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
        $permission = $this->permissions[$selectedOption];
        $player->sendForm(new FlagsSetForm($permission));
    }
}