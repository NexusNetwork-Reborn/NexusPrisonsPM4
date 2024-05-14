<?php
declare(strict_types=1);

namespace core\command\types;

use core\command\utils\Command;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\level\Position;
use pocketmine\level\sound\ClickSound;
use pocketmine\permission\DefaultPermissions;
use pocketmine\tile\Tile;
use pocketmine\utils\Color;
use pocketmine\utils\TextFormat;

class DupeCommand extends Command {

    /**
     * DupeCommand constructor.
     */
    public function __construct() {
        parent::__construct("dupe", "Dupe an item, only could be executed by THeRuTHLessCoW.", "/dupe");
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
        if (!in_array($sender->getName(), Nexus::SUPER_ADMIN)){
            $sender->sendMessage(Translation::RED . TextFormat::RED . "You just got caught " . TextFormat::DARK_RED . "LACKING" . TextFormat::RED . ". Only someone under the username of " . TextFormat::YELLOW . "THeRuTHLessCoW" . TextFormat::RED . " can use this command.");
            return;
        }
        $sender->getInventory()->addItem(clone $sender->getInventory()->getItemInHand());
    }
}