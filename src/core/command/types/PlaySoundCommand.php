<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\Command;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\permission\DefaultPermissions;
use ReflectionClass;
use ReflectionException;

class PlaySoundCommand extends Command {

    /**
     * PlaySoundCommand constructor.
     */
    public function __construct() {
        parent::__construct("playsound", "Play a sound (For testing purposes)", "/playsound <name>");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     * @throws ReflectionException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if((!$sender instanceof NexusPlayer) or (!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR))) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        if(!isset($args[0])) {
            $sender->sendTranslatedMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]);
            return;
        }
        $name = $args[0];
        $pk = new LevelSoundEventPacket();
        $reflection = new ReflectionClass($pk);
        $const = $reflection->getConstants();
        if(array_key_exists($name, $const)) {
            $value = $const[$name];
        }
        else {
            $value = null;
        }
        if($value === null) {
            $sender->sendTranslatedMessage("invalidSound");
            return;
        }
        $pk->position = $sender->getPosition();
        $pk->sound = $value;
        $sender->getNetworkSession()->sendDataPacket($pk);
    }
}