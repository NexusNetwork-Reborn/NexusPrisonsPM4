<?php

namespace core\command\inventory;

use core\command\task\TeleportTask;
use core\game\boss\BossFight;
use core\game\boss\task\BossSummonTask;
use core\game\item\ItemManager;
use core\game\kit\Kit;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rank\Rank;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\Inventory;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;

class BossInventory extends InvMenu {

    /**
     * BossInventory constructor.
     *
     * @param NexusPlayer $player
     */
    public function __construct(NexusPlayer $player) {
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InvMenu::TYPE_HOPPER));
        $this->initItems($player);
        $this->setName(TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . "Bosses");
        $task = new ClosureTask(function() use($player) : void {
            $this->initItems($player);
        });
        Nexus::getInstance()->getScheduler()->scheduleRepeatingTask($task, 20);
        $this->setListener(self::readonly(function(DeterministicInvMenuTransaction $transaction): void {
            $slot = $transaction->getAction()->getSlot();
            $player = $transaction->getPlayer();
            if(!$player instanceof NexusPlayer) {
                return;
            }
            if($slot > 0 and $slot < 4) {
                $bosses = [BossSummonTask::getBossFight()];
                /** @var BossFight $boss */
                $boss = $bosses[$slot - 1] ?? null;
                if(is_null($boss)) {
                    $player->playErrorSound();
                    return;
                }
                $access = $boss->canAccess($player) or $player->hasPermission(DefaultPermissions::ROOT_OPERATOR);
                if($access && $boss->isOpenForJoining() && !$boss->inArena($player)) {
                    //$player->removeCurrentWindow();
                    $boss->addPlayer($player);
                    $this->initItems($player);
                    $player->playDingSound(); // TODO: Reconsider this sound.
                } elseif($access && $boss->isOpenForJoining() && $boss->inArena($player)) {
                    //$player->removeCurrentWindow();
                    $boss->removePlayer($player);
                    $this->initItems($player);
                    $player->playDingSound(); // TODO: Reconsider this sound.
                } else {
                    $player->playErrorSound();
                }
            }
        }));
        $this->setInventoryCloseListener(function(Player $player, Inventory $inventory) use($task) : void {
            $task->getHandler()->cancel();
        });
    }

    /**
     * @param NexusPlayer $player
     * @param Kit $kit
     */
    public function initItems(NexusPlayer $player): void {
        $glass = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 15, 1);
        $glass->setCustomName(" ");
        $bosses = [BossSummonTask::getBossFight()];
        for($i = 0; $i < 5; $i++) {
            if($i > 0 and $i < 4) {
                if(!empty($bosses)) {
                    /** @var BossFight $boss */
                    $boss = array_shift($bosses);
                    if(!is_null($boss) && $boss->isOpenForJoining()) {
                        if(!$boss->inArena($player)) {
                            $item = $boss->getItem()->setCount(1);
                        } else {
                            $item = VanillaBlocks::BARRIER()->asItem()->setCount(1);
                        }
                        $lore = [];
                        $lore[] = "";
                        $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::GREEN . "QUEUE OPEN: " . TextFormat::WHITE . $boss->getSummonTask()->getTimeToStart();
                    }
                    elseif(!is_null($boss)&& $boss->isStarted()) {
                        $item = VanillaBlocks::WOOL()->setColor(DyeColor::RED())->asItem()->setCount(1);
                        $lore = [];
                        $lore[] = "";
                        $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "STARTED";
                    } else {
                        $item = VanillaBlocks::WOOL()->setColor(DyeColor::BLUE())->asItem()->setCount(1);
                        $lore = [];
                        $lore[] = "";
                        $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::BLUE . "STARTING IN: " . TextFormat::WHITE . $boss->getSummonTask()->getTimeToStart();
                    } // TODO: Potentially LOCKED if Fund Manager implemented
                    $color = $boss->getColor();
                    $item->setCustomName(TextFormat::RESET . $boss->getName());
                    $lore[] = "";
                    if($boss->canAccess($player)) {
                        $lore[] = TextFormat::RESET . TextFormat::BOLD . $color . "Required Level: " . TextFormat::WHITE . $boss->getLevel() . " or Prestige " . TextFormat::AQUA . "<" . TextFormat::LIGHT_PURPLE . "I" . TextFormat::AQUA . ">";
                        if($boss->isOpenForJoining()) {
                            $lore[] = TextFormat::RESET . TextFormat::BOLD . $color . "In Queue: " . TextFormat::WHITE . $boss->participantCount() . " players";
                            $lore[] = "";
                            if($boss->inArena($player)) {
                                $lore[] = TextFormat::RESET . TextFormat::GRAY . "(Click to leave)";
                            } else {
                                $lore[] = TextFormat::RESET . TextFormat::GRAY . "(Click to join)";
                            }
                        } else if($boss->isStarted()) {
                            $lore[] = TextFormat::RESET . TextFormat::BOLD . $color . "Participants: " . TextFormat::WHITE . $boss->participantCount() . "/" . $boss->getMaxParticipants() . " remaining";
                            $lore[] = "";
                            $lore[] = TextFormat::RESET . TextFormat::GRAY . "Event already started!"; // TODO: Spectate?
                        }
                    } else {
                        $lore[] = TextFormat::RESET . TextFormat::BOLD . $color . "Required Level: " . TextFormat::WHITE . $boss->getLevel() . " or Prestige " . TextFormat::AQUA . "<" . TextFormat::LIGHT_PURPLE . "I" . TextFormat::AQUA . ">";
                        $lore[] = "";
                        $lore[] = TextFormat::RESET . TextFormat::RED . "Reach the required level to join!";
                    }
                    $item->setLore($lore);
                    $this->getInventory()->setItem($i, $item);
                    continue;
                }
            }
            $this->getInventory()->setItem($i, $glass);
        }
    }
}