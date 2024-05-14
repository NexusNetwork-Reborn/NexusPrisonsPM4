<?php

namespace core\game\slotbot;

use core\game\item\slotbot\SlotBotTicket;
use core\game\item\types\CustomItem;
use core\game\item\types\Rarity;
use core\game\item\types\vanilla\Fireworks;
use core\level\entity\types\FireworksRocket;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rank\Rank;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\utils\TextFormat as C;
use muqsit\invmenu\InvMenu;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\inventory\Inventory;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use pocketmine\world\sound\ClickSound;
use pocketmine\world\sound\FireExtinguishSound;

class SlotBotUI
{

    private const REROLL_LIMIT = 3;

    private const MAIN_REWARD = 0, POSSIBLE_REWARDS = 13, REROLL_SESSION = 17, UNTOUCHABLE_RED = 18, MIN_GREEN = 20, MAX_GREEN = 24, ROLL_BUTTON = 26, MIN_TICKETS = 47, MAX_TICKETS = 51, TUTORIAL = 53;

    private const BLACK_SLOTS = [1, 7, 10, 16, 19, 25, 28, 34, 37, 43];

    private const AIR_SLOTS = [2, 3, 4, 5, 6, 11, 12, 14, 15, 29, 30, 31, 32, 33, 38, 39, 40, 41, 42];

    private const RESERVED_SLOTS = [0, 13, 17, 18, 20, 21, 22, 23, 24, 26, 47, 48, 49, 50, 51, 53];

    private $menu = null;

    private $player = null;
    private $slotBotSession = null;

    private $requestedRolls = 0;

    private $canRoll = false;
    private $rolls = 0;

    private $rolling = false;

    private $terminated = false;
    private $claimedRewards = false;
    private $clickedNetherwart = false;

    private SlotBotRewardsUI $nextUI;

    public function __construct(Player $player)
    {
        $this->menu = new InvMenu(InvMenuHandler::getTypeRegistry()->get(InvMenu::TYPE_DOUBLE_CHEST));
        $this->player = $player;
        $this->slotBotSession = SlotBotRewardsSession::getRewardSession($player->getName());
        $this->menu->setName(TextFormat::AQUA . TextFormat::BOLD . "Cosmo" . TextFormat::WHITE . "-"  . TextFormat::colorize("&dSlot Bot"));
        $this->prepareItems();
        $this->prepareActionListener();
        $this->prepareClosingListener();
    }

