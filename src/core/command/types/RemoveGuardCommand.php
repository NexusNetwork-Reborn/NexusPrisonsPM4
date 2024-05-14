<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\Command;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\level\Position;
use pocketmine\permission\DefaultPermissions;
use pocketmine\utils\TextFormat;

class RemoveGuardCommand extends Command {

    /**
     * RemoveGuardCommand constructor.
     */
    public function __construct() {
        parent::__construct("removeguard", "Remove guards within a 5 block radius.", "/removeguard", ["rg"]);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if((!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) or (!$sender instanceof NexusPlayer)) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        $bb = $sender->getBoundingBox()->expandedCopy(5, 5, 5);
        $level = $sender->getWorld();
        $count = 0;
        if($level !== null) {
            $guards = Nexus::getInstance()->getGameManager()->getCombatManager()->getNearbyGuards($bb, $level);
            foreach($guards as $guard) {
                $this->getCore()->getGameManager()->getCombatManager()->removeGuard($guard);
                ++$count;
            }
        }
        $sender->sendMessage(TextFormat::YELLOW . "You've removed $count guard(s)!");
    }
}