<?php

namespace core\player\vault;

use core\Nexus;
use core\player\NexusPlayer;
use core\player\vault\forms\VaultListForm;
use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use pocketmine\inventory\transaction\action\DropItemAction;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\permission\DefaultPermissions;
use pocketmine\utils\TextFormat;

class Vault {

    /** @var string */
    private $owner;

    /** @var InvMenu */
    private $menu;

    /** @var int */
    private $id;

    /** @var int */
    private $uuid;

    /**
     * Vault constructor.
     *
     * @param string $owner
     * @param int $uuid
     * @param int $id
     * @param string $items
     */
    public function __construct(string $owner, int $uuid, int $id, string $items = "") {
        $this->owner = $owner;
        $this->id = $id;
        $this->uuid = $uuid;
        $this->menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
        $this->menu->setName(TextFormat::YELLOW . "PV $id");
        $this->menu->setListener(function(InvMenuTransaction $transaction): InvMenuTransactionResult {
            /** @var NexusPlayer $player */
            $player = $transaction->getPlayer();
            if($this->owner === $player->getName()) {
                return $transaction->continue();
            }
            if($player->hasPermission("permission.admin") or $player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
                return $transaction->continue();
            }
            return $transaction->discard();
        });
        if(!empty($items)) {
            $items = Nexus::decodeInventory($items);
            $i = 0;
            foreach($items as $item) {
                $this->menu->getInventory()->setItem($i, $item);
                ++$i;
            }
        }
        $this->menu->setInventoryCloseListener(function(NexusPlayer $player, InvMenuInventory $inventory) use($id): void {
            $items = Nexus::encodeInventory($inventory, true);
            $stmt = Nexus::getInstance()->getMySQLProvider()->getDatabase()->prepare("REPLACE INTO vaults(uuid, username, id, items) VALUES(?, ?, ?, ?)");
            $stmt->bind_param("isis", $this->uuid, $this->owner, $id, $items);
            $stmt->execute();
            $stmt->close();
            if($player->getName() === $this->owner) {
                $player->sendDelayedForm(new VaultListForm($player));
            }
            else {
                $vaults = Nexus::getInstance()->getPlayerManager()->getVaultManager()->getVaultsFor($this->owner);
                $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
                foreach($vaults as $id => $vault) {
                    $menu->getInventory()->setItem($id - 1, ItemFactory::getInstance()->get(ItemIds::CHEST, 0, 1)->setCustomName(TextFormat::RESET . TextFormat::YELLOW . "PV $id"));
                }
                $menu->setListener(InvMenu::readonly(function(DeterministicInvMenuTransaction $transaction) use($vaults): void {
                    $player = $transaction->getPlayer();
                    $itemClicked = $transaction->getItemClicked();
                    $number = $transaction->getAction()->getSlot() + 1;
                    if(!$player instanceof NexusPlayer) {
                        return;
                    }
                    if($itemClicked->getId() !== ItemIds::AIR) {
                        $vault = $vaults[$number];
                        $player->removeCurrentWindow();
                        $player->sendDelayedWindow($vault->getMenu());
                    }
                    return;
                }));
                // TODO: Figure out why vault spectating creates recursive calls
                //$menu->send($player);
            }
        });
    }

    /**
     * @return string
     */
    public function getOwner(): string {
        return $this->owner;
    }

    /**
     * @return InvMenu
     */
    public function getMenu(): InvMenu {
        return $this->menu;
    }

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }
}