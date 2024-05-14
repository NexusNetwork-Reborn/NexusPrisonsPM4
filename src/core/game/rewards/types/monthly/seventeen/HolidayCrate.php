<?php
declare(strict_types=1);

namespace core\game\rewards\types;

use core\game\item\types\custom\Cosmetic;
use core\game\rewards\Reward;
use core\game\rewards\Rewards;
use core\player\NexusPlayer;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat;

class HolidayCrate extends MonthlyRewards {

    /**
     * HolidayCrate constructor.
     */
    public function __construct() {
        $coloredName = TextFormat::RESET . TextFormat::WHITE . TextFormat::BOLD . "Christmas 2021";
        $adminItems = [
            new Reward("Happy Holidays", function(?NexusPlayer $player): Item {
                $display = VanillaBlocks::FERN()->asItem();
                $name = TextFormat::RESET . TextFormat::BOLD . TextFormat::GREEN . "Happy" . TextFormat::RED . " Holidays";
                $lore = [];
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . "Happy New Year" . TextFormat::RED . "<3";
                $item = (new Cosmetic($display, $name, $lore, true))->toItem();
//                if($player !== null) {
//                    $player->getInventory()->addItem($item);
//                }
                return $item;
            }, 100),
            new Reward("Holiday Tunes", function(?NexusPlayer $player): Item {
                $display = VanillaItems::RECORD_11();
                $name = TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_AQUA . "Holiday Tunes";
                $lore = [];
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::ITALIC . TextFormat::RED . "The best holiday songs 2017";
                $item = (new Cosmetic($display, $name, $lore, true))->toItem();
//                if($player !== null) {
//                    $player->getInventory()->addItem($item);
//                }
                return $item;
            }, 100)
        ];
        $cosmetics = [

        ];
        $this->treasureItems = $treasureItems;
        $this->bonus = $bonus;
        parent::__construct(self::HOLIDAY, 2021, $coloredName, $adminItems, $cosmetics, $treasureItems, $bonus);
    }
}