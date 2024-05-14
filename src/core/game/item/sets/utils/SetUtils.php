<?php

declare(strict_types = 1);

namespace core\game\item\sets\utils;

use pocketmine\player\Player;

class SetUtils
{

    /**
     * @param Player $player
     * @param string $setName
     * @return bool
     */
    public static function isWearingFullSet(Player $player, string $setName) : bool
    {
        $checked = 0;

        foreach ($player->getArmorInventory()->getContents() as $content) {
            if($content->getNamedTag()->getString("set" , "") !== $setName) break;
            $checked++;
        }

        return $checked >= 4;
    }
}