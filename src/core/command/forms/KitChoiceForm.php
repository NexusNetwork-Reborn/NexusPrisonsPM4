<?php

declare(strict_types = 1);

namespace core\command\forms;

use core\command\inventory\KitItemListInventory;
use core\game\item\enchantment\EnchantmentManager;
use core\game\kit\GodKit;
use core\game\kit\Kit;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\form\FormIcon;
use libs\form\MenuForm;
use libs\form\MenuOption;
use libs\utils\Utils;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class KitChoiceForm extends MenuForm {

    /** @var Kit */
    private $kit;

    /**
     * KitChoiceForm constructor.
     *
     * @param NexusPlayer $player
     * @param Kit $kit
     */
    public function __construct(NexusPlayer $player, Kit $kit) {
        $title = $kit->getColoredName();
        $text = "What would you like to do?";
        $options = [];
        if(!$player->hasPermission("permission." . strtolower($kit->getName()))) {
            $options[] = new MenuOption("Redeem Kit\n" . TextFormat::BOLD . TextFormat::RED . "LOCKED", new FormIcon("http://www.aethic.games/images/lock.png", FormIcon::IMAGE_TYPE_URL));
        }
        else {
            $cooldown = $player->getDataSession()->getKitCooldown($kit);
            $cooldown = $kit->getCooldown() - (time() - $cooldown);
            if($cooldown > 0) {
                $options[] = new MenuOption("Redeem Kit\n" . TextFormat::BOLD . TextFormat::RED . "COOLDOWN: " . TextFormat::RESET . TextFormat::RED . Utils::secondsToTime($cooldown), new FormIcon("http://www.aethic.games/images/hourglass.png", FormIcon::IMAGE_TYPE_URL));
            }
            else {
                if($kit instanceof GodKit) {
                    $tier = $player->getDataSession()->getGodKitTier($kit);
                    $roman = EnchantmentManager::getRomanNumber($tier);
                    $options[] = new MenuOption("Redeem Kit\n" . TextFormat::BOLD . TextFormat::GREEN . "READY [$roman/III]", new FormIcon("http://www.aethic.games/images/open_lock.png", FormIcon::IMAGE_TYPE_URL));
                }
                else {
                    $options[] = new MenuOption("Redeem Kit\n" . TextFormat::BOLD . TextFormat::GREEN . "READY", new FormIcon("http://www.aethic.games/images/open_lock.png", FormIcon::IMAGE_TYPE_URL));
                }
            }
        }
        $options[] = new MenuOption("Preview Kit", new FormIcon("http://www.aethic.games/images/sunglasses.png", FormIcon::IMAGE_TYPE_URL));
        $this->kit = $kit;
        parent::__construct($title, $text, $options);
    }

    /**
     * @param Player $player
     * @param int $selectedOption
     *
     * @throws TranslationException
     */
    public function onSubmit(Player $player, int $selectedOption): void {
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if($selectedOption === 0) {
            $lowercaseName = strtolower($this->kit->getName());
            if(!$player->hasPermission("permission.$lowercaseName")) {
                $player->sendMessage(Translation::getMessage("noPermission"));
                return;
            }
            $cooldown = $player->getDataSession()->getKitCooldown($this->kit);
            $cooldown = $this->kit->getCooldown() - (time() - $cooldown);
            if($cooldown > 0) {
                $player->sendMessage(Translation::getMessage("kitCooldown", [
                    "time" => TextFormat::RED . Utils::secondsToTime($cooldown)
                ]));
                return;
            }
            $this->kit->giveTo($player);
            $player->sendTitle($this->kit->getColoredName(), TextFormat::GRAY . "Equipped");
            $player->getDataSession()->setKitCooldown($this->kit);
        }
        else {
            $player->sendDelayedWindow(new KitItemListInventory($player, $this->kit));
        }
    }

    /**
     * @param Player $player
     */
    public function onClose(Player $player): void {
        // CUZ IM FUCKING LAZY TO ADD ANOTHER PROPERTY
        Server::getInstance()->dispatchCommand($player, "gkit");
    }
}