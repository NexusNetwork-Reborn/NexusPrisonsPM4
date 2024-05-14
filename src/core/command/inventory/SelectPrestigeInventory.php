<?php

namespace core\command\inventory;

use core\game\item\enchantment\EnchantmentManager;
use core\game\item\event\PrestigePickaxeEvent;
use core\game\item\ItemManager;
use core\game\item\prestige\Prestige;
use core\game\item\types\custom\PrestigeToken;
use core\game\item\types\vanilla\Pickaxe;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rpg\RPGManager;
use core\player\rpg\XPUtils;
use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class SelectPrestigeInventory extends InvMenu {

    /** @var Prestige[] */
    private $prestiges = [];

    /** @var Pickaxe */
    private $pickaxe;

    /** @var PrestigeToken|null */
    private $token;

    /**
     * SelectPrestigeInventory constructor.
     *
     * @param NexusPlayer $player
     * @param Pickaxe $pickaxe
     * @param PrestigeToken|null $token
     */
    public function __construct(NexusPlayer $player, Pickaxe $pickaxe, ?PrestigeToken $token = null) {
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InvMenu::TYPE_DOUBLE_CHEST));
        $this->initItems($player, $pickaxe);
        $this->pickaxe = $pickaxe;
        $this->token = $token;
        $this->setName(TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . "Pickaxe Prestige");
        $this->setListener(self::readonly(function(DeterministicInvMenuTransaction $transaction): void {
            $slot = $transaction->getAction()->getSlot();
            $player = $transaction->getPlayer();
            if(!$player instanceof NexusPlayer) {
                return;
            }
            if(isset($this->prestiges[$slot])) {
                $prestige = $this->prestiges[$slot];
                if(!$prestige->isEligible($this->pickaxe)) {
                    $player->playErrorSound();
                    return;
                }
                $inventory = $player->getInventory();
                if(!$inventory->contains($this->pickaxe)) {
                    $player->playErrorSound();
                    $player->removeCurrentWindow();
                    $player->sendTranslatedMessage("itemNotFound");
                    return;
                }
                if($this->token !== null) {
                    $this->token->setUsed();
                    $this->setInventoryCloseListener(function(Player $player, InvMenuInventory $inventory): void {
                    });
                }
                $player->removeCurrentWindow();
                $inventory->removeItem($this->pickaxe);
                $this->pickaxe->addPrestige();
                $this->pickaxe->setAttribute($prestige->getIdentifier(), $prestige->getNewValue($this->pickaxe));
                $inventory->addItem($this->pickaxe);
                $player->playDingSound();
                $ev = new PrestigePickaxeEvent($player);
                $ev->call();
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
    public function initItems(NexusPlayer $player, Pickaxe $pickaxe): void {
        $glass = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 15, 1);
        $glass->setCustomName(" ");
        $prestiges = Nexus::getInstance()->getGameManager()->getItemManager()->getPickaxePrestiges();
        for($i = 0; $i < 54; $i++) {
            if($i === 4) {
                $this->getInventory()->setItem($i, $pickaxe);
                continue;
            }
            if(($i >= 18 and $i <= 26) or $i === 31) {
                $prestige = array_shift($prestiges);
                $this->prestiges[$i] = $prestige;
                $this->getInventory()->setItem($i, $prestige->getDisplayItem($pickaxe));
                continue;
            }
            $this->getInventory()->setItem($i, $glass);
        }
    }
}