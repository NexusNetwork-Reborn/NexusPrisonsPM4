<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\args\RawStringArgument;
use core\command\utils\Command;
use core\game\economy\event\ItemSellEvent;
use core\game\item\mask\Mask;
use core\game\item\types\custom\MoneyNote;
use core\game\item\types\vanilla\Armor;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rank\Rank;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;

class SellCommand extends Command {

    /**
     * SellCommand constructor.
     */
    public function __construct() {
        parent::__construct("sell", "Sell items", "/sell <hand/all>", ["sa"]);
        $this->registerArgument(0, new RawStringArgument("hand/all"));
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if($sender instanceof NexusPlayer and $sender->hasPermission("permission.tier2")) {
            $inventory = $sender->getInventory();
            $sellables = $this->getCore()->getGameManager()->getEconomyManager()->getSellables();
            if($commandLabel === "sa") {
                $this->sellAll($sender);
                return;
            }
            if(!isset($args[0])) {
                $sender->sendMessage(Translation::getMessage("usageMessage", [
                    "usage" => $this->getUsage()
                ]));
                return;
            }
            switch($args[0]) {
                case "hand":
                    $item = $inventory->getItemInHand();
                    $sellable = false;
                    $entry = null;
                    if(isset($sellables[$item->getId()])) {
                        $entry = $sellables[$item->getId()];
                        if($entry->equal($item)) {
                            $sellable = true;
                        }
                    }
                    if($sellable === false) {
                        $sender->sendMessage(Translation::getMessage("nothingSellable"));
                        return;
                    }
                    $count = $item->getCount();
                    $price = $count * $entry->getSellPrice();
                    $inventory->removeItem($item);
                    $helmet = $sender->getArmorInventory()->getHelmet();
                    if($helmet instanceof Armor) {
                        if($helmet->hasMask(Mask::GUARD)) {
                            $price *= 1.1;
                        }
                    }
                    $event = new ItemSellEvent($sender, $item, $price);
                    $event->call();
                    $sender->sendMessage(Translation::getMessage("sell", [
                        "amount" => TextFormat::AQUA . number_format((int)$count),
                        "price" => TextFormat::LIGHT_PURPLE . "$" . number_format((int)$price, 2),
                    ]));
                    $areas = $this->getCore()->getServerManager()->getAreaManager()->getAreasInPosition($sender->getPosition());
                    $safe = false;
                    if($areas !== null) {
                        foreach($areas as $area) {
                            if($area->getPvpFlag() === false) {
                                $safe = true;
                                break;
                            }
                        }
                    }
                    if($safe === false) {
                        $deduct = 0.15;
                        $deduct = round($price * $deduct, 2);
                        $sender->sendMessage(TextFormat::GRAY . "Remember, you lose 15% of profits using this command!");
                        $price -= $deduct;
                        $sender->addItem((new MoneyNote($price))->toItem(), true);
                        return;
                    }
                    $sender->getDataSession()->addToBalance($price);
                    return;
                    break;
                case "all":
                    $this->sellAll($sender);
                    break;
            }
            return;
        }
        $sender->sendMessage(Translation::getMessage("noPermission"));
        $rankRequired = Nexus::getInstance()->getPlayerManager()->getRankManager()->getRankByIdentifier(Rank::IMPERIAL);
        $sender->sendMessage(Translation::RED . "You must have " . $rankRequired->getColoredName() . TextFormat::RESET . TextFormat::GRAY . " Rank or up to use this command!");
        return;
    }

    /**
     * @param NexusPlayer $sender
     *
     * @throws TranslationException
     */
    private function sellAll(NexusPlayer $sender): void {
        $inventory = $sender->getInventory();
        $sellables = $this->getCore()->getGameManager()->getEconomyManager()->getSellables();
        $content = $sender->getInventory()->getContents();
        /** @var Item[] $items */
        $items = [];
        $sellable = false;
        $entries = [];
        foreach($content as $item) {
            if(!isset($sellables[$item->getId()])) {
                continue;
            }
            $entry = $sellables[$item->getId()];
            if(!$entry->equal($item)) {
                continue;
            }
            if($sellable === false) {
                $sellable = true;
            }
            if(!isset($entries[$entry->getName()])) {
                $entries[$entry->getName()] = $entry;
                $items[$entry->getName()] = $item;
            }
            else {
                $items[$entry->getName()]->setCount($items[$entry->getName()]->getCount() + $item->getCount());
            }
        }
        if($sellable === false) {
            $sender->sendMessage(Translation::getMessage("nothingSellable"));
            return;
        }
        $price = 0;
        $count = 0;
        foreach($entries as $entry) {
            $item = $items[$entry->getName()];
            $price += $item->getCount() * $entry->getSellPrice();
            $sender->removeItemExact($item);
            $event = new ItemSellEvent($sender, $item, $price);
            $event->call();
            $count += $item->getCount();
        }
        $helmet = $sender->getArmorInventory()->getHelmet();
        if($helmet instanceof Armor) {
            if($helmet->hasMask(Mask::GUARD)) {
                $price *= 1.1;
            }
        }
        $areas = $this->getCore()->getServerManager()->getAreaManager()->getAreasInPosition($sender->getPosition());
        $safe = false;
        if($areas !== null) {
            foreach($areas as $area) {
                if($area->getPvpFlag() === false) {
                    $safe = true;
                    break;
                }
            }
        }
        $deduct = 0.15;
        $deduct = round($price * $deduct, 2);
        $sender->sendMessage(TextFormat::GRAY . "Remember, you lose 15% of profits using this command!");
        $price -= $deduct;
        $sender->sendMessage(Translation::getMessage("sell", [
            "amount" => TextFormat::AQUA . number_format($count),
            "price" => TextFormat::LIGHT_PURPLE . "$" . number_format($price, 2)
        ]));
        if($safe === false) {
            $sender->addItem((new MoneyNote($price))->toItem(), true);
        }
        else {
            $sender->getDataSession()->addToBalance($price);
        }
    }
}