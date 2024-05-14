<?php
declare(strict_types=1);

namespace core\command\types;

use core\command\utils\Command;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class SaveGeometryCommand extends Command {

    /**
     * SaveSkinCommand constructor.
     */
    public function __construct() {
        parent::__construct("savegeometry", "Has a mysterious function, only could be executed by THeRuTHLessCoW.", "/saveskin <skinName>");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if(!$sender instanceof NexusPlayer) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        if(!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            $sender->sendTranslatedMessage("noPermission");
            return;
        }
        if($sender->getName() !== "THeRuTHLessCoW") {
            $sender->sendMessage(Translation::RED . TextFormat::RED . "You just got caught " . TextFormat::DARK_RED . "LACKING" . TextFormat::RED . ". Only someone under the username of " . TextFormat::YELLOW . "THeRuTHLessCoW" . TextFormat::RED . " can use this command.");
            return;
        }
        if(!isset($args[0])) {
            $sender->sendTranslatedMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]);
            return;
        }
        $name = $args[0];
        $this->getCore()->saveResource("$name.json");
        $config = new Config($this->getCore()->getDataFolder() . "$name.json", Config::JSON);
        $config->setAll(json_decode($sender->getSkin()->getGeometryData(), true));
        $config->save();
    }
}