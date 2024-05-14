<?php

declare(strict_types = 1);

namespace core\command\forms;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\types\Rarity;
use libs\form\CustomForm;
use libs\form\element\Label;
use pocketmine\utils\TextFormat;

class CEInfoForm extends CustomForm {

    /**
     * CEInfoForm constructor.
     *
     * @param Enchantment $enchantment
     */
    public function __construct(Enchantment $enchantment) {
        $title = TextFormat::BOLD . TextFormat::AQUA . $enchantment->getName();
        $elements = [];
        $elements[] = new Label($enchantment->getName(), Rarity::RARITY_TO_COLOR_MAP[Rarity::ENCHANTMENT_RARITY_TO_STRING_MAP[$enchantment->getRarity()]] . TextFormat::BOLD . $enchantment->getName() . TextFormat::RESET . TextFormat::AQUA . "\nApplicable Items: " . TextFormat::WHITE . EnchantmentManager::flagToString($enchantment->getPrimaryItemFlags()) . TextFormat::AQUA . "\nMax Level: " . TextFormat::WHITE . $enchantment->getMaxLevel()  . TextFormat::AQUA . "\nDescription: " . TextFormat::WHITE . $enchantment->getDescription()  . TextFormat::AQUA . "\nRarity: " . TextFormat::WHITE . EnchantmentManager::rarityToString($enchantment->getRarity()));
        parent::__construct($title, $elements);
    }
}