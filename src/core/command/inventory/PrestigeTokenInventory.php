<?php

namespace core\command\inventory;

use core\game\item\ItemManager;
use core\game\item\types\custom\PrestigeToken;
use core\game\item\types\vanilla\Pickaxe;
use core\game\kit\Kit;
use core\player\NexusPlayer;
use core\player\rpg\RPGManager;
use core\player\rpg\XPUtils;
use core\server\inventory\InventoryTypes;
use core\translation\Translation;
use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\BlockFactory;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class PrestigeTokenInventory extends InvMenu {

    /** @var PrestigeToken */
    private $token;

    /**
     * PrestigeTokenInventory constructor.
     *
     * @param NexusPlayer $player
     * @param PrestigeToken $token
     */
    public function __construct(NexusPlayer $player, PrestigeToken $token) {
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InventoryTypes::TYPE_DISPENSER));
        $this->initItems($player);
        $this->token = $token;
        $this->setName(TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . "Pickaxe Prestige");
        $this->setListener(self::readonly(function(DeterministicInvMenuTransaction $transaction): void {
            $slot = $transaction->getAction()->getSlot();
            /** @var NexusPlayer $player */
            $player = $transaction->getPlayer();
            $itemClickWith = $transaction->getItemClickedWith();
            if($slot === 4) {
                if($itemClickWith instanceof Pickaxe) {
                    $prestige = $this->token->getPrestige();
                    if($itemClickWith->getPrestige() >= $prestige) {
                        $player->removeCurrentWindow();
                        $player->playErrorSound();
                        $player->sendMessage(Translation::RED . "This token can only prestige your pickaxe up to a maximum prestige of $prestige!");
                        return;
                    }
                    $this->setInventoryCloseListener(function(Player $player, InvMenuInventory $inventory): void {});
                    $player->removeCurrentWindow();
                    $player->sendDelayedWindow(new SelectPrestigeInventory($player, $itemClickWith, $this->token));
                }
                else {
                    $player->removeCurrentWindow();
                    $player->playErrorSound();
                    $player->sendTranslatedMessage("invalidItem");
                }
            }
        }));
        $this->setInventoryCloseListener(function(Player $player, InvMenuInventory $inventory): void {
            if($this->token !== null) {
                $item = $this->token->toItem()->setCount(1);
                if($player->getInventory()->canAddItem($item)) {
                    $player->getInventory()->addItem($item);
                }
                else {
                    $player->getWorld()->dropItem($player->getPosition(), $item);
                }
            }
        });
    }

    /**
     * @param NexusPlayer $player
     * @param Pickaxe $pickaxe
     */
    public function initItems(NexusPlayer $player): void {
        $glass = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::WHITE())->asItem()->setCount(1);
        $glass->setCustomName(TextFormat::RESET . TextFormat::GRAY . "Place a pickaxe into the empty slot");
        for($i = 0; $i < 9; $i++) {
            if($i === 4) {
                continue;
            }
            $this->getInventory()->setItem($i, $glass);
        }
    }
}