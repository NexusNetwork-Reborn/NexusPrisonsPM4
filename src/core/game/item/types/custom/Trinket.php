<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\item\enchantment\EnchantmentManager;
use core\game\item\types\Interactive;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rpg\RPGManager;
use core\player\rpg\XPUtils;
use core\translation\Translation;
use pocketmine\block\Block;
use pocketmine\inventory\Inventory;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class Trinket extends Interactive {

    const TRINKET = "Trinket";

    const ENERGY = "Energy";

    const WHITESCROLLED = "Whitescrolled";

    const CORRUPTED = "Corrupted";

    /** @var \core\game\item\trinket\Trinket */
    private $type;

    /** @var int */
    private $energy;

    /** @var bool */
    private $whitescroll;

    /** @var int */
    private $corrupted = 0;

    /**
     * Trinket constructor.
     *
     * @param string $type
     * @param int $energy
     * @param bool $whitescrolled
     * @param int $corrupted
     */
    public function __construct(string $type, int $energy = 0, bool $whitescrolled = false, int $corrupted = 0) {
        $this->type = Nexus::getInstance()->getGameManager()->getItemManager()->getTrinket($type);
        $this->energy = $energy;
        $this->whitescroll = $whitescrolled;
        $this->corrupted = $corrupted;
        $color = $this->type->getColor();
        $uses = (int)floor($energy / $this->type->getCost());
        $customName = TextFormat::RESET . TextFormat::BOLD . $color . $type . TextFormat::RESET . TextFormat::GRAY . " (" . number_format($uses) . ")";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::WHITE . "Effect: " . $color . $this->type->getEffect();
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::WHITE . "Charges: " . $color . number_format($uses);
        $lore[] = TextFormat::RESET . TextFormat::WHITE . "Energy: " . $color . number_format($energy);
        $lore[] = TextFormat::RESET . TextFormat::WHITE . "Energy per use: " . $color . number_format($uses);
        $lore[] = TextFormat::RESET . TextFormat::WHITE . "Cooldown: " . $color . $this->type->getCooldown() . "s";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "(Right-click to activate)";
        if($this->corrupted > 0) {
            $lore[] = "";
            if($whitescrolled) {
                $lore[] = TextFormat::RESET . TextFormat::WHITE . TextFormat::BOLD . "WHITESCROLLED";
            }
            $lore[] = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "SEMI-CORRUPT (" . TextFormat::RESET . TextFormat::GRAY . $this->corrupted . TextFormat::BOLD . TextFormat::RED . "/" . TextFormat::RESET . TextFormat::GRAY . "3 whitescrolls applied" . TextFormat::RED . TextFormat::BOLD . ")";
        }
        parent::__construct($this->type->getDisplay(), $customName, $lore, true, true);
    }

    /**
     * @return string
     */
    public function getName(): string {
        $color = $this->type->getColor();
        $uses = (int)floor($this->energy / $this->type->getCost());
        return TextFormat::RESET . TextFormat::BOLD . $color . $this->type->getName() . TextFormat::RESET . TextFormat::GRAY . " (" . number_format($uses) . ")";
    }

    /**
     * @return array
     */
    public function getLore(): array {
        $color = $this->type->getColor();
        $uses = (int)floor($this->energy / $this->type->getCost());
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::WHITE . "Effect: " . $color . $this->type->getEffect();
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::WHITE . "Charges: " . $color . number_format($uses);
        $lore[] = TextFormat::RESET . TextFormat::WHITE . "Energy: " . $color . number_format($this->energy);
        $lore[] = TextFormat::RESET . TextFormat::WHITE . "Energy per use: " . $color . number_format($uses);
        $lore[] = TextFormat::RESET . TextFormat::WHITE . "Cooldown: " . $color . $this->type->getCooldown() . "s";
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "(Right-click to activate)";
        if($this->corrupted > 0) {
            $lore[] = "";
            if($this->whitescroll) {
                $lore[] = TextFormat::RESET . TextFormat::WHITE . TextFormat::BOLD . "WHITESCROLLED";
            }
            $lore[] = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "SEMI-CORRUPT (" . TextFormat::RESET . TextFormat::GRAY . $this->corrupted . TextFormat::BOLD . TextFormat::RED . "/" . TextFormat::RESET . TextFormat::GRAY . "3 whitescrolls applied" . TextFormat::RED . TextFormat::BOLD . ")";
        }
        return $lore;
    }

    /**
     * @return string[]
     */
    public static function getRequiredTags(): array {
        return [
            self::TRINKET => StringTag::class,
            self::ENERGY => LongTag::class,
            self::WHITESCROLLED => ByteTag::class,
            self::CORRUPTED => ByteTag::class
        ];
    }

    /**
     * @param Item $item
     *
     * @return Satchel|mixed
     */
    public static function fromItem(Item $item) {
        $tag = self::getCustomTag($item);
        $type = $tag->getString(self::TRINKET);
        $energy = $tag->getLong(self::ENERGY);
        $whitescrolled = (bool)$tag->getByte(self::WHITESCROLLED);
        $corrupted = (int)$tag->getByte(self::CORRUPTED);
        return new self($type, $energy, $whitescrolled, $corrupted);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setString(self::TRINKET, $this->type->getName());
        $tag->setLong(self::ENERGY, $this->energy);
        $tag->setByte(self::WHITESCROLLED, (int)$this->whitescroll);
        $tag->setByte(self::CORRUPTED, $this->corrupted);
        return $tag;
    }

    /**
     * @return \core\game\item\trinket\Trinket
     */
    public function getType(): \core\game\item\trinket\Trinket {
        return $this->type;
    }
    /**
     * @return int
     */
    public function getEnergy(): int {
        return $this->energy;
    }

    /**
     * @param int $energy
     */
    public function setEnergy(int $energy): void {
        $this->energy = $energy;
    }

    /**
     * @param int $energy
     */
    public function addEnergy(int $energy): void {
        $this->energy += $energy;
    }

    /**
     * @return bool
     */
    public function isWhitescroll(): bool {
        return $this->whitescroll;
    }

    /**
     * @param bool $whitescroll
     */
    public function setWhitescroll(bool $whitescroll): void {
        $this->whitescroll = $whitescroll;
    }

    /**
     * @return int
     */
    public function getCorrupted(): int {
        return $this->corrupted;
    }

    /**
     * @param int $corrupted
     */
    public function setCorrupted(int $corrupted): void {
        $this->corrupted = min(3, $corrupted);
    }

    /**
     * @param NexusPlayer $player
     * @param Inventory $inventory
     * @param Item $item
     */
    public function execute(NexusPlayer $player, Inventory $inventory, Item $item): void {
        $cd = $player->getCooldownLeft($this->type->getCooldown(), $this->type->getName());
        if($cd > 0) {
            $player->sendTranslatedMessage("actionCooldown", [
                "amount" => TextFormat::RED . $cd
            ]);
            $player->playErrorSound();
            return;
        }
        $leftover = $this->energy - $this->type->getCost();
        if($leftover < 0) {
            $player->sendMessage(Translation::RED . "You have no more charges!");
            $player->playErrorSound();
            return;
        }
        if($this->type->onItemUse($player)) {
            $this->setEnergy($leftover);
            $player->getInventory()->setItemInHand($this->toItem());
        }
    }

    /**
     * @param NexusPlayer $player
     * @param Inventory $inventory
     * @param Item $item
     * @param int $face
     * @param Block $block
     */
    public function onInteract(NexusPlayer $player, Inventory $inventory, Item $item, int $face, Block $block): void {
        $this->execute($player, $inventory, $item);
    }
}