<?php

declare(strict_types = 1);

namespace core\command\forms;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use libs\form\MenuForm;
use libs\form\MenuOption;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class CEListForm extends MenuForm {

    const TYPE_TO_FLAG_MAP = [
        "Armor" => "Armor",
        "Pickaxe" => "Pickaxe",
        "Sword" => "Weapon",
        "Axe" => "Weapon",
        "Leggings" => "Armor",
        "Boots" => "Armor",
        "Bow" => "Weapon",
        "Chestplate" => "Armor",
        "Helmet" => "Armor",
        "Weapon" => "Weapon",
        "Satchel" => "Satchel",
        "Universal" => "Universal"
    ];

    /**
     * CEListForm constructor.
     *
     * @param string $type
     */
    public function __construct(string $type) {
        $title = TextFormat::BOLD . TextFormat::AQUA . $type;
        $options = [];
        $enchantments = [];
        foreach(EnchantmentManager::getEnchantments() as $enchantment) {
            if($enchantment instanceof Enchantment and (!in_array($enchantment->getRuntimeId(), $enchantments))) {
                if(EnchantmentManager::flagToString($enchantment->getPrimaryItemFlags()) === "None") {
                    continue;
                }
                if(self::TYPE_TO_FLAG_MAP[EnchantmentManager::flagToString($enchantment->getPrimaryItemFlags())] === self::TYPE_TO_FLAG_MAP[$type]) {
                    $enchantments[] = $enchantment->getRuntimeId();
                    $options[] = new MenuOption($enchantment->getName());
                }
            }
        }
        parent::__construct($title, "Select an enchantment to view.", $options);
    }

    /**
     * @param Player $player
     * @param int $selectedOption
     */
    public function onSubmit(Player $player, int $selectedOption): void {
        $enchantment = EnchantmentManager::getEnchantment($this->getOption($selectedOption)->getText());
        if($enchantment instanceof Enchantment) {
            $player->sendForm(new CEInfoForm($enchantment));
        }
    }
}