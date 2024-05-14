<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\Command;
use core\player\NexusPlayer;
use core\player\rank\Rank;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class ListCommand extends Command {

    /**
     * ListCommand constructor.
     */
    public function __construct() {
        parent::__construct("list", "List current online players.");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        /** @var NexusPlayer[] $players */
        $players = [];
        $noble = [];
        $imperial = [];
        $supreme = [];
        $majesty = [];
        $emperor = [];
        $heroic = [];
        $president = [];
        /** @var NexusPlayer[] $staffs */
        $staffs = [];
        /** @var NexusPlayer[] $youtubers */
        $youtubers = [];
        foreach($this->getCore()->getServer()->getOnlinePlayers() as $player) {
            if(!$player instanceof NexusPlayer) {
                return;
            }
            if($player->isLoaded() === false) {
                continue;
            }
            $identifier = $player->getDataSession()->getRank()->getIdentifier();
            if($identifier === Rank::PLAYER) {
                $players[] = $player;
                continue;
            }
            if($identifier >= Rank::NOBLE and $identifier <= Rank::PRESIDENT) {
                switch($identifier) {
                    case Rank::NOBLE:
                        $noble[] = $player;
                        break;
                    case Rank::IMPERIAL:
                        $imperial[] = $player;
                        break;
                    case Rank::SUPREME:
                        $supreme[] = $player;
                        break;
                    case Rank::MAJESTY:
                        $majesty[] = $player;
                        break;
                    case Rank::EMPEROR:
                        $emperor[] = $player;
                        break;
                    case Rank::EMPEROR_HEROIC:
                        $heroic[] = $player;
                        break;
                    case Rank::PRESIDENT:
                        $president[] = $player;
                        break;
                }
                continue;
            }
            if($identifier === Rank::YOUTUBER or $identifier === Rank::FAMOUS) {
                $youtubers[] = $player;
                continue;
            }
            else {
                $staffs[] = $player;
            }
        }
        /** @var NexusPlayer[] $rankedPlayers */
        $rankedPlayers = array_merge($noble, $imperial, $supreme, $majesty, $emperor, $heroic, $president);
        $onlinePlayers = count($this->getCore()->getServer()->getOnlinePlayers());
        if($onlinePlayers === 0) {
            $sender->sendMessage(TextFormat::YELLOW . "There is a total of " . TextFormat::AQUA . $onlinePlayers . TextFormat::YELLOW . " online player(s).");
            return;
        }
        $list = "";
        /** @var NexusPlayer $player */
        foreach($players as $player) {
            if(empty($list)) {
                if($player->isDisguise()) {
                    $list .= TextFormat::RESET . TextFormat::WHITE . $player->getDisplayName();
                    continue;
                }
                $list .= TextFormat::RESET . TextFormat::WHITE . $player->getName();
            }
            else {
                if($player->isDisguise()) {
                    $list .= ", " . TextFormat::RESET . TextFormat::WHITE . $player->getDisplayName();
                    continue;
                }
                $list .= ", " . TextFormat::RESET . TextFormat::WHITE . $player->getName();
            }
        }
        foreach($rankedPlayers as $rankedPlayer) {
            if(empty($list)) {
                if($rankedPlayer->isDisguise()) {
                    $list .= $rankedPlayer->getDisguiseRank()->getColoredName() . " " . TextFormat::RESET . TextFormat::WHITE . $rankedPlayer->getDisplayName();
                    continue;
                }
                $list .= $rankedPlayer->getDataSession()->getRank()->getColoredName() . " " . TextFormat::RESET . TextFormat::WHITE . $rankedPlayer->getName();
            }
            else {
                if($rankedPlayer->isDisguise()) {
                    $list .= ", " . $rankedPlayer->getDisguiseRank()->getColoredName() . " " . TextFormat::RESET . TextFormat::WHITE . $rankedPlayer->getDisplayName();
                    continue;
                }
                $list .= ", " . $rankedPlayer->getDataSession()->getRank()->getColoredName() . " " . TextFormat::RESET . TextFormat::WHITE . $rankedPlayer->getName();
            }
        }
        $playerCount = count($players) + count($rankedPlayers);
        $times = (int)round(($playerCount / $onlinePlayers) * 20);
        $sender->sendMessage(TextFormat::DARK_GRAY . "[" . TextFormat::DARK_AQUA . str_repeat("|", $times) . TextFormat::GRAY . str_repeat("|", 20 - $times) . TextFormat::DARK_GRAY . "] " . Translation::getMessage("listMessage", [
                "group" => TextFormat::AQUA . "Prisoners",
                "count" => TextFormat::DARK_GRAY . "(" . TextFormat::BOLD . TextFormat::DARK_AQUA . $playerCount . TextFormat::RESET . TextFormat::DARK_GRAY . ")",
                "list" => $list
            ]));
        $list = "";
        foreach($youtubers as $youtuber) {
            if(empty($list)) {
                $list .= $youtuber->getDataSession()->getRank()->getColoredName() . " " . TextFormat::RESET . TextFormat::WHITE . $youtuber->getName();
            }
            else {
                $list .= ", " . $youtuber->getDataSession()->getRank()->getColoredName() . " " . TextFormat::RESET . TextFormat::WHITE . $youtuber->getName();
            }
        }
        $times = (int)round((count($youtubers) / $onlinePlayers) * 20);
        $sender->sendMessage(TextFormat::DARK_GRAY . "[" . TextFormat::DARK_RED . str_repeat("|", $times) . TextFormat::GRAY . str_repeat("|", 20 - $times) . TextFormat::DARK_GRAY . "] " . Translation::getMessage("listMessage", [
                "group" => TextFormat::RED . "YouTubers",
                "count" => TextFormat::DARK_GRAY . "(" . TextFormat::BOLD . TextFormat::DARK_RED . count($youtubers) . TextFormat::RESET . TextFormat::DARK_GRAY . ")",
                "list" => TextFormat::WHITE . $list
            ]));
        $list = "";
        foreach($staffs as $staff) {
            if(empty($list)) {
                $list .= $staff->getDataSession()->getRank()->getColoredName() . " " . TextFormat::RESET . TextFormat::WHITE . $staff->getName();
            }
            else {
                $list .= ", " . $staff->getDataSession()->getRank()->getColoredName() . " " . TextFormat::RESET . TextFormat::WHITE . $staff->getName();
            }
        }
        $sender->sendMessage(" ");
        $times = (int)round((count($staffs) / $onlinePlayers) * 20);
        $sender->sendMessage(TextFormat::DARK_GRAY . "[" . TextFormat::GOLD . str_repeat("|", $times) . TextFormat::GRAY . str_repeat("|", 20 - $times) . TextFormat::DARK_GRAY . "] " . Translation::getMessage("listMessage", [
                "group" => TextFormat::YELLOW . "Staffs",
                "count" => TextFormat::DARK_GRAY . "(" . TextFormat::BOLD . TextFormat::GOLD . count($staffs) . TextFormat::RESET . TextFormat::DARK_GRAY . ")",
                "list" => TextFormat::WHITE . $list
            ]));
        $sender->sendMessage(" ");
        $sender->sendMessage(TextFormat::GRAY . "There is a total of " . TextFormat::WHITE . $onlinePlayers . TextFormat::GRAY . " online player(s).");
        $sender->sendMessage(" ");
    }
}