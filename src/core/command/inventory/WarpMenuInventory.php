<?php

namespace core\command\inventory;

use core\command\task\TeleportTask;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\ItemManager;
use core\game\item\types\vanilla\Armor;
use core\game\kit\Kit;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rank\Rank;
use core\player\task\ExecutiveMineTask;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\permission\DefaultPermissions;
use pocketmine\utils\TextFormat;

class WarpMenuInventory extends InvMenu {

    public static $executiveSessions = [];

    /**
     * PlotMenuInventory constructor.
     *
     * @param NexusPlayer $player
     */
    public function __construct(NexusPlayer $player) {
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InvMenu::TYPE_CHEST));
        $this->initItems($player);
        $this->setName(TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . "Warps");
        $this->setListener(self::readonly(function(DeterministicInvMenuTransaction $transaction): void {
            $slot = $transaction->getAction()->getSlot();
            $player = $transaction->getPlayer();
            if(!$player instanceof NexusPlayer) {
                return;
            }
            if($slot > 0 and $slot < 8) {
                $mines = Nexus::getInstance()->getGameManager()->getZoneManager()->getMines();
                $mine = $mines[$slot - 1];
                if($mine->canAccess($player) or $player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
                    $player->removeCurrentWindow();
                    Nexus::getInstance()->getScheduler()->scheduleRepeatingTask(new TeleportTask($player, $mine->getPosition(), 10), 20);
                }
                else {
                    $player->playErrorSound();
                }
            }

            if($slot === 10) $region = Nexus::getInstance()->getGameManager()->getZoneManager()->getBadlandsByTier(Armor::TIER_CHAIN);
            if($slot === 12) $region = Nexus::getInstance()->getGameManager()->getZoneManager()->getBadlandsByTier(Armor::TIER_GOLD);
            if($slot === 14) $region = Nexus::getInstance()->getGameManager()->getZoneManager()->getBadlandsByTier(Armor::TIER_IRON);
            if($slot === 16) $region = Nexus::getInstance()->getGameManager()->getZoneManager()->getBadlandsByTier(Armor::TIER_DIAMOND);

            if(isset($region)) {
                if($region->canAccess($player) || $player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
                    $player->removeCurrentWindow();
                    Nexus::getInstance()->getScheduler()->scheduleRepeatingTask(new TeleportTask($player, $region->getSpawnPosition(), 10), 20);
                } else {
                    $player->playErrorSound();
                }
            }

            if($slot === 22) {
                $id = $player->getDataSession()->getRank()->getIdentifier();
                if($id === Rank::PRESIDENT or ($id >= Rank::MODERATOR and $id <= Rank::EXECUTIVE)) {
                    $lounge = Nexus::getInstance()->getServer()->getWorldManager()->getWorldByName("lounge");
                    $player->removeCurrentWindow();
                    Nexus::getInstance()->getScheduler()->scheduleRepeatingTask(new TeleportTask($player, $lounge->getSpawnLocation(), 10), 20);
                }
                else {
                    $player->playErrorSound();
                }
            }
            if($slot === 25) {
                $mines = Nexus::getInstance()->getGameManager()->getZoneManager()->getMines();
                $mine = $mines[count($mines)-1];
                if($mine->canAccess($player) && $player->getDataSession()->canEnterExecutiveMine()) {
                    $player->removeCurrentWindow();
                    self::$executiveSessions[$player->getXuid()] = new ExecutiveMineTask(Nexus::getInstance(), $player);
                    Nexus::getInstance()->getScheduler()->scheduleRepeatingTask(self::$executiveSessions[$player->getXuid()], 20);
                    $player->teleport($mine->getPosition());
                    //Nexus::getInstance()->getScheduler()->scheduleRepeatingTask(new TeleportTask($player, $mine->getPosition(), 10), 20);
                } elseif(isset(WarpMenuInventory::$executiveSessions[$player->getXuid()])) {
                    $player->teleport($mine->getPosition());
                }
                else {
                    $player->playErrorSound();
                }
            }
            return;
        }));
    }

    public static function updateExecutiveSession(NexusPlayer $player): void {
        if(isset(self::$executiveSessions[$player->getXuid()])) {
            self::$executiveSessions[$player->getXuid()]->addBlock();
        }
    }

    public static function getExecutiveSession(NexusPlayer $player): ?ExecutiveMineTask {
        if(isset(self::$executiveSessions[$player->getXuid()])) {
            return self::$executiveSessions[$player->getXuid()];
        }
        return null;
    }

    /**
     * @param NexusPlayer $player
     * @param Kit $kit
     */
    public function initItems(NexusPlayer $player): void {
        $glass = ItemFactory::getInstance()->get(ItemIds::STAINED_GLASS_PANE, 15, 1);
        $glass->setCustomName(" ");
        $mines = Nexus::getInstance()->getGameManager()->getZoneManager()->getMines();
        $badlands = Nexus::getInstance()->getGameManager()->getZoneManager()->getBadlands();
        for($i = 0; $i < 27; $i++) {
            if($i > 0 and $i < 8) {
                if(!empty($mines)) {
                    $mine = array_shift($mines);
                    if($mine->canAccess($player)) {
                        $item = $mine->getItem()->setCount(1);
                        $lore = [];
                        $lore[] = "";
                        $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::GREEN . "UNLOCKED";
                    }
                    else {
                        $item = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::RED())->asItem()->setCount(1);
                        $lore = [];
                        $lore[] = "";
                        $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "LOCKED";
                    }
                    $color = ItemManager::getColorByOre($mine->getItem()->getBlock());
                    $item->setCustomName(TextFormat::RESET . TextFormat::BOLD . $color . $mine->getName() . " Mine");
                    $lore[] = "";
                    $lore[] = TextFormat::RESET . TextFormat::BOLD . $color . "Ore: " . TextFormat::WHITE . $mine->getName();
                    $location = $mine->getPosition()->floor();
                    $x = $location->getX();
                    $y = $location->getY();
                    $z = $location->getZ();
                    if($mine->canAccess($player)) {
                        $lore[] = TextFormat::RESET . TextFormat::BOLD . $color . "Location: " . TextFormat::RESET . TextFormat::WHITE . $x . TextFormat::GRAY . "x, " . TextFormat::WHITE . $y . TextFormat::GRAY . "y, " . TextFormat::WHITE . $z . TextFormat::GRAY . "z";
                        $lore[] = TextFormat::RESET . TextFormat::BOLD . $color . "Required Level: " . TextFormat::WHITE . $mine->getLevel() . " or Prestige " . TextFormat::AQUA . "<" . TextFormat::LIGHT_PURPLE . "I" . TextFormat::AQUA . ">";
                        $lore[] = "";
                        $lore[] = TextFormat::RESET . TextFormat::GRAY . "(Click to teleport)";
                    }
                    else {
                        $lore[] = TextFormat::RESET . TextFormat::BOLD . $color . "Location: " . TextFormat::RESET . TextFormat::RED . "(locked)";
                        $lore[] = TextFormat::RESET . TextFormat::BOLD . $color . "Required Level: " . TextFormat::WHITE . $mine->getLevel() . " or Prestige " . TextFormat::AQUA . "<" . TextFormat::LIGHT_PURPLE . "I" . TextFormat::AQUA . ">";
                        $lore[] = "";
                        $lore[] = TextFormat::RESET . TextFormat::RED . "Reach the required level to teleport!";
                    }
                    $item->setLore($lore);
                    $this->getInventory()->setItem($i, $item);
                    continue;
                }
            }

            if(!empty($badlands)) {
                if($i === 10) {
                    $region = Nexus::getInstance()->getGameManager()->getZoneManager()->getBadlandsByTier(Armor::TIER_CHAIN);

                    // readme: hardcode bypass
                    if(1 != 2 /*$region->canAccess($player)*/) {
                        $item = VanillaItems::STONE_HOE();
                        $item->setLore([
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::GREEN . "UNLOCKED",
                            "",
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . "Ore Bandits: " . $this->getBadlandsZoneInfo(Armor::TIER_CHAIN)["oreBandits"],
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . "Location: " . TextFormat::RESET . TextFormat::WHITE . $this->getBadlandsZoneInfo(Armor::TIER_CHAIN)["location"]["x"] . TextFormat::DARK_GRAY . "x, " . TextFormat::WHITE . $this->getBadlandsZoneInfo(Armor::TIER_CHAIN)["location"]["y"] . TextFormat::DARK_GRAY . "y, " . TextFormat::WHITE . $this->getBadlandsZoneInfo(Armor::TIER_CHAIN)["location"]["z"] . TextFormat::DARK_GRAY . "z",
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . "Required Level: " . $this->getBadlandsZoneInfo(Armor::TIER_CHAIN)["required"],
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . "Danger: " . $this->getBadlandsZoneInfo(Armor::TIER_CHAIN)["difficulty"],
                            "",
                            TextFormat::RESET . TextFormat::GRAY . "(Click to teleport)"
                        ]);
                    } else {
                        $item = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::RED())->asItem()->setCount(1);
                        $item->setLore([
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "LOCKED",
                            "",
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . "Ore Bandits: " . $this->getBadlandsZoneInfo(Armor::TIER_CHAIN)["oreBandits"],
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . "Location: " . TextFormat::RESET . TextFormat::WHITE . $this->getBadlandsZoneInfo(Armor::TIER_CHAIN)["location"]["x"] . TextFormat::DARK_GRAY . "x, " . TextFormat::WHITE . $this->getBadlandsZoneInfo(Armor::TIER_CHAIN)["location"]["y"] . TextFormat::DARK_GRAY . "y, " . TextFormat::WHITE . $this->getBadlandsZoneInfo(Armor::TIER_CHAIN)["location"]["z"] . TextFormat::DARK_GRAY . "z",
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . "Required Level: " . $this->getBadlandsZoneInfo(Armor::TIER_CHAIN)["required"],
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . "Danger: " . $this->getBadlandsZoneInfo(Armor::TIER_CHAIN)["difficulty"],
                            "",
                            TextFormat::RESET . TextFormat::GRAY . "(Level up to teleport)"
                        ]);
                    }
                    $item->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Badlands: " . TextFormat::GOLD . "Chain");
                    $this->getInventory()->setItem($i, $item);
                    continue;
                }

                if($i === 12) {
                    $region = Nexus::getInstance()->getGameManager()->getZoneManager()->getBadlandsByTier(Armor::TIER_GOLD);
                    // readme: hardcode bypass
                    if(1 != 2 /*$region->canAccess($player)*/) {
                        $item = VanillaItems::GOLDEN_HOE();
                        $item->setLore([
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::GREEN . "UNLOCKED",
                            "",
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::YELLOW . "Ore Bandits: " . $this->getBadlandsZoneInfo(Armor::TIER_GOLD)["oreBandits"],
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::YELLOW . "Location: " . TextFormat::RESET . TextFormat::WHITE . $this->getBadlandsZoneInfo(Armor::TIER_GOLD)["location"]["x"] . TextFormat::DARK_GRAY . "x, " . TextFormat::WHITE . $this->getBadlandsZoneInfo(Armor::TIER_GOLD)["location"]["y"] . TextFormat::DARK_GRAY . "y, " . TextFormat::WHITE . $this->getBadlandsZoneInfo(Armor::TIER_GOLD)["location"]["z"] . TextFormat::DARK_GRAY . "z",
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::YELLOW . "Required Level: " . $this->getBadlandsZoneInfo(Armor::TIER_GOLD)["required"],
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::YELLOW . "Danger: " . $this->getBadlandsZoneInfo(Armor::TIER_GOLD)["difficulty"],
                            "",
                            TextFormat::RESET . TextFormat::GRAY . "(Click to teleport)"
                        ]);
                    } else {
                        $item = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::RED())->asItem()->setCount(1);
                        $item->setLore([
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "LOCKED",
                            "",
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::YELLOW . "Ore Bandits: " . $this->getBadlandsZoneInfo(Armor::TIER_GOLD)["oreBandits"],
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::YELLOW . "Location: " . TextFormat::RESET . TextFormat::WHITE . $this->getBadlandsZoneInfo(Armor::TIER_GOLD)["location"]["x"] . TextFormat::DARK_GRAY . "x, " . TextFormat::WHITE . $this->getBadlandsZoneInfo(Armor::TIER_GOLD)["location"]["y"] . TextFormat::DARK_GRAY . "y, " . TextFormat::WHITE . $this->getBadlandsZoneInfo(Armor::TIER_GOLD)["location"]["z"] . TextFormat::DARK_GRAY . "z",
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::YELLOW . "Required Level: " . $this->getBadlandsZoneInfo(Armor::TIER_GOLD)["required"],
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::YELLOW . "Danger: " . $this->getBadlandsZoneInfo(Armor::TIER_GOLD)["difficulty"],
                            "",
                            TextFormat::RESET . TextFormat::GRAY . "(Level up to teleport)"
                        ]);
                    }
                    $item->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Badlands: " . TextFormat::YELLOW . "Gold");
                    $this->getInventory()->setItem($i, $item);
                    continue;
                }

                if($i === 14) {
                    $region = Nexus::getInstance()->getGameManager()->getZoneManager()->getBadlandsByTier(Armor::TIER_IRON);
                    // readme: hardcode bypass
                    if(1 != 2 /*$region->canAccess($player)*/) {
                        $item = VanillaItems::IRON_HOE();
                        $item->setLore([
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::GREEN . "UNLOCKED",
                            "",
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::GRAY . "Ore Bandits: " . $this->getBadlandsZoneInfo(Armor::TIER_IRON)["oreBandits"],
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::GRAY . "Location: " . TextFormat::RESET . TextFormat::WHITE . $this->getBadlandsZoneInfo(Armor::TIER_IRON)["location"]["x"] . TextFormat::DARK_GRAY . "x, " . TextFormat::WHITE . $this->getBadlandsZoneInfo(Armor::TIER_IRON)["location"]["y"] . TextFormat::DARK_GRAY . "y, " . TextFormat::WHITE . $this->getBadlandsZoneInfo(Armor::TIER_IRON)["location"]["z"] . TextFormat::DARK_GRAY . "z",
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::GRAY . "Required Level: " . $this->getBadlandsZoneInfo(Armor::TIER_IRON)["required"],
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::GRAY . "Danger: " . $this->getBadlandsZoneInfo(Armor::TIER_IRON)["difficulty"],
                            "",
                            TextFormat::RESET . TextFormat::GRAY . "(Click to teleport)"
                        ]);
                    } else {
                        $item = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::RED())->asItem()->setCount(1);
                        $item->setLore([
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "LOCKED",
                            "",
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::GRAY . "Ore Bandits: " . $this->getBadlandsZoneInfo(Armor::TIER_IRON)["oreBandits"],
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::GRAY . "Location: " . TextFormat::RESET . TextFormat::WHITE . $this->getBadlandsZoneInfo(Armor::TIER_IRON)["location"]["x"] . TextFormat::DARK_GRAY . "x, " . TextFormat::WHITE . $this->getBadlandsZoneInfo(Armor::TIER_IRON)["location"]["y"] . TextFormat::DARK_GRAY . "y, " . TextFormat::WHITE . $this->getBadlandsZoneInfo(Armor::TIER_IRON)["location"]["z"] . TextFormat::DARK_GRAY . "z",
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::GRAY . "Required Level: " . $this->getBadlandsZoneInfo(Armor::TIER_IRON)["required"],
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::GRAY . "Danger: " . $this->getBadlandsZoneInfo(Armor::TIER_IRON)["difficulty"],
                            "",
                            TextFormat::RESET . TextFormat::GRAY . "(Level up to teleport)"
                        ]);
                    }
                    $item->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Badlands: " . TextFormat::GRAY . "Iron");
                    $this->getInventory()->setItem($i, $item);
                    continue;
                }


                if($i === 16) {
                    $region = Nexus::getInstance()->getGameManager()->getZoneManager()->getBadlandsByTier(Armor::TIER_DIAMOND);
                    // readme: hardcode bypass
                    if(1 != 2 /*$region->canAccess($player)*/) {
                        $item = VanillaItems::DIAMOND_HOE();
                        $item->setLore([
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::GREEN . "UNLOCKED",
                            "",
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Ore Bandits: " . $this->getBadlandsZoneInfo(Armor::TIER_DIAMOND)["oreBandits"],
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Location: " . TextFormat::RESET . TextFormat::WHITE . $this->getBadlandsZoneInfo(Armor::TIER_DIAMOND)["location"]["x"] . TextFormat::DARK_GRAY . "x, " . TextFormat::WHITE . $this->getBadlandsZoneInfo(Armor::TIER_DIAMOND)["location"]["y"] . TextFormat::DARK_GRAY . "y, " . TextFormat::WHITE . $this->getBadlandsZoneInfo(Armor::TIER_DIAMOND)["location"]["z"] . TextFormat::DARK_GRAY . "z",
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Required Level: " . $this->getBadlandsZoneInfo(Armor::TIER_DIAMOND)["required"],
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Danger: " . $this->getBadlandsZoneInfo(Armor::TIER_DIAMOND)["difficulty"],
                            "",
                            TextFormat::RESET . TextFormat::GRAY . "(Click to teleport)"
                        ]);
                    } else {
                        $item = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::RED())->asItem()->setCount(1);
                        $item->setLore([
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "LOCKED",
                            "",
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Ore Bandits: " . $this->getBadlandsZoneInfo(Armor::TIER_DIAMOND)["oreBandits"],
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Location: " . TextFormat::RESET . TextFormat::WHITE . $this->getBadlandsZoneInfo(Armor::TIER_DIAMOND)["location"]["x"] . TextFormat::DARK_GRAY . "x, " . TextFormat::WHITE . $this->getBadlandsZoneInfo(Armor::TIER_DIAMOND)["location"]["y"] . TextFormat::DARK_GRAY . "y, " . TextFormat::WHITE . $this->getBadlandsZoneInfo(Armor::TIER_DIAMOND)["location"]["z"] . TextFormat::DARK_GRAY . "z",
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Required Level: " . $this->getBadlandsZoneInfo(Armor::TIER_DIAMOND)["required"],
                            TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Danger: " . $this->getBadlandsZoneInfo(Armor::TIER_DIAMOND)["difficulty"],
                            "",
                            TextFormat::RESET . TextFormat::GRAY . "(Level up to teleport)"
                        ]);
                    }
                    $item->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Badlands: " . TextFormat::AQUA . "Diamond");
                    $this->getInventory()->setItem($i, $item);
                    continue;
                }

            }

            if($i === 22) {
                $item = VanillaBlocks::DRAGON_EGG()->asItem()->setCount(1);
                $item->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Presidential Lounge");
                $lore = [];
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "Available exclusively to players";
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "with the " . TextFormat::BOLD . TextFormat::RED . "President" . TextFormat::RESET . TextFormat::GRAY . " global rank!";
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::RED . "Access to the following perks within";
                $lore[] = TextFormat::RESET . TextFormat::DARK_RED . TextFormat::BOLD . " * " . TextFormat::RED . "No /cf" . TextFormat::RESET . TextFormat::WHITE . " Energy Tax";
                $lore[] = TextFormat::RESET . TextFormat::DARK_RED . TextFormat::BOLD . " * " . TextFormat::RED . "-2.5%" . TextFormat::RESET . TextFormat::WHITE . " /xpextract";
                $item->setLore($lore);
                $this->getInventory()->setItem($i, $item);
                continue;
            }
            if($i === 25) {
                $color = TextFormat::AQUA;
                $executive = $mines[count($mines)-1];
                if($executive->canAccess($player)) {
                    $item = $executive->getItem()->setCount(1);
                    $lore = [];
                    $lore[] = "";
                    $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::GREEN . "UNLOCKED";
                }
                else {
                    $item = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::RED())->asItem()->setCount(1);
                    $lore = [];
                    $lore[] = "";
                    $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "LOCKED";
                }
                $item->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Executive Mine");
                $lore[] = "";
                if($executive->canAccess($player)) {
                    $lore[] = TextFormat::RESET . TextFormat::BOLD . $color . "Time Left: " . TextFormat::RESET . TextFormat::WHITE . (int)($player->getDataSession()->getFancyExecutiveTime($player->getXuid()) / 60) . "m";
                    $lore[] = TextFormat::RESET . TextFormat::BOLD . $color . "Required Level: " . TextFormat::WHITE . $executive->getLevel() . " or Prestige " . TextFormat::AQUA . "<" . TextFormat::LIGHT_PURPLE . "I" . TextFormat::AQUA . ">";
                    $lore[] = "";
                    $lore[] = TextFormat::RESET . TextFormat::GRAY . "(Click to teleport)";
                } else {
                    $lore[] = TextFormat::RESET . TextFormat::BOLD . $color . "Required Level: " . TextFormat::WHITE . $executive->getLevel() . " or Prestige " . TextFormat::AQUA . "<" . TextFormat::LIGHT_PURPLE . "I" . TextFormat::AQUA . ">";
                    $lore[] = "";
                    $lore[] = TextFormat::RESET . TextFormat::RED . "Reach the required level to teleport!";
                }
                $lore[] = TextFormat::RESET . TextFormat::AQUA . "Access to the following perks within";
                $lore[] = TextFormat::RESET . TextFormat::DARK_AQUA . TextFormat::BOLD . " * " . TextFormat::AQUA . "Executive Wormhole" /*. TextFormat::RESET . TextFormat::WHITE . " Energy Tax"*/;
                $item->setLore($lore);
                $this->getInventory()->setItem($i, $item);
                continue;
            }
            $this->getInventory()->setItem($i, $glass);
        }
    }

    /**
     * @param int $id
     * @return array
     */
    public function getBadlandsZoneInfo(int $id) : array
    {
        $info = [];
        $region = Nexus::getInstance()->getGameManager()->getZoneManager()->getBadlandsByTier($id);

        switch ($id) {
            case Armor::TIER_CHAIN:
                $info["difficulty"] = str_repeat(TextFormat::RED . "|", 7) . str_repeat(TextFormat::GRAY . "|", 21);
                $info["required"] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . 1 . " or Prestige " . TextFormat::LIGHT_PURPLE . "<" . TextFormat::AQUA . EnchantmentManager::getRomanNumber(1) . TextFormat::LIGHT_PURPLE . ">";
                $info["location"] = ["x" => $region->getSpawnPosition()->getFloorX(), "y" => $region->getSpawnPosition()->getFloorY(), "z" => $region->getSpawnPosition()->getFloorZ()];
                $info["oreBandits"] = TextFormat::BOLD . TextFormat::WHITE . "Coal, Iron";

                return $info;
            break;

            case Armor::TIER_GOLD:
                $info["difficulty"] = str_repeat(TextFormat::RED . "|", 14) . str_repeat(TextFormat::GRAY . "|", 14);
                $info["required"] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . 30 . " or Prestige " . TextFormat::LIGHT_PURPLE . "<" . TextFormat::AQUA . EnchantmentManager::getRomanNumber(1) . TextFormat::LIGHT_PURPLE . ">";
                $info["location"] = ["x" => $region->getSpawnPosition()->getFloorX(), "y" => $region->getSpawnPosition()->getFloorY(), "z" => $region->getSpawnPosition()->getFloorZ()];
                $info["oreBandits"] = TextFormat::BOLD . TextFormat::WHITE . "Lapis, Redstone";

                return $info;
            break;

            case Armor::TIER_IRON:
                $info["difficulty"] = str_repeat(TextFormat::RED . "|", 21) . str_repeat(TextFormat::GRAY . "|", 7);
                $info["required"] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . 50 . " or Prestige " . TextFormat::LIGHT_PURPLE . "<" . TextFormat::AQUA . EnchantmentManager::getRomanNumber(1) . TextFormat::LIGHT_PURPLE . ">";
                $info["location"] = ["x" => $region->getSpawnPosition()->getFloorX(), "y" => $region->getSpawnPosition()->getFloorY(), "z" => $region->getSpawnPosition()->getFloorZ()];
                $info["oreBandits"] = TextFormat::BOLD . TextFormat::WHITE . "Redstone, Gold";

                return $info;
            break;

            default:
                $info["difficulty"] = str_repeat(TextFormat::RED . "|", 28);
                $info["required"] = TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . 90 . " or Prestige " . TextFormat::LIGHT_PURPLE . "<" . TextFormat::AQUA . EnchantmentManager::getRomanNumber(1) . TextFormat::LIGHT_PURPLE . ">";
                $info["location"] = ["x" => $region->getSpawnPosition()->getFloorX(), "y" => $region->getSpawnPosition()->getFloorY(), "z" => $region->getSpawnPosition()->getFloorZ()];
                $info["oreBandits"] = TextFormat::BOLD . TextFormat::WHITE . "Diamond, Emerald";

                return $info;
            break;
        }
    }
}