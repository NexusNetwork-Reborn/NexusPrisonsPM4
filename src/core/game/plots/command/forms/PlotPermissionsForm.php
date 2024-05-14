<?php

declare(strict_types = 1);

namespace core\game\plots\command\forms;

use core\game\plots\plot\PlotUser;
use libs\form\CustomForm;
use libs\form\CustomFormResponse;
use libs\form\element\Label;
use libs\form\element\Toggle;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class PlotPermissionsForm extends CustomForm {

    /** @var PlotUser */
    private $user;

    /**
     * PlotPermissionsForm constructor.
     *
     * @param PlotUser $user
     */
    public function __construct(PlotUser $user) {
        $title = TextFormat::BOLD . TextFormat::YELLOW . "Plot Settings";
        $this->user = $user;
        $elements = [];
        $elements[] = new Label("Label", "Flip a toggle to allow/deny a permission");
        foreach($user->getPermissionManager()->getPermissions() as $permission => $value) {
            $elements[] = new Toggle($permission, $permission, $value);
        }
        parent::__construct($title, $elements);
    }

    /**
     * @param Player $player
     * @param CustomFormResponse $data
     */
    public function onSubmit(Player $player, CustomFormResponse $data): void {
        foreach($this->user->getPermissionManager()->getPermissions() as $permission => $value) {
            $new = $data->getBool($permission);
            $this->user->getPermissionManager()->setValue($permission, $new);
        }
    }
}