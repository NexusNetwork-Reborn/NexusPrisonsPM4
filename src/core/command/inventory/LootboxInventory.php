<?php

namespace core\command\inventory;

use core\command\task\TickLootboxInventory;
use core\game\item\types\custom\Lootbox;
use core\game\item\types\vanilla\Pickaxe;
use core\game\rewards\RewardsManager;
use core\Nexus;
use core\player\NexusPlayer;
use core\server\inventory\InventoryTypes;
use core\translation\Translation;
use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class LootboxInventory extends InvMenu {

    /**
     * LootboxInventory constructor.
     */
    public function __construct() {
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InventoryTypes::TYPE_DISPENSER));
        $this->initItems();
        $lootbox = Nexus::getInstance()->getGameManager()->getRewardsManager()->getLootbox(RewardsManager::CURRENT_LOOTBOX);
        $this->setName(TextFormat::RESET . TextFormat::WHITE . TextFormat::BOLD . "Lootbox: " . $lootbox->getColoredName());
        $this->setListener(self::readonly(function(DeterministicInvMenuTransaction $transaction): void {
            $slot = $transaction->getAction()->getSlot();
            /** @var NexusPlayer $player */
            $player = $transaction->getPlayer();
            if($slot === 4) {
                $player->removeCurrentWindow();
                $player->sendDelayedWindow(new LootboxRewardsInventory());
            }
        }));
        Nexus::getInstance()->getScheduler()->scheduleRepeatingTask(new TickLootboxInventory($this), 20);
    }

    public function initItems(): void {
        $colors = DyeColor::getAll();
        for($i = 0; $i < 9; $i++) {
            $placeholder = VanillaBlocks::STAINED_GLASS_PANE()->setColor($colors[array_rand($colors)])->asItem();
            $placeholder->setCustomName(TextFormat::RESET . TextFormat::GRAY . "nexusprisons.tebex.io");
            if($i === 4) {
                $this->getInventory()->setItem($i, (new Lootbox(RewardsManager::CURRENT_LOOTBOX))->toItem());
                continue;
            }
            $this->getInventory()->setItem($i, $placeholder);
        }
    }

    public function tick(): bool {
        $colors = DyeColor::getAll();for($i = 0; $i < 9; $i++) {
            $placeholder = VanillaBlocks::STAINED_GLASS_PANE()->setColor($colors[array_rand($colors)])->asItem();
            $placeholder->setCustomName(TextFormat::RESET . TextFormat::GRAY . "nexusprisons.tebex.io");
            if($i === 4) {
                continue;
            }
            $this->getInventory()->setItem($i, $placeholder);
        }
        foreach($this->getInventory()->getViewers() as $viewer) {
            if($viewer instanceof NexusPlayer and $viewer->isOnline()) {
                $viewer->getNetworkSession()->getInvManager()->syncContents($this->getInventory());
            }
        }
        return true;
    }
}