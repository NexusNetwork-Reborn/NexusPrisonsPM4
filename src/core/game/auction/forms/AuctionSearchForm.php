<?php

declare(strict_types = 1);

namespace core\game\auction\forms;

use core\game\auction\inventory\AuctionListInventory;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\TranslationException;
use libs\form\CustomForm;
use libs\form\CustomFormResponse;
use libs\form\element\Input;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class AuctionSearchForm extends CustomForm {

    /**
     * AuctionSearchForm constructor.
     */
    public function __construct() {
        $title = TextFormat::BOLD . TextFormat::AQUA . "Search";
        $elements[] = new Input("Search", "Enter in a key word");
        parent::__construct($title, $elements);
    }

    /**
     * @param Player $player
     * @param CustomFormResponse $data
     *
     * @throws TranslationException
     */
    public function onSubmit(Player $player, CustomFormResponse $data): void {
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $entries = Nexus::getInstance()->getGameManager()->getAuctionManager()->getEntries();
        $search = $data->getString("Search");
        $found = [];
        foreach($entries as $entry) {
            $item = $entry->getItem();
            if(strpos(strtolower(TextFormat::clean($item->getName())), strtolower($search)) !== false) {
                $found[] = $entry;
                continue;
            }
            if(strpos(strtolower(TextFormat::clean($item->getCustomName())), strtolower($search)) !== false) {
                $found[] = $entry;
                continue;
            }
            foreach($item->getLore() as $lore) {
                if(strpos(strtolower(TextFormat::clean($lore)), strtolower($search)) !== false) {
                    $found[] = $entry;
                    break;
                }
            }
        }
        $player->sendDelayedWindow(new AuctionListInventory($found));
    }
}