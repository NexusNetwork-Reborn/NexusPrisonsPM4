<?php

namespace core\command\inventory;

use core\command\forms\ItemInformationForm;
use core\game\item\types\custom\Lootbox;
use core\game\item\types\vanilla\Pickaxe;
use core\game\rewards\Reward;
use core\game\rewards\RewardsManager;
use core\Nexus;
use core\player\NexusPlayer;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\VanillaBlocks;
use pocketmine\utils\TextFormat;

class LootboxRewardsInventory extends InvMenu {

    /**
     * LootboxInventory constructor.
     */
    public function __construct() {
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InvMenu::TYPE_DOUBLE_CHEST));
        $this->initItems();
        $lootbox = Nexus::getInstance()->getGameManager()->getRewardsManager()->getLootbox(RewardsManager::CURRENT_LOOTBOX);
        $this->setName(TextFormat::RESET . TextFormat::WHITE . TextFormat::BOLD . "Lootbox: " . $lootbox->getColoredName());
        $this->setListener(self::readonly(function(DeterministicInvMenuTransaction $transaction): void {
            $itemClicked = $transaction->getItemClicked();
            /** @var NexusPlayer $player */
            $player = $transaction->getPlayer();
            if(!$itemClicked->isNull()) {
                $player->removeCurrentWindow();
                $player->sendDelayedForm(new ItemInformationForm($itemClicked));
            }
        }));
    }

    /**
     * @param NexusPlayer $player
     * @param Pickaxe $pickaxe
     */
    public function initItems(): void {
        $i = 0;
        $lootbox = Nexus::getInstance()->getGameManager()->getRewardsManager()->getLootbox(RewardsManager::CURRENT_LOOTBOX);
        $rewards = array_merge($lootbox->getAllRewards(), $lootbox->getBonus());
        /** @var Reward $reward */
        foreach($rewards as $reward) {
            $this->inventory->setItem($i, $reward->executeCallback());
            ++$i;
        }
    }
}