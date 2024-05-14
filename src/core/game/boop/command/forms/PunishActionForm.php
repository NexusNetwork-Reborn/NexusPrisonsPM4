<?php

namespace core\game\boop\command\forms;

use core\Nexus;
use core\translation\Translation;
use core\translation\TranslationException;
use core\game\boop\PunishmentEntry;
use core\game\boop\Reasons;
use core\game\boop\BOOPException;
use libs\form\CustomForm;
use libs\form\CustomFormResponse;
use libs\form\element\Dropdown;
use libs\form\element\Input;
use libs\form\element\Label;
use libs\utils\Utils;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class PunishActionForm extends CustomForm {

    const TYPE_TO_REASONS_MAP = [
        PunishmentEntry::MUTE => [
            Reasons::STAFF_DISRESPECT,
            Reasons::SPAMMING
        ],
        PunishmentEntry::BAN => [
            Reasons::ADVERTISING,
            Reasons::EXPLOITING,
            Reasons::IRL_SCAMMING,
            Reasons::ALTING,
            Reasons::BAN_EVADING,
            Reasons::DDOS_THREATS,
            Reasons::HACK
        ]
    ];

    /** @var int */
    private $type;

    /**
     * PunishActionForm constructor.
     *
     * @param int $type
     */
    public function __construct(int $type) {
        $title = TextFormat::BOLD . TextFormat::AQUA . "Punish";
        $this->type = $type;
        $elements = [];
        $elements[] = new Label("Label", "The expiration time will automatically be chosen due to how many offenses a player has.");
        $elements[] = new Input("Name", "Username", "Donald Trump");
        $elements[] = new Dropdown("Reasons", "Reasons", self::TYPE_TO_REASONS_MAP[$type]);
        parent::__construct($title, $elements);
    }

    /**
     * @param Player $player
     * @param CustomFormResponse $data
     *
     * @throws TranslationException
     * @throws BOOPException
     */
    public function onSubmit(Player $player, CustomFormResponse $data): void {
        $name = $data->getString("Name");
        /** @var Dropdown $element */
        $element = $this->getElementByName("Reasons");
        $reason = $element->getOption($data->getInt("Reasons"));
        switch($this->type) {
            case PunishmentEntry::BAN:
                if(Nexus::getInstance()->getGameManager()->getBOOPManager()->isBanned($name)) {
                    $player->sendMessage(Translation::getMessage("alreadyBanned", [
                        "name" => TextFormat::YELLOW . $name,
                    ]));
                    return;
                }
                break;
            case PunishmentEntry::MUTE:
                if(Nexus::getInstance()->getGameManager()->getBOOPManager()->isMuted($name)) {
                    $player->sendMessage(Translation::getMessage("alreadyMuted", [
                        "name" => TextFormat::YELLOW . $name,
                    ]));
                    return;
                }
                break;
        }
        $entry = Nexus::getInstance()->getGameManager()->getBOOPManager()->punish($name, $this->type, $player->getName(), $reason);
        if($entry->getExpiration() === 0) {
            $expiration = "Forever";
        }
        else {
            $expiration = Utils::secondsToTime($entry->getExpiration());
        }
        switch($this->type) {
            case PunishmentEntry::BAN:
                $banned = Server::getInstance()->getPlayerByPrefix($name);
                if($banned !== null) {
                    $name = $banned->getName();
                    $banned->kickDelay(Translation::getMessage("banMessage", [
                        "name" => TextFormat::RED . $player->getName(),
                        "reason" => TextFormat::YELLOW . $reason,
                        "time" => TextFormat::RED . $expiration
                    ]));
                }
                Server::getInstance()->broadcastMessage(Translation::getMessage("banBroadcast", [
                    "name" => TextFormat::RED . $name,
                    "effector" => TextFormat::DARK_RED . $player->getName(),
                    "reason" => TextFormat::YELLOW . "\"$reason\"",
                    "time" => TextFormat::RED . $expiration
                ]));
                break;
            case PunishmentEntry::MUTE:
                Server::getInstance()->broadcastMessage(Translation::getMessage("muteBroadcast", [
                    "name" => TextFormat::RED . $name,
                    "effector" => TextFormat::DARK_RED . $player->getName(),
                    "reason" => TextFormat::YELLOW . "\"$reason\"",
                    "time" => TextFormat::RED . $expiration
                ]));
                break;
        }
    }
}