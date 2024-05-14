<?php

declare(strict_types = 1);

namespace core\command\forms;

use core\command\inventory\ShopListInventory;
use core\game\economy\EconomyCategory;
use core\game\economy\event\ItemBuyEvent;
use core\game\economy\PriceEntry;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\form\CustomForm;
use libs\form\CustomFormResponse;
use libs\form\element\Input;
use libs\form\element\Label;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class TransactionForm extends CustomForm {

    /** @var EconomyCategory */
    private $place;

    /** @var PriceEntry */
    private $priceEntry;

    /**
     * TransactionForm constructor.
     *
     * @param NexusPlayer $player
     * @param EconomyCategory $place
     * @param PriceEntry $entry
     */
    public function __construct(NexusPlayer $player, EconomyCategory $place, PriceEntry $entry) {
        $this->place = $place;
        $this->priceEntry = $entry;
        $title = TextFormat::BOLD . TextFormat::AQUA . $entry->getName();
        $elements = [];
        $message = TextFormat::GRAY . "Your balance: " . TextFormat::WHITE . "$" . number_format($player->getDataSession()->getBalance(), 2);
        $elements[] = new Label("Balance", $message);
        $buyPrice = $entry->getBuyPrice();
        if($buyPrice === null) {
            $buyPrice = TextFormat::WHITE . "Not buyable";
        }
        else {
            $buyPrice = TextFormat::WHITE . "$" . number_format($buyPrice, 2);
        }
        $elements[] = new Label("Buy Price", TextFormat::GREEN . "Unit Price: " . $buyPrice);
        $elements[] = new Input("Amount", "Amount");
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
        $all = $data->getAll();
        $amount = (int)$all["Amount"];
        $item = clone $this->priceEntry->getItem();
        if(!$item instanceof Item) {
            return;
        }
        if($this->priceEntry->getLevel() > $player->getDataSession()->getTotalXPLevel() and $player->getDataSession()->getPrestige() <= 0) {
            $player->sendMessage(Translation::getMessage("noPermission"));
            return;
        }
        if($amount <= 0 or (!is_numeric($amount))) {
            $player->sendMessage(Translation::getMessage("invalidAmount"));
            return;
        }
        $inventory = $player->getInventory();
        $price = $this->priceEntry->getBuyPrice() * $amount;
        $balance = $player->getDataSession()->getBalance();
        if($price > $balance) {
            $player->sendMessage(Translation::getMessage("notEnoughMoneyRankUp", [
                "amount" => TextFormat::RED . "$" . number_format($price)
            ]));
            return;
        }
        $item->setCount($amount * $this->priceEntry->getItem()->getCount());
        $inventory->addItem($item);
        $player->getDataSession()->subtractFromBalance($price);
        $player->sendMessage(Translation::getMessage("buy", [
            "amount" => TextFormat::AQUA . "x" . number_format($item->getCount()),
            "item" => TextFormat::AQUA . $this->priceEntry->getName(),
            "price" => TextFormat::LIGHT_PURPLE . "$" . number_format($price, 2),
        ]));
        $event = new ItemBuyEvent($player, $item, $price);
        $event->call();
        $player->sendDelayedWindow(new ShopListInventory($this->place));
    }
}