    protected function prepareItems(): void
    {
        $inv = $this->menu->getInventory();

        $blackItem = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::BLACK())->asItem();
        $nullItem = $blackItem->setCustomName(" ");
        $rewardSlot = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::LIME())->asItem()->setCustomName(C::GREEN . C::BOLD . "Reward Slot");
        $insertSlotItem = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::RED())->asItem();
        for ($i = 0; $i < 54; $i++) {
            if (in_array($i, self::AIR_SLOTS)) {
                $inv->setItem($i, ItemFactory::air());
            } elseif (!in_array($i, self::BLACK_SLOTS) && !in_array($i, self::RESERVED_SLOTS)) {
                $inv->setItem($i, $nullItem);
            } elseif (in_array($i, self::BLACK_SLOTS)) {
                $inv->setItem($i, $blackItem);
            } elseif ($i === self::MAIN_REWARD) {
                $mainReward = $this->slotBotSession->getMainReward();

                $item = ItemFactory::getInstance()->get(
                    ItemIds::BEACON,
                    0,
                    1
                )->setCustomName(C::RED . C::BOLD . "(Top Reward) " . C::RESET . "\n\n" . $mainReward->getName())->setLore($mainReward->getLore());
                $item->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(50), 1));

                $inv->setItem($i, $item);
            } elseif ($i === self::POSSIBLE_REWARDS) {
                $item = ItemFactory::getInstance()->get(ItemIds::DYE, 6, 1)->setCustomName(C::AQUA . C::BOLD . "View Possible Rewards");
                $lore = [""];
                foreach ($this->slotBotSession->getChancedRewards() as [$reward, $chance]) {
                    $lore[] = C::WHITE . C::BOLD . "* " . C::RESET . $reward->getCustomName();
                }
                $item->setLore($lore);
                $item->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(50), 1));

                $inv->setItem($i, $item);
            } elseif ($i === self::UNTOUCHABLE_RED) {
                $inv->setItem($i, $blackItem); // TODO: Finish if needed or nah
                //$inv->setItem($i, ItemFactory::getInstance()->get($nullItem->getId(), 14, 1)->setCustomName($nullItem->getName())->setLore($nullItem->getLore()));
            } elseif ($i >= self::MIN_GREEN && $i <= self::MAX_GREEN) {
                $inv->setItem($i, $rewardSlot);
            } elseif ($i === self::REROLL_SESSION){
                $item = ItemFactory::getInstance()->get(ItemIds::NETHER_WART, 0, 1)->setCustomName(C::RED . C::BOLD . "Shuffle Rewards\n");
                $item->setLore([C::GRAY . "Shuffle the possible rewards that", C::GRAY . "you could win!"]);
                $inv->setItem($i, $item);
            }elseif ($i === self::ROLL_BUTTON) {
                $item = ItemFactory::getInstance()->get(ItemIds::MAGMA_CREAM, 0, 1)->setCustomName(C::RED . C::BOLD . "Spin (" . C::RESET . C::GRAY . "Missing Ticket" . C::RED . C::BOLD . ")\n");
                $item->setLore([C::GRAY . "Insert a Slot Bot Ticket", C::GRAY . "to spin the bot.", "", C::GRAY . "Purchase Slot Bot Tickets and Lootboxes at " . C::WHITE . "nexusprisons.tebex.io"]);

                $inv->setItem($i, $item);
            } elseif ($i >= self::MIN_TICKETS && $i <= self::MAX_TICKETS) {
                $tickets = $i - 46;

                $insertSlot = clone $insertSlotItem;
                $insertSlot->setCustomName(C::YELLOW . C::BOLD . "Use " . $tickets . " Slot Bot tickets!");
                $insertSlot->setCount($tickets);
                $insertSlot->setNamedTag($insertSlot->getNamedTag()->setInt("ticketReq", $tickets));

                $inv->setItem($i, $insertSlot);
            } elseif ($i === self::TUTORIAL) {
                $p = $this->getPlayer();
                if($p instanceof NexusPlayer) {
                    $roles = $this->rankToRollLimit($p);
                } else {
                    $roles = 0;
                }
                $item = ItemFactory::getInstance()->get(ItemIds::BOOK, 0, 1)->setCustomName(TextFormat::AQUA . TextFormat::BOLD . "How to Play\n");
                $item->setLore([
                    TextFormat::colorize("&d&l1. ") . TextFormat::RESET . TextFormat::GRAY . "Click on one of the red buttons",
                    TextFormat::GRAY . "at the bottom to insert Slot Bot tickets.",
                    "",
                    TextFormat::colorize("&d&l2. ") . TextFormat::RESET . TextFormat::GRAY . 'Click "Spin" (on the right) to roll the bot or,',
                    TextFormat::GRAY . "close the menu for your tickets back.",
                    "",
                    TextFormat::colorize("&d&l3. ") . TextFormat::RESET . TextFormat::GRAY . "When the bot finishes spinning",
                    TextFormat::GRAY . "the item which lands on the Reward Slot",
                    TextFormat::GRAY . "will be the item you receive.",
                    "",
                    TextFormat::BOLD . TextFormat::WHITE . "TIP: " . TextFormat::RESET . TextFormat::GRAY . "Closing the bot at any point will",
                    TextFormat::GRAY . "cancel the roll and return your tickets unless you're currently re-rolling.",
                    "",
                    TextFormat::BOLD . TextFormat::WHITE . "TIP: " . TextFormat::RESET . TextFormat::GRAY . 'Click "Re-Roll" (on the top right) to roll the bot again,',
                    TextFormat::GRAY . "but remember you only get {$roles} roles!"
                ]);
                $inv->setItem($i, $item);
            }
        }
    }

    public function getRewardSession(): ?SlotBotRewardsSession{
        return $this->slotBotSession;
    }

    protected function prepareActionListener(): void
    {
        $this->menu->setListener(
            InvMenu::readonly(function (DeterministicInvMenuTransaction $transaction): void {
                $insertAsBlock = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::RED());
                $insertSlotItem = $insertAsBlock->asItem()->setCustomName("");
                $item = $transaction->getItemClicked();
                if ($item->getId() === ItemIds::DYE) {
                    $this->nextUI = new SlotBotRewardsUI($this->player);
                    $this->menu->onClose($this->player);
                } elseif ($item->getId() === ItemIds::MAGMA_CREAM) {
                    $filledSlots = count($transaction->getPlayer()->getInventory()->getContents());
                    if ($this->canRoll && $this->rolls === 0) {
                        if (36 - $filledSlots >= $this->requestedRolls) {
                            $this->canRoll = false;
                            $this->rolling = true;
                            $this->getMenu()->getInventory()->setItem(self::POSSIBLE_REWARDS, ItemFactory::getInstance()->get(0));
                            $this->getMenu()->getInventory()->setItem(self::ROLL_BUTTON, ItemFactory::getInstance()->get(0));
                            Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new ScrollTask($this), 5);
                        } else {
                            $this->menu->onClose($this->player);
                            $this->player->sendMessage(C::RED . C::BOLD . "(!)" . C::RESET . C::GRAY . " Inventory is full, cannot spin the Slot Bot!");
                        }
                    }
                } elseif ($item->getId() === ItemIds::HEART_OF_THE_SEA){
                    if($this->canRoll){
                        $this->canRoll = false;
                        $this->rolling = true;
                        $this->getMenu()->getInventory()->setItem(self::POSSIBLE_REWARDS, ItemFactory::getInstance()->get(0));
                        $this->getMenu()->getInventory()->setItem(self::ROLL_BUTTON, ItemFactory::getInstance()->get(0));
                        Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new ScrollTask($this), 5);
                    }
                } elseif($item->getId() === ItemIds::NETHER_WART && !$this->clickedNetherwart){
                    $this->clickedNetherwart = true;
                    /** @var NexusPlayer $p */
                    $p = $transaction->getPlayer();
                    $p->sendMessage(TextFormat::RED . "This Has been disabled Due to a dupe!");
                    $this->getMenu()->onClose($this->getPlayer());
                    # Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use($p) : void {
                  #      $this->getMenu()->onClose($this->getPlayer());
                  #      SlotBotRewardsSession::addRewardSession($p->getName(), new SlotBotRewardsSession());
                   # }), 5); Dupe
                } elseif ($item->getId() === $insertSlotItem->getId() && !$this->canRoll && !$this->rolling && $insertAsBlock->getColor() === DyeColor::RED()) {
                    $requestedRolls = $item->getNamedTag()->getInt("ticketReq", 0);
                    if ($requestedRolls > 0) {
                        $ticketSlot = (new SlotBotTicket("Normal"))->toItem()->setCount(1);
                        $playerInv = $transaction->getPlayer()->getInventory();
                        $inv = $this->menu->getInventory();

                        $ticketSlotCounter = 0;
                        $allTickets = $playerInv->all($ticketSlot);
                        foreach ($allTickets as $index => $ticketItem) {
                            $ticketSlotCounter += $ticketSlot->equals($ticketItem) ? $ticketItem->getCount() : 0;
                        }

                        if ($ticketSlotCounter >= $requestedRolls) {
                            $this->getPlayer()->broadcastSound(new ClickSound());
                            $purpleItem = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::RED())->asItem(); //PURPLE???
                            $purpleItem->setCustomName("");
                            $playerInv->remove($ticketSlot);
                            $playerInv->addItem($ticketSlot->setCount($ticketSlotCounter - $requestedRolls));

                            $this->canRoll = true;
                            $this->requestedRolls = $requestedRolls;

                            for ($i = self::MIN_TICKETS; $i <= self::MAX_TICKETS; $i++) {
                                //$key = $i - 46;
                                //if ($key <= $requestedRolls) {
                                    $inv->setItem($i, $insertAsBlock->setColor(DyeColor::PURPLE())->asItem()->setCount($this->requestedRolls)->setCustomName(C::RED . C::BOLD . $this->requestedRolls . " Tickets Inserted!"));// $key or nah?
                                //}
                            }
                            $cream = $inv->getItem(self::ROLL_BUTTON);
                            $cream->setCustomName(C::GREEN . C::BOLD . "Spin\n");
                            $cream->setLore([C::GRAY . "Purchase Slot Bot Tickets and", "Lootboxes at " . C::WHITE . "nexusprisons.tebex.io"]);
                            $inv->setItem(self::ROLL_BUTTON, $cream);
                        }
                    }
                }
                //$this->clearItemInCursor($this->player);
            }
            ));
    }

    public function clearItemInCursor(Player $player)
    {
        $player->getCursorInventory()->setItem(0, ItemFactory::getInstance()->get(0));
    }


    protected function prepareClosingListener(): void
    {
        $this->menu->setInventoryCloseListener(
            function (Player $player, Inventory $inv): void {
                if($this->claimedRewards){
                    return;
                }
                if($this->rolls > 0 && $this->claimedRewards === false){
                    $this->closeSlotbotWithRewards();
                    return;
                }
                if ($this->canRoll || $this->rolling) {
                    $ticket = (new SlotBotTicket("Normal"))->toItem()->setCount($this->requestedRolls);
                    $player->getInventory()->addItem($ticket);
                    $this->terminated = true;
                    return;
                }
                if(isset($this->nextUI)) {
                    $this->nextUI?->send();
                    return;
                }
            }
        );
    }

    public function incrementRoll(){
        $p = $this->getPlayer();
        if($p instanceof NexusPlayer) {
            $limit = $this->rankToRollLimit($p);
        } else {
            $limit = 0;
        }
        $inv = $this->menu->getInventory();
        $this->rolls++;
        if($this->rolls === $limit+1){
            $this->closeSlotbotWithRewards();
            $blackItem = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::BLACK())->asItem();
            $nullItem = $blackItem->setCustomName(" ");
            $inv->setItem(8, $nullItem);
            return;
        }
        if($this->rolls >= $limit+1){
            $blackItem = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::BLACK())->asItem();
            $nullItem = $blackItem->setCustomName(" ");
            $inv->setItem(8, $nullItem);
            return;
        }
        $this->canRoll = true;
        $item = ItemFactory::getInstance()->get(ItemIds::HEART_OF_THE_SEA, 0, 1)->setCustomName(C::RED . C::BOLD . "Re-Roll \n");
        $leftRolls = $limit + 1 - $this->rolls;
        $item->setLore([C::GRAY . "Click to ReRoll the Slotbot.", C::GRAY . "You have {$leftRolls} rerolls left."]);
        $inv->setItem(8, $item);
    }

    private function rankToRollLimit(NexusPlayer $player) : int {
        switch ($player->getDataSession()->getRank()->getIdentifier()) {
            case Rank::PRESIDENT:
                return self::REROLL_LIMIT;
            case Rank::EMPEROR_HEROIC:
                return self::REROLL_LIMIT - 1;
            case Rank::EMPEROR:
                return self::REROLL_LIMIT - 2;
        }
        return 0;
    }

    public function closeSlotbotWithRewards(): void {
        $this->claimedRewards = true;
        $this->getPlayer()->broadcastSound(new FireExtinguishSound(), [$this->getPlayer()]);
        $msg = TextFormat::AQUA . TextFormat::BOLD . "Cosmo" . TextFormat::WHITE . "-"  . TextFormat::colorize("&dSlot Bot");
        $msg .= "\n" . TextFormat::RESET . TextFormat::GRAY . $this->getPlayer()->getName() . " rolled the SlotBot with " . TextFormat::WHITE . $this->getRequestedRolls() . " tickets" . TextFormat::GRAY . " and won:";
        foreach (ScrollTask::ROLLING_SLOTS as $i => $slots) {
            if ($i <= $this->getRequestedRolls()) {
                $item = $this->getMenu()->getInventory()->getItem($slots[2]);
                $v = $item->getNamedTag()->getTag(CustomItem::CUSTOM);
                if($v instanceof CompoundTag && $v->getString(CustomItem::UUID, "") !== "") {
                    $nbt = $item->getNamedTag();
                    $v->setString(CustomItem::UUID, md5(microtime()));
                    $item->setNamedTag($nbt->setTag(CustomItem::CUSTOM, $v));
                }
                $this->getPlayer()->getInventory()->addItem($item);
                $msg .= "\n" . TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . "* " . TextFormat::RESET . TextFormat::BOLD . $item->getCustomName();
            }
        }
        Nexus::getInstance()->getServer()->broadcastMessage("\n" . $msg . "\n");
        /**
        foreach (self::REWARD_SLOTS as $slot){
        $reward = $inv->getItem($slot) ;
        if($reward->getId() !== ItemIds::GLASS_PANE){
        $inv->setItem($slot, $rewardSlot);
        $player->addItem($reward);
        }
        }**/
        /**
         * $nullItem = InventoryUI::getNullItem();
         * $insertSlot = InventoryUI::getItem($nullItem->getId(), 14, $nullItem->getName(), $nullItem->getLore());
         * for($i = 47; $i <= 51; $i++){
         * $tickets = $i - 46;
         *
         * $insertSlot->setCustomName(C::YELLOW . "Click me to use " . $tickets . " slot tickets!");
         * $insertSlot->setCount($tickets);
         * $insertSlot->setNamedTag($insertSlot->getNamedTag()->setInt("ticketReq", $tickets));
         *
         * $inv->setItem($i, $insertSlot);
         * }**/

        $this->setRolling(false);

        Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() : void {
            $this->getMenu()->onClose($this->getPlayer());
        }), 100);
        $fw = ItemFactory::getInstance()->get(ItemIds::FIREWORKS);
        $fw->addExplosion(Fireworks::TYPE_STAR, Fireworks::COLOR_WHITE, Rarity::getFireworkColor("Uncommon"), true, true);
        $fw->setFlightDuration(1);
        $entity = new FireworksRocket($this->getPlayer()->getLocation(), $fw);
        if($entity instanceof FireworksRocket) {
            $entity->spawnToAll();
        }
    }

    public function isTerminated(): bool
    {
        return $this->terminated;
    }

    public function setRolling(bool $rolling): void
    {
        $this->rolling = $rolling;
    }

    public function getRequestedRolls(): int
    {
        return $this->requestedRolls;
    }

    public function getMenu(): InvMenu
    {
        return $this->menu;
    }

    public function getPlayer(): Player
    {
        return $this->player;
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

