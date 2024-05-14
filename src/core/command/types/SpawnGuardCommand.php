<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\Command;
use core\game\combat\guards\types\Enforcer;
use core\game\combat\guards\types\Safeguard;
use core\game\combat\guards\types\Warden;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\world\Position;

;

class SpawnGuardCommand extends Command {

    /**
     * SpawnGuardCommand constructor.
     */
    public function __construct() {
        parent::__construct("spawnguard", "Spawn a guard.", "/spawnguard <type>", ["sg"]);
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
        if(!isset($args[0])) {
            $sender->sendTranslatedMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]);
            return;
        }
        switch(strtolower($args[0])) {
            case "guard":
                $class = Safeguard::class;
                break;
            case "enforcer":
                $class = Enforcer::class;
                break;
            case "warden":
                $class = Warden::class;
                break;
            default:
                $sender->sendTranslatedMessage("invalidGuard");
                return;
                break;
        }
        $this->getCore()->getGameManager()->getCombatManager()->addGuard($class, Position::fromObject($sender->getPosition()->floor()->add(0.5, 0, 0.5), $sender->getWorld()));
    }
}