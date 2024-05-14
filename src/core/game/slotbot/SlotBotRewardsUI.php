<?php

namespace core\game\slotbot;

use core\Nexus;
use draxite\Loader;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\Inventory;
use pocketmine\item\ItemFactory;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\utils\TextFormat as C;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;

class SlotBotRewardsUI {

    private const BACK_BUTTON = 45, NEXT_BUTTON = 53;

    private $menu = null;

    private $player = null;

    private $slotBotRegistry = null;

    private $nextUI = null;

    private $page;

    public function __construct(Player $player, int $page = 0)
    {
        $this->menu = new InvMenu(InvMenuHandler::getTypeRegistry()->get(InvMenu::TYPE_DOUBLE_CHEST));
        $this->player = $player;
        $this->slotBotRegistry = SlotBotRewardsSession::getRewardSession($player->getName());
        $this->menu->setName(TextFormat::AQUA . TextFormat::BOLD . "Cosmo" . TextFormat::WHITE . "-"  . TextFormat::colorize("&dSlot Bot") . C::GREEN . " Rewards");
        $this->page = $page;

        $this->prepareItems();
        $this->prepareActionListener();
        $this->prepareClosingListener();
    }

    protected function prepareItems(): void
    {
        $blackItem = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::BLACK())->asItem();
        $nullItem = $blackItem->setCustomName(" ");
        $backItem = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::RED())->asItem()->setCustomName(C::BOLD . C::RED . "Back")->setLore([C::GRAY . "Go back to the previous\nmenu!"]);
        $backItem->setNamedTag($backItem->getNamedTag()->setByte("back", 1));
        for ($i = 0; $i < 54; $i++) {
            $this->menu->getInventory()->setItem($i, $nullItem);
        }
        $inv = $this->menu->getInventory();
        $chancedRewards = $this->slotBotRegistry->getChancedRewards();
        $slicedRewards = array_splice($chancedRewards, 35 * $this->page, 36);
        foreach ($slicedRewards as $i => [$reward, $chance]){
            $inv->setItem($i, $reward);
        }
        if($this->page > 0){
            $inv->setItem(self::BACK_BUTTON, $backItem);
        }

        $nextSlice = $slicedRewards = array_splice($chancedRewards, 35 * ($this->page + 1), 36);
        if(count($nextSlice) > 0){
            $nextButton = ItemFactory::getInstance()->get(ItemIds::EMERALD_BLOCK, 0)->setCustomName(C::GREEN . "Next");
            $nextButton->setNamedTag($nextButton->getNamedTag()->setByte("next", 1));

            $inv->setItem(self::NEXT_BUTTON, $nextButton);
        }
    }

    protected function prepareActionListener(): void
    {
        $this->menu->setListener(
            InvMenu::readonly(function(DeterministicInvMenuTransaction $transaction) : void {
                $nbt = $transaction->getItemClicked()->getNamedTag();
                $back = (bool)$nbt->getByte("back", 0);
                $next = (bool)$nbt->getByte("next", 0);

                if($back){
                    $this->nextUI = new SlotBotRewardsUI($this->player, $this->page - 1);
                    $this->menu->onClose($this->player);
                } elseif($next){
                    $this->nextUI = new SlotBotRewardsUI($this->player, $this->page + 1);
                    $this->menu->onClose($this->player);
                }
            })
        );
    }

    protected function prepareClosingListener() : void{
        $this->menu->setInventoryCloseListener(
            function(Player $player, Inventory $inventory) : void{
                if(isset($this->nextUI)) {
                    $this->nextUI?->send();
                }
            }
        );
    }

    public function send(bool $instant = false){
        if(!$instant) {
            Nexus::getInstance()->getScheduler()->scheduleDelayedTask(
                new ClosureTask(function (): void {
                    $this->menu->send($this->player);
                }),
                15
            );
        } else {
            $this->menu->send($this->player);
        }
    }

}