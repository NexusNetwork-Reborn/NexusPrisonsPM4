<?php

declare(strict_types = 1);

namespace core\game\gamble\command\forms;

use core\game\gamble\command\inventory\SelectColorInventory;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\form\FormIcon;
use libs\form\MenuForm;
use libs\form\MenuOption;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class CoinFlipListForm extends MenuForm {

    const COLOR_TO_TEXTURE = [
        TextFormat::RED => "textures/blocks/wool_colored_red.png",
        TextFormat::GOLD => "textures/blocks/wool_colored_orange.png",
        TextFormat::YELLOW => "textures/blocks/wool_colored_yellow.png",
        TextFormat::GREEN => "textures/blocks/wool_colored_lime.png",
        TextFormat::AQUA => "textures/blocks/wool_colored_light_blue.png",
        TextFormat::DARK_PURPLE => "textures/blocks/wool_colored_purple.png",
        TextFormat::GRAY => "textures/blocks/wool_colored_silver.png",
        TextFormat::BLACK => "textures/blocks/wool_colored_black.png",
        TextFormat::WHITE => "textures/blocks/wool_colored_white.png"
    ];

    /**
     * CoinFlipListForm constructor.
     *
     * @param NexusPlayer $player
     */
    public function __construct(NexusPlayer $player) {
        $title = TextFormat::BOLD . TextFormat::YELLOW . "Coin Flip";
        $text = "Select a player to coin flip with.";
        $coinFlips = $player->getCore()->getGameManager()->getGambleManager()->getCoinFlips();
        $options = [];
        foreach($coinFlips as $coinFlip) {
            $owner = $coinFlip->getOwner();
            if($owner->isOnline() and $owner->getUniqueId()->toString() !== $player->getUniqueId()->toString()) {
                $amount = $coinFlip->getAmount();
                $icon = new FormIcon(self::COLOR_TO_TEXTURE[$coinFlip->getColor()], FormIcon::IMAGE_TYPE_PATH);
                $options[] = new MenuOption($owner->getName() . "\n" . TextFormat::RESET . TextFormat::GREEN . "$" . number_format($amount), $icon);
            }
        }
        $options[] = new MenuOption("Refresh");
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
        $text = $this->getOption($selectedOption)->getText();
        if($text === "Refresh") {
            $player->sendForm(new CoinFlipListForm($player));
            return;
        }
        $name = explode("\n", $text)[0];
        $target = $player->getServer()->getPlayerExact($name);
        if(!$target instanceof NexusPlayer) {
            $player->sendMessage(Translation::getMessage("invalidPlayer"));
            return;
        }
        if($target->getUniqueId()->toString() === $player->getUniqueId()->toString()) {
            $player->sendMessage(Translation::getMessage("invalidPlayer"));
            return;
        }
        $cf = $player->getCore()->getGameManager()->getGambleManager()->getCoinFlip($target);
        if($cf === null) {
            $player->sendMessage(Translation::getMessage("invalidPlayer"));
            return;
        }
        if($player->getDataSession()->getBalance() < $cf->getAmount()) {
            $player->sendMessage(Translation::getMessage("notEnoughMoney"));
            return;
        }
        $player->sendDelayedWindow(new SelectColorInventory($cf->getAmount(), $cf));
    }
}