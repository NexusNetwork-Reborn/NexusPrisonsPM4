<?php

namespace core\command\inventory;

use core\command\forms\KitChoiceForm;
use core\game\kit\Kit;
use core\player\NexusPlayer;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\utils\TextFormat;

class KitItemListInventory extends InvMenu {

    /** @var Kit */
    private $kit;

    /** @var int */
    private $lastRefresh = 0;

    /**
     * KitItemListInventory constructor.
     *
     * @param NexusPlayer $player
     * @param Kit $kit
     */
    public function __construct(NexusPlayer $player, Kit $kit) {
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InvMenu::TYPE_CHEST));
        $this->initItems($player, $kit);
        $this->kit = $kit;
        $this->setName(TextFormat::RESET . $kit->getColoredName());
        $this->setListener(self::readonly(function(DeterministicInvMenuTransaction $transaction): void {
            $slot = $transaction->getAction()->getSlot();
            $player = $transaction->getPlayer();
            if(!$player instanceof NexusPlayer) {
                return;
            }
            if($slot === 0) {
                $player->removeCurrentWindow();
                $player->sendDelayedForm(new KitChoiceForm($player, $this->kit));
            }
            if($slot === 4) {
                if((time() - $this->lastRefresh) < 1) {
                    return;
                }
                $this->lastRefresh = time();
                $player->playOrbSound();
                $this->initItems($player, $this->kit);
            }
            return;
        }));
    }

    /**
     * @param NexusPlayer $player
     * @param Kit $kit
     */
    public function initItems(NexusPlayer $player, Kit $kit): void {
        $glass = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 15, 1);
        $glass->setCustomName(" ");
        $items = $kit->giveTo($player, false);
        for($i = 0; $i < 27; $i++) {
            if(($i > 9 and $i < 17) or ($i > 18 and $i < 26)) {
                if(!empty($items)) {
                    $item = array_shift($items);
                    $this->getInventory()->setItem($i, $item);
                    continue;
                }
            }
            $this->getInventory()->setItem($i, $glass);
        }
        $home = ItemFactory::getInstance()->get(ItemIds::OAK_DOOR, 0, 1);
        $home->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::YELLOW . "Home");
        $lore = [];
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Return to the main";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "kit menu";
        $home->setLore($lore);
        $this->getInventory()->setItem(0, $home);
        $home = ItemFactory::getInstance()->get(ItemIds::ENDER_EYE, 0, 1);
        $home->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::GREEN . "Reroll");
        $lore = [];
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "See different outputs";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "of this kit";
        $home->setLore($lore);
        $this->getInventory()->setItem(4, $home);
    }
}