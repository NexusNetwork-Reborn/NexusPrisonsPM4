<?php

namespace core\game\quest\inventory;

use core\command\forms\TransactionForm;
use core\game\economy\EconomyCategory;
use core\game\economy\EconomyException;
use core\game\economy\event\ItemSellEvent;
use core\game\economy\PriceEntry;
use core\game\item\mask\Mask;
use core\game\item\types\custom\Satchel;
use core\game\item\types\custom\Token;
use core\game\item\types\Interactive;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Axe;
use core\game\item\types\vanilla\Bow;
use core\game\item\types\vanilla\Pickaxe;
use core\game\item\types\vanilla\Sword;
use core\game\quest\QuestShopItem;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

class QuestShopInventory extends InvMenu {

    /** @var QuestShopItem[] */
    private $entries;

    /**
     * QuestShopInventory constructor.
     *
     * @param array $items
     */
    public function __construct(array $items) {
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InvMenu::TYPE_DOUBLE_CHEST));
        $this->initItems($items);
        $this->setName(TextFormat::RESET . TextFormat::DARK_AQUA . TextFormat::BOLD . "Mystery Man");
        $this->setListener(self::readonly(function(DeterministicInvMenuTransaction $transaction): void {
            $slot = $transaction->getAction()->getSlot();
            $player = $transaction->getPlayer();
            if(!$player instanceof NexusPlayer) {
                return;
            }
            if(isset($this->entries[$slot])) {
                $entry = $this->entries[$slot];
                $tokens = 0;
                foreach($player->getInventory()->getContents() as $slot => $item) {
                    if(Token::isInstanceOf($item)) {
                        $tokens += $item->getCount();
                    }
                }
                if($entry->getPrice() > $tokens) {
                    $player->playErrorSound();
                    $player->sendTranslatedMessage("notEnoughTokens");
                    return;
                }
                if($player->getInventory()->firstEmpty() === -1) {
                    $player->playErrorSound();
                    $player->sendTranslatedMessage("fullInventory");
                    return;
                }
                $player->playDingSound();
                $player->getInventory()->removeItem((new Token())->toItem()->setCount($entry->getPrice()));
                $item = $entry->getItem();
                $matchedItem = Nexus::getInstance()->getGameManager()->getItemManager()->getItem($entry->getItem());
                if($matchedItem instanceof Interactive) {
                    $item = $matchedItem->createNewItem()->toItem();
                }
                $player->getInventory()->addItem($item->setCount(1));
                return;
            }
            return;
        }));
    }

    /**
     * @param array $entries
     */
    public function initItems(array $entries): void {
        for($i = 0; $i < 54; $i++) {
            if($this->getInventory()->isSlotEmpty($i)) {
                $entry = array_shift($entries);
                if($entry instanceof QuestShopItem) {
                    $display = clone $entry->getItem();
                    $this->entries[$i] = $entry;
                    $lore = $display->getLore();
                    $add = [];
                    $add[] = "";
                    $price = $entry->getPrice();
                    $add[] = TextFormat::RESET . TextFormat::WHITE . TextFormat::BOLD . number_format($price) . TextFormat::AQUA . " Tokens";
                    $add[] = "";
                    $add[] = TextFormat::RESET . TextFormat::GRAY . "Click to purchase 1";
                    $display->setLore(array_merge($lore, $add));
                    $this->getInventory()->setItem($i, $display);
                }
            }
        }
    }
}