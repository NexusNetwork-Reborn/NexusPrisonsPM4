<?php

declare(strict_types=1);

namespace core\game\badlands;

use core\game\badlands\bandit\BanditBoss;
use core\game\badlands\bandit\BaseBandit;
use core\game\badlands\bandit\EliteBandit;
use core\game\badlands\bandit\XPBandit;
use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentIdentifiers;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\slotbot\SlotBotTicket;
use core\game\item\types\custom\Contraband;
use core\game\item\types\custom\EnchantmentPage;
use core\game\item\types\custom\GKitFlare;
use core\game\item\types\custom\Lootbox;
use core\game\item\types\custom\MoneyNote;
use core\game\item\types\custom\Satchel;
use core\game\item\types\vanilla\Armor;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rank\Rank;
use core\provider\event\PlayerLoadEvent;
use core\game\item\types\custom\Shard;
use core\game\item\types\Rarity;
use core\game\item\types\vanilla\Pickaxe;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\EnchantmentIds;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\permission\DefaultPermissions;
use pocketmine\scheduler\ClosureTask;
use pocketmine\nbt\tag\CompoundTag;

class BadlandsListener implements Listener {

    /** @var array */
    private static array $deathBanTimes = [];

    /**
     * @param BlockBreakEvent $event
     */
    public function onBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();

        if (!$player instanceof NexusPlayer) return;
        if ($player->getWorld()->getFolderName() !== "badlands" && $player->getWorld()->getFolderName() !== "boss") return;

        if (!$player->hasPermission(DefaultPermissions::ROOT_OPERATOR) && $player->getDataSession()->getRank()->getIdentifier() < Rank::MODERATOR) {
            $event->cancel();
            $player->playErrorSound();
        }
    }

    /**
     * @param BlockPlaceEvent $event
     */
    public function onPlace(BlockPlaceEvent $event): void {
        $player = $event->getPlayer();

        if (!$player instanceof NexusPlayer) return;
        if ($player->getWorld()->getFolderName() !== "badlands" && $player->getWorld()->getFolderName() !== "boss") return;

        if (!$player->hasPermission(DefaultPermissions::ROOT_OPERATOR) && $player->getDataSession()->getRank()->getIdentifier() < Rank::MODERATOR) {
            $event->cancel();
            $player->playErrorSound();
        }
    }

    /**
     * @param EntityDamageByEntityEvent $event
     */
    public function onDamage(EntityDamageByEntityEvent $event): void {
        $damager = $event->getDamager();
        $victim = $event->getEntity();

        if ($damager->getWorld()->getFolderName() !== "badlands") return;

        if ($damager instanceof NexusPlayer && $victim instanceof NexusPlayer) {
            $event->cancel();
        }
    }

    /**
     * @param PlayerDeathEvent $event
     */
    public function onDeath(PlayerDeathEvent $event): void {
        $player = $event->getPlayer();

        if (!$player instanceof NexusPlayer) return;
        if ($player->getWorld()->getFolderName() !== "badlands") return;

        $id = $player->getUniqueId()->toString();
        $event->setKeepInventory(true);
        $player->combatTag(false);

        self::$deathBanTimes[$player->getUniqueId()->toString()] = time() + 300;

        Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($id): void {
            if (isset(self::$deathBanTimes[$id])) unset(self::$deathBanTimes[$id]);
        }), 300 * 20);
    }

    /**
     * @throws \core\translation\TranslationException
     * @throws \core\level\LevelException
     * @throws \core\display\animation\AnimationException
     */
    public function onEntityDeath(EntityDeathEvent $event): void {
        $entity = $event->getEntity();
        $cause = $entity->getLastDamageCause();

        if ($entity instanceof XPBandit || $entity instanceof EliteBandit) {
            if (mt_rand(1, 100) <= 10) {
                $loco = $entity->getLocation();
                $boss = new BanditBoss($loco, CompoundTag::create());
                $boss->region = $entity->region;

                $boss->spawnToAll();
            }

            if ($entity->region === null) return;

            $drops = match ($entity->region->getTier()) {
                Armor::TIER_CHAIN => [
                    (new MoneyNote(mt_rand(1000, 15000)))->toItem(),
                    (new MoneyNote(mt_rand(2000, 10000)))->toItem(),
                    (new EnchantmentPage(Enchantment::SIMPLE, 10, 5))->toItem(),
                    (new EnchantmentPage(Enchantment::SIMPLE, 10, 5))->toItem(),
                    (new EnchantmentPage(Enchantment::SIMPLE, 10, 5))->toItem(),
                    (new EnchantmentPage(Enchantment::UNCOMMON, 10, 5))->toItem(),
                    (new EnchantmentPage(Enchantment::UNCOMMON, 10, 5))->toItem(),
                    (new EnchantmentPage(Enchantment::ELITE, 10, 5))->toItem()
                ],
                Armor::TIER_GOLD => [
                    (new MoneyNote(mt_rand(5000, 25000)))->toItem(),
                    (new MoneyNote(mt_rand(10000, 20000)))->toItem(),
                    (new EnchantmentPage(Enchantment::SIMPLE, 15, 10))->toItem(),
                    (new EnchantmentPage(Enchantment::SIMPLE, 15, 10))->toItem(),
                    (new EnchantmentPage(Enchantment::UNCOMMON, 12, 8))->toItem(),
                    (new EnchantmentPage(Enchantment::UNCOMMON, 12, 8))->toItem(),
                    (new EnchantmentPage(Enchantment::ELITE, 11, 6))->toItem(),
                    (new EnchantmentPage(Enchantment::LEGENDARY, 8, 4))->toItem()
                ],
                Armor::TIER_IRON => [
                    (new MoneyNote(mt_rand(15000, 50000)))->toItem(),
                    (new MoneyNote(mt_rand(25000, 45000)))->toItem(),
                    (new EnchantmentPage(Enchantment::SIMPLE, 20, 15))->toItem(),
                    (new EnchantmentPage(Enchantment::UNCOMMON, 15, 10))->toItem(),
                    (new EnchantmentPage(Enchantment::UNCOMMON, 15, 10))->toItem(),
                    (new EnchantmentPage(Enchantment::ELITE, 13, 9))->toItem(),
                    (new EnchantmentPage(Enchantment::ELITE, 13, 9))->toItem(),
                    (new EnchantmentPage(Enchantment::LEGENDARY, 10, 5))->toItem()
                ],
                default => [
                    (new MoneyNote(mt_rand(25000, 100000)))->toItem(),
                    (new MoneyNote(mt_rand(45000, 80000)))->toItem(),
                    (new EnchantmentPage(Enchantment::UNCOMMON, 25, 15))->toItem(),
                    (new EnchantmentPage(Enchantment::ELITE, 18, 14))->toItem(),
                    (new EnchantmentPage(Enchantment::ELITE, 18, 14))->toItem(),
                    (new EnchantmentPage(Enchantment::LEGENDARY, 15, 10))->toItem(),
                    (new EnchantmentPage(Enchantment::LEGENDARY, 15, 10))->toItem(),
                    (new EnchantmentPage(Enchantment::GODLY, 10, 5))->toItem(),
                    (new EnchantmentPage(Enchantment::EXECUTIVE, 5, 2))->toItem()
                ],
            };
            $rand = $drops[array_rand($drops)];
            $event->setDrops([$rand]);
        }

        if ($entity instanceof BanditBoss) {
            if ($entity->region === null) return;

            $drops = match ($entity->region->getTier()) {
                Armor::TIER_CHAIN => [
                    (new Shard(Rarity::SIMPLE))->toItem(),
                    (new Contraband(Rarity::SIMPLE))->toItem(),
                    (new Satchel(VanillaBlocks::COAL_ORE()->asItem(), 1))->toItem(),
                ],
                Armor::TIER_GOLD => [
                    (new Shard(Rarity::UNCOMMON))->toItem(),
                    (new Contraband(Rarity::UNCOMMON))->toItem(),
                    (new GKitFlare(Nexus::getInstance()->getGameManager()->getKitManager()->getKitByName("Ares")))->toItem(),
                    (new Satchel(VanillaBlocks::GOLD_ORE()->asItem(), 1))->toItem(),
                ],
                Armor::TIER_IRON => [
                    (new Shard(Rarity::ELITE))->toItem(),
                    (new Contraband(Rarity::ELITE))->toItem(),
                    (new GKitFlare(Nexus::getInstance()->getGameManager()->getKitManager()->getKitByName(["Ares", "Grim Reaper"][mt_rand(0, 1)])))->toItem(),
                    (new Satchel(VanillaBlocks::IRON_ORE()->asItem(), 1))->toItem(),
                ],
                default => [
                    (new Shard(Rarity::LEGENDARY))->toItem(),
                    (new Contraband(Rarity::LEGENDARY))->toItem(),
                    (new GKitFlare(Nexus::getInstance()->getGameManager()->getKitManager()->getKitByName("Heroic " . ["Atheos", "Broteas", "Colossus", "Enchanter", "Iapetus", "Slaughter", "Vulkarion", "Warlock", "Zenith"][mt_rand(0, 8)])))->toItem(),
                    (new Satchel(VanillaBlocks::DIAMOND_ORE()->asItem(), 1))->toItem(),
                    (new SlotBotTicket("Normal"))->toItem()->setCount(mt_rand(1, 2))
                ],
            };
            $rand = $drops[array_rand($drops)];
            $event->setDrops([$rand]);
        }

        if ($cause instanceof EntityDamageByEntityEvent && ($entity instanceof BanditBoss || $entity instanceof XPBandit || $entity instanceof EliteBandit)) {
            $damager = $cause->getDamager();
            $money = 100;
            if ($damager instanceof NexusPlayer) {
                foreach ($damager->getArmorInventory()->getContents() as $content) {
                    if ($content->hasEnchantment(EnchantmentManager::getEnchantment(EnchantmentIdentifiers::BLOOD_MONEY))) {
                        $level = 1 + $content->getEnchantmentLevel(EnchantmentManager::getEnchantment(EnchantmentIdentifiers::BLOOD_MONEY));
                        break;
                    }


                    if (isset($level)) {
                        $damager->getDataSession()->addToBalance(ceil($entity->attackDamage * 10 * $money * $level));
                    } else {
                        $damager->getDataSession()->addToBalance(ceil($entity->attackDamage * 10 * $money));
                    }

                    foreach ($damager->getArmorInventory()->getContents() as $a) {
                        if ($a->hasEnchantment(EnchantmentManager::getEnchantment(EnchantmentIdentifiers::LEECH))) {
                            $level2 = 1 + $content->getEnchantmentLevel(EnchantmentManager::getEnchantment(EnchantmentIdentifiers::LEECH));
                            break;
                        }
                    }

                    if (isset($level2)) {
                        foreach ($damager->getInventory()->getContents() as $pick) {
                            if ($pick instanceof Pickaxe) {
                                $pick->addEnergy((int)ceil($level2 * $entity->attackDamage), $damager);
                            }
                        }
                    } else {
                        foreach ($damager->getInventory()->getContents() as $pick) {
                            if ($pick instanceof Pickaxe) {
                                $pick->addEnergy((int)ceil($entity->attackDamage), $damager);
                            }
                        }
                    }

                    if ($damager->getInventory()->getItemInHand()->hasEnchantment(EnchantmentManager::getEnchantment(EnchantmentIdentifiers::BOUNTY_HUNTER))) {
                        $level3 = 1 + $content->getEnchantmentLevel(EnchantmentManager::getEnchantment(EnchantmentIdentifiers::BOUNTY_HUNTER));
                    }

                    $base = 10;

                    if (isset($level3)) {
                        $damager->getDataSession()->addToXP((int)ceil($base * $level3));
                    } else {
                        $damager->getDataSession()->addToXP($base);
                    }
                }
            }
        }
    }

    public function debugSpawning(EntitySpawnEvent $event): void {
        $entity = $event->getEntity();
        if ($entity instanceof BaseBandit){
            $entity->despawnFromAll();
            $entity->flagForDespawn();
        }
    }
}
