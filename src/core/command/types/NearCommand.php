<?php

namespace core\command\types;

use core\command\utils\Command;
use core\game\item\sets\utils\SetUtils;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class NearCommand extends Command {

    /**
     * NearCommand constructor.
     */
    public function __construct() {
        parent::__construct("near", "Show nearby players");
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
        $sender->sendMessage(TextFormat::GOLD . "Nearby players within " . TextFormat::BOLD . TextFormat::AQUA . "128m");
        $bb = $sender->getBoundingBox()->expandedCopy(128, 128, 128);
        $world = $sender->getWorld();
        if($world === null) {
            return;
        }
        $nearby = [];
        foreach($world->getNearbyEntities($bb) as $e) {
            if($e->getId() === $sender->getId()) {
                continue;
            }
            if($e instanceof NexusPlayer) {
                if($e->isLoaded()) {
                    $gang = $e->getDataSession()->getGang();
                    if($gang !== null and $gang->isInGang($sender->getName())) {
                        continue;
                    }
                    if(!$e->isSurvival()) {
                        continue;
                    }
                    if(SetUtils::isWearingFullSet($e, "ghost")) continue;
                    $nearby[] = $e;
                }
                else {
                    continue;
                }
            }
        }
        $senderGang = $sender->getDataSession()->getGang();
        foreach($nearby as $player) {
            $session = $player->getDataSession();
            $factionTag = "";
            $color = TextFormat::WHITE;
            $playerGang = $session->getGang();
            if($playerGang !== null) {
                if($senderGang !== null) {
                    $senderGang->getRelationColor($playerGang);
                }
                $factionTag = $color . $session->getGangRoleToString() . $playerGang->getName() . " " . TextFormat::WHITE;
            }
            $sender->sendMessage(TextFormat::WHITE . $factionTag . $player->getName() . TextFormat::GRAY . "(" . number_format($sender->getLocation()->distance($player->getPosition()), 1) . "m)");
        }
    }
}