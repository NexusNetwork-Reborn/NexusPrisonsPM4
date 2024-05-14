<?php
declare(strict_types=1);

namespace core\player\gang;

use core\Nexus;
use core\player\gang\task\UpdateGangsTask;
use core\player\NexusPlayer;
use libs\utils\ArrayUtils;
use libs\utils\Utils;
use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class GangManager {

    /** @var Nexus */
    private $core;

    /** @var Gang[] */
    private $gangs = [];

    /**
     * GangManager constructor.
     *
     * @param Nexus $core
     *
     * @throws GangException
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $this->init();
        $core->getServer()->getPluginManager()->registerEvents(new GangListener($core), $core);
        Nexus::getInstance()->getScheduler()->scheduleRepeatingTask(new UpdateGangsTask($this), 6000);
    }

    /**
     * @throws GangException
     */
    public function init(): void {
        $stmt = $this->core->getMySQLProvider()->getDatabase()->prepare("SELECT name, members, allies, enemies, value, balance, permissions, vault FROM gangs");
        $stmt->execute();
        $stmt->bind_result($name, $members, $allies, $enemies, $value, $balance, $permissions, $vault);
        while($stmt->fetch()) {
            $members = explode(",", $members);
            $allyList = [];
            if($allies !== null) {
                $allyList = explode(",", $allies);
            }
            $enemyList = [];
            if($enemies !== null) {
                $enemyList = explode(",", $enemies);
            }
            $inv = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
            $inv->setName(TextFormat::YELLOW . $name . "'s Vault");
            $inv->setListener(function(InvMenuTransaction $transaction): InvMenuTransactionResult {
                $player = $transaction->getPlayer();
                if(Nexus::getInstance()->getServerManager()->getAreaManager()->canDamage($player->getPosition())) {
                    $player->sendTranslatedMessage("inWarzone");
                    $player->playErrorSound();
                    return $transaction->discard();
                }
                return $transaction->continue();
            });
            $inv->setInventoryCloseListener(function(Player $player, InvMenuInventory $inventory) use ($name): void {
                $gang = $this->getGang($name);
                if($gang !== null) {
                    $gang->scheduleUpdate();
                }
            });
            if($vault !== null) {
                $items = Nexus::decodeInventory($vault);
                $i = 0;
                foreach($items as $item) {
                    $inv->getInventory()->setItem($i, $item);
                    ++$i;
                }
            }
            $gang = new Gang($name, $members, $allyList, $enemyList, $value, $balance, $inv, ArrayUtils::decodeArray($permissions));
            $this->gangs[strtolower($name)] = $gang;
        }
        $stmt->close();
    }

    /**
     * @return Gang[]
     */
    public function getGangs(): array {
        return $this->gangs;
    }

    /**
     * @param string $name
     *
     * @return Gang|null
     */
    public function getGang(string $name): ?Gang {
        return $this->gangs[strtolower($name)] ?? null;
    }

    /**
     * @param string $name
     * @param NexusPlayer $leader
     *
     * @throws GangException
     */
    public function createGang(string $name, NexusPlayer $leader): void {
        if(isset($this->gangs[strtolower($name)])) {
            throw new GangException("Unable to override an existing gang!");
        }
        $members = $leader->getName();
        $stmt = Nexus::getInstance()->getMySQLProvider()->getDatabase()->prepare("INSERT INTO gangs(name, members) VALUES(?, ?)");
        $stmt->bind_param("ss", $name, $members);
        $stmt->execute();
        $stmt->close();
        $inv = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
        $inv->setName(TextFormat::YELLOW . $name . "'s Vault");
        $inv->setInventoryCloseListener(function(Player $player, InvMenuInventory $inventory) use ($name): void {
            $gang = $this->getGang($name);
            if($gang !== null) {
                $gang->scheduleUpdate();
            }
        });
        $gang = new Gang($name, [$members], [], [], 0, 0, $inv);
        $gang->scheduleUpdate();
        $this->gangs[strtolower($name)] = $gang;
        $leader->getDataSession()->setGang($gang);
        $leader->getDataSession()->setGangRole(Gang::LEADER);
    }

    /**
     * @param string $name
     */
    public function removeGang(string $name): void {
        if(isset($this->gangs[strtolower($name)])) {
            unset($this->gangs[strtolower($name)]);
            $database = Nexus::getInstance()->getMySQLProvider()->getDatabase();
            $stmt = $database->prepare("DELETE FROM gangs WHERE name = ?");
            $stmt->bind_param("s", $name);
            $stmt->execute();
            $stmt->close();
        }
    }
}