<?php

namespace core\game\item\slotbot;

use core\game\item\types\CustomItem;
use core\game\item\types\vanilla\Fireworks;
use core\level\entity\types\FireworksRocket;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rank\Rank;
use core\translation\Translation;
use pocketmine\block\Block;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\Location;
use pocketmine\inventory\Inventory;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;

use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class SlotBotTicket extends CustomItem
{

    const TYPE = "Type";

    /** @var string */
    private $type = "Normal";

    /**
     * RankNote constructor.
     *
     * @param string $type
     * @param string|null $uuid
     */
    public function __construct(string $type, ?string $uuid = null)
    {
        // TODO: Type basis
        $item = ItemFactory::getInstance()->get(ItemIds::PAPER);
//        $item->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(50), 1));
        $item->setCustomName($name = TextFormat::AQUA . TextFormat::BOLD . "Cosmo" . TextFormat::WHITE . "-"  . TextFormat::colorize("&dSlot Bot") . TextFormat::RESET . TextFormat::GRAY . " Ticket");
        //$item->setCustomName($name = TextFormat::GOLD . "Slot" . TextFormat::AQUA . "Bot " . TextFormat::GRAY . "Ticket");
        $lore = [];
        $lore[] = TextFormat::GRAY . "\nUse this item in the SlotBot for\na chance to win cool rewards!";
//        $lore[] = TextFormat::GRAY . "-------------------------";
//        $lore[] = TextFormat::GOLD . "Discovered in the ancient\ngold-mines";
//        $lore[] = TextFormat::GRAY . "-------------------------";
        $item->setLore($lore);
        return parent::__construct($item, $name, $lore, true, false);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array
    {
        return [
            self::TYPE => StringTag::class
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
        $type = $tag->getString(self::TYPE, "Normal");
        //$uuid = $tag->getString(self::UUID);
        return new self($type);
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag
    {
        $tag = new CompoundTag();
        $tag->setString(self::TYPE, $this->type);
        //$tag->setString(self::UUID, $this->getUniqueId());
        return $tag;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}