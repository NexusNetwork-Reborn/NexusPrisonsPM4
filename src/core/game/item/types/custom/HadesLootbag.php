<?php

declare(strict_types=1);

namespace core\game\item\types\custom;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\ItemManager;
use core\game\item\types\customies\ItemSkinScroll;
use core\game\item\types\Interactive;
use core\game\item\types\vanilla\Armor;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rank\Rank;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\Inventory;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class HadesLootbag extends Interactive
{

    const HADES_LOOTBAG = "HadesLootbag";

    /**
     * SlaughterLootbag constructor.
     *
     * @param string|null $uuid
     */
    public function __construct(?string $uuid = null)
    {
        $customName = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "Hades Lootbag";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::RED . "Contains lots of goodies!";
        $lore[] = TextFormat::RESET . TextFormat::RED . "This was obtained from the Hades boss!";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hint: Right-click to open!";
        parent::__construct(VanillaItems::CHARCOAL(), $customName, $lore, true, true, $uuid);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array
    {
        return [
            self::HADES_LOOTBAG => StringTag::class
        ];
    }

    /**
     * @param Item $item
     *
     * @return $this
     */
    public static function fromItem(Item $item): self
    {
        $tag = self::getCustomTag($item);
        $uuid = $tag->getString(self::UUID);
        return new self($uuid);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag
    {
        $tag = new CompoundTag();
        $tag->setString(self::HADES_LOOTBAG, self::HADES_LOOTBAG);
        $tag->setString(self::UUID, $this->getUniqueId());
        return $tag;
    }

    /**
     * @param NexusPlayer $player
     * @param Inventory $inventory
     * @param Item $item
     */
    public function execute(NexusPlayer $player, Inventory $inventory, Item $item): void
    {
        if (mt_rand(1, 500) == 1) {
            $types = [
                Rank::NOBLE,
                Rank::IMPERIAL,
                Rank::SUPREME
            ];
            $item = (new RankNote(Nexus::getInstance()->getPlayerManager()->getRankManager()->getRankByIdentifier($types[array_rand($types)])))->toItem();
        } else {
            switch (mt_rand(1, 5)) {
                case 1:
                    $item = ItemManager::getSkinScroll(ItemSkinScroll::UNCOMMON[array_rand(ItemSkinScroll::UNCOMMON)])->toItem()->setCount(1);
                    if(mt_rand(1, 3) === 1) {
                        $item = ItemManager::getSkinScroll(ItemSkinScroll::ELITE[array_rand(ItemSkinScroll::ELITE)])->toItem()->setCount(1);
                    }
                    break;
                case 2:
                    $item = $this->getHadesHelm($player)->setCount(1);
                    break;
                case 3:
                    $types = [
                        VanillaItems::COAL(),
                        VanillaItems::IRON_INGOT(),
                        VanillaItems::LAPIS_LAZULI(),
                        VanillaItems::REDSTONE_DUST(),
                        VanillaBlocks::COAL_ORE()->asItem(),
                        VanillaBlocks::IRON_ORE()->asItem(),
                        VanillaBlocks::LAPIS_LAZULI_ORE()->asItem(),
                        VanillaBlocks::REDSTONE_ORE()->asItem()
                    ];
                    $item = (new Satchel($types[array_rand($types)]))->toItem();
                    break;
                case 4:
                    $enchantment = EnchantmentManager::getRandomMiningEnchantment(Enchantment::LEGENDARY);
                    $max = $enchantment->getMaxLevel() > 1 ? $enchantment->getMaxLevel() - 1 : 1;
                    $item = (new EnchantmentOrb(new EnchantmentInstance($enchantment, mt_rand(1, $max)), mt_rand(1, 100)))->toItem();
                    break;
                case 5:
                    $enchantment = EnchantmentManager::getRandomFightingEnchantment(Enchantment::LEGENDARY);
                    $max = $enchantment->getMaxLevel() > 1 ? $enchantment->getMaxLevel() - 1 : 1;
                    $item = (new EnchantmentBook(new EnchantmentInstance($enchantment, mt_rand(1, $max)), mt_rand(1, 100), mt_rand(1, 100)))->toItem();
                    break;
            }
        }
        $player->playDingSound();
        $this->setUsed();
        $inventory->setItemInHand($item);
    }

    private function getHadesHelm(NexusPlayer $player)
    {
        $level = $player->getDataSession()->getTotalXPLevel();
        $helm = $this->levelToHelm($level);
        $helm->setOriginalCustomName(TextFormat::RESET . TextFormat::DARK_PURPLE . TextFormat::BOLD . "Helm of Hades");
        $helm->setOriginalLore(["The helmet said to belong to the", "god of the underworld. Those who", "come in possession of this helmet", "should be weary of its power"]);
        $helm->addEnchantment(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::ANTI_VIRUS), 6));
        $helm->addEnchantment(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::GODLY_OVERLOAD), 3));
        $helm->addEnchantment(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::AEGIS), 5));
        $helm->addEnchantment(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::ENLIGHTED), 4));
        $helm->addEnchantment(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::LUCKY), 4));
        $helm->addEnchantment(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::TITAN_BLOOD), 4));
        $helm->addEnchantment(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::BLOOD_MAGIC), 5));
        $helm->addEnchantment(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::FATTY), 3));
        $helm->addEnchantment(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::HARDEN), 3));
        $helm->addEnchantment(new EnchantmentInstance(EnchantmentManager::getEnchantment(Enchantment::TOXIC_MIST), 3));

        return $helm;
    }

    /**
     * @param int $level
     *
     * @return Armor
     */
    private function levelToHelm(int $level): Armor
    {
        if ($level >= 100) {
            return ItemFactory::getInstance()->get(ItemIds::DIAMOND_HELMET, 0, 1);
        } elseif ($level >= 60) {
            return ItemFactory::getInstance()->get(ItemIds::IRON_HELMET, 0, 1);
        } elseif ($level >= 30) {
            return ItemFactory::getInstance()->get(ItemIds::GOLDEN_HELMET, 0, 1);
        } elseif ($level >= 10) {
            return ItemFactory::getInstance()->get(ItemIds::CHAIN_HELMET, 0, 1);
        } else {
            return ItemFactory::getInstance()->get(ItemIds::CHAIN_HELMET, 0, 1);
        }
    }
}
