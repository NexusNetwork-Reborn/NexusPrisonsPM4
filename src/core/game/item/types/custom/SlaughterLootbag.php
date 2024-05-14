<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\types\Interactive;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rank\Rank;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\Inventory;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class SlaughterLootbag extends Interactive {

    const SLAUGHTER_LOOTBAG = "SlaughterLootbag";

    /**
     * SlaughterLootbag constructor.
     *
     * @param string|null $uuid
     */
    public function __construct(?string $uuid = null) {
        $customName = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "Slaughter Lootbag";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::RED . "Contains lots of goodies!";
        $lore[] = TextFormat::RESET . TextFormat::RED . "This was obtained from the Slaughter G-Kit!";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Hint: Right-click to open!";
        parent::__construct(VanillaItems::CHARCOAL(), $customName, $lore, true, true, $uuid);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::SLAUGHTER_LOOTBAG => StringTag::class
        ];
    }

    /**
     * @param Item $item
     *
     * @return $this
     */
    public static function fromItem(Item $item): self {
        $tag = self::getCustomTag($item);
        $uuid = $tag->getString(self::UUID);
        return new self($uuid);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setString(self::SLAUGHTER_LOOTBAG, self::SLAUGHTER_LOOTBAG);
        $tag->setString(self::UUID, $this->getUniqueId());
        return $tag;
    }

    /**
     * @param NexusPlayer $player
     * @param Inventory $inventory
     * @param Item $item
     */
    public function execute(NexusPlayer $player, Inventory $inventory, Item $item): void {
        if(mt_rand(1, 500) == 1) {
            $types = [
                Rank::NOBLE,
                Rank::IMPERIAL,
                Rank::SUPREME
            ];
            $item = (new RankNote(Nexus::getInstance()->getPlayerManager()->getRankManager()->getRankByIdentifier($types[array_rand($types)])))->toItem();
        } else {
            switch (mt_rand(1, 5)) {
                case 1:
                    $item = (new ChargeOrb(mt_rand(2, 7)))->toItem();
                    break;
                case 2:
                    $item = (new ChargeOrbSlot())->toItem()->setCount(mt_rand(1, 5));
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
}