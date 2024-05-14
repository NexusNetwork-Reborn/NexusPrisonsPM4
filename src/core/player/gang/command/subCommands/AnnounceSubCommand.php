<?php
declare(strict_types=1);

namespace core\player\gang\command\subCommands;

use core\command\utils\args\TextArgument;
use core\command\utils\SubCommand;
use core\player\gang\Gang;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class AnnounceSubCommand extends SubCommand {

    /**
     * AnnounceSubCommand constructor.
     */
    public function __construct() {
        parent::__construct("announce", "/gang announce <message>");
        $this->registerArgument(0, new TextArgument("message"));
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
        if(!isset($args[1])) {
            $sender->sendTranslatedMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]);
            return;
        }
        $gang = $sender->getDataSession()->getGang();
        if($gang === null) {
            $sender->sendTranslatedMessage("beInGang");
            return;
        }
        if($sender->getDataSession()->getGangRole() < Gang::OFFICER) {
            $sender->sendTranslatedMessage("noPermission");
            return;
        }
        array_shift($args);
        $message = implode(" ", $args);
        foreach($gang->getOnlineMembers() as $player) {
            $player->sendTitle(TextFormat::GREEN . TextFormat::BOLD . "Announcement", TextFormat::GRAY . $message, 20, 60, 20);
        }
    }
}