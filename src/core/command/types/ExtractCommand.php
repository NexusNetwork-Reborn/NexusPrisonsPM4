<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\forms\WithdrawForm;
use core\command\utils\args\IntegerArgument;
use core\command\utils\Command;
use core\game\item\mask\Mask;
use core\game\item\types\custom\EnchantmentBook;
use core\game\item\types\custom\Energy;
use core\game\item\types\custom\Satchel;
use core\game\item\types\custom\Trinket;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Pickaxe;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class ExtractCommand extends Command {

    /**
     * WithdrawCommand constructor.
     */
    public function __construct() {
        parent::__construct("extract", "Extract energy from your item in your hand.", "/extract <amount>", ["splitnrg"]);
        $this->registerArgument(0, new IntegerArgument("amount"));
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if($sender instanceof NexusPlayer) {
            if(isset($args[0])) {
                $amount = Utils::shortenToNumber($args[0]) !== null ? (int)Utils::shortenToNumber($args[0]) : (int)$args[0];
                $amount = (int)$amount;
                if($amount <= 0) {
                    $sender->sendMessage(Translation::getMessage("invalidAmount"));
                    return;
                }
                $item = $sender->getInventory()->getItemInHand();
                if($item instanceof Pickaxe) {
                    $energy = $item->getMaxSubtractableEnergy();
                }
                elseif(Satchel::isInstanceOf($item)) {
                    $energy = Satchel::fromItem($item)->getMaxSubtractableEnergy();
                }
                elseif(Trinket::isInstanceOf($item)) {
                    $energy = Trinket::fromItem($item)->getEnergy();
                }
                elseif(EnchantmentBook::isInstanceOf($item)) {
                    $energy = EnchantmentBook::fromItem($item)->getEnergy();
                }
                elseif(Energy::isInstanceOf($item)) {
                    $energy = Energy::fromItem($item)->getEnergy();
                }
                else {
                    $energy = 0;
                }
                if($energy <= 0) {
                    $sender->sendMessage(Translation::getMessage("invalidItem"));
                    return;
                }
                if($amount > $energy) {
                    $sender->sendMessage(Translation::getMessage("invalidAmount"));
                    return;
                }
                if($item instanceof Pickaxe) {
                    $item->subtractEnergy($amount);
                    $sender->getInventory()->setItemInHand($item);
                    $helm = $sender->getArmorInventory()->getHelmet();
                    $deduct = 0.1;
                    if ($helm instanceof Armor && $helm->hasMask(Mask::TINKERER)) {
                        $deduct = 0;
                    }
                    if($deduct > 0) {
                        $percent = $deduct * 100;
                        $deduct = (int)floor($amount * $deduct);
                        $sender->sendMessage(TextFormat::GRAY . "(-$percent% tax)");
                        $amount -= $deduct;
                    }
                }
                else {
                    $leftOver = $energy - $amount;
                    if(!Energy::isInstanceOf($item)) {
                        if(EnchantmentBook::isInstanceOf($item)) {
                            $book = EnchantmentBook::fromItem($item);
                            $book->setEnergy($leftOver);
                            $item = $book->toItem();
                        }
                        elseif(Satchel::isInstanceOf($item)) {
                            $item = Satchel::fromItem($item);
                            $item->addEnergy(-$amount);
                            $item = $item->toItem();
                        }
                        elseif(Trinket::isInstanceOf($item)) {
                            $item = Trinket::fromItem($item);
                            $item->setEnergy($leftOver);
                            $item = $item->toItem();
                        }
                        $helm = $sender->getArmorInventory()->getHelmet();
                        $deduct = 0.1;
                        if ($helm instanceof Armor && $helm->hasMask(Mask::TINKERER)) {
                            $deduct = 0;
                        }
                        if($deduct > 0) {
                            $percent = $deduct * 100;
                            $deduct = (int)floor($amount * $deduct);
                            $sender->sendMessage(TextFormat::GRAY . "(-$percent% tax)");
                            $amount -= $deduct;
                        }
                    }
                    else {
                        $item = Energy::fromItem($item);
                        $item->setEnergy($leftOver);
                        $item = $item->toItem();
                    }
                }
                $sender->getInventory()->setItemInHand($item);
                $sender->getInventory()->addItem((new Energy($amount, $sender->getName()))->toItem());
                $sender->playDingSound();
                return;
            }
            $sender->sendMessage(Translation::getMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]));
            return;
        }
        $sender->sendMessage(Translation::getMessage("noPermission"));
    }
}