<?php

namespace core\game\item\sets\type;

use core\game\item\sets\Set;
use core\game\item\sets\SetManager;
use core\game\item\sets\utils\SetUtils;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Axe;
use core\game\item\types\vanilla\Pickaxe;
use core\game\plots\PlotManager;
use core\level\entity\types\Powerball;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\PlayerManager;
use pocketmine\block\BlockLegacyIds;
use pocketmine\color\Color;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\entity\Location;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\TieredTool;
use pocketmine\item\ToolTier;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\BlazeShootSound;

class KothSet extends Set implements Listener
{

    private static array $compatibility = [];

    /**
     * @param int $levelRequirement
     */
    public function __construct(int $levelRequirement = 100)
    {
        parent::__construct($levelRequirement);
        Nexus::getInstance()->getServer()->getPluginManager()->registerEvents($this, Nexus::getInstance());

        self:: $compatibility = [
            ToolTier::WOOD()->name() => [BlockLegacyIds::COAL_ORE, BlockLegacyIds::IRON_ORE],
            ToolTier::STONE()->name() => [BlockLegacyIds::IRON_ORE, BlockLegacyIds::LAPIS_ORE],
            ToolTier::GOLD()->name() => [BlockLegacyIds::REDSTONE_ORE],
            ToolTier::IRON()->name() => [BlockLegacyIds::GOLD_ORE],
            ToolTier::DIAMOND()->name() => [BlockLegacyIds::DIAMOND_ORE],
        ];
    }

    public function getName(): string
    {
        return "Koth";
    }

    public function getColor(): string
    {
        return TextFormat::GOLD;
    }

    /**
     * @return Item
     */
    public function getHandItem(): Item
    {
        $item = ItemFactory::getInstance()->get(ItemIds::DIAMOND_PICKAXE);

        if ($item instanceof Pickaxe) {
            $item->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . "KOTH " . TextFormat::GRAY . "Pickaxe");
            $item->setOriginalLore([
                "",
                TextFormat::RESET . TextFormat::BOLD . TextFormat::YELLOW . "Set Bonus: " . TextFormat::GOLD . "KOTH",
                " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::GOLD . "+10% Warp Miner/Time Warp boost",
                "",
                TextFormat::RESET . TextFormat::GRAY . "(Requires all 4 KOTH armor pieces)",
                "",
            ]);
            $item->getNamedTag()->setString(SetManager::SET, "koth");

            $item->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(50), 1));
        }

        return $item;
    }

    /**
     * @return Item[]
     */
    public function getArmor(): array
    {
        $head = ItemFactory::getInstance()->get(ItemIds::LEATHER_CAP);
        $chest = ItemFactory::getInstance()->get(ItemIds::LEATHER_TUNIC);
        $leggings = ItemFactory::getInstance()->get(ItemIds::LEATHER_PANTS);
        $boots = ItemFactory::getInstance()->get(ItemIds::LEATHER_BOOTS);
        $armorLore = [
            "",
            TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . "Set Bonus: " . TextFormat::GOLD . "Koth",
            " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::GOLD . "+50% Outgoing PvE Damage",
            " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::GOLD . "Passive Powerball Party Ability",
            " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::GOLD . "Passive Epic Momentum Ability",
            " " . TextFormat::RESET . TextFormat::YELLOW . "* " . TextFormat::GOLD . "+100% Warp Miner Daily Limits",
            "",
            TextFormat::RESET . TextFormat::GRAY . "(Requires all 4 Lunacy armor pieces)",
            "",
            TextFormat::RESET . TextFormat::YELLOW . "This Armor is as strong as Diamond",
        ];

        if ($head instanceof Armor && $chest instanceof Armor && $leggings instanceof Armor && $boots instanceof Armor) {
            $head->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . "Koth " . TextFormat::GRAY . "Helmet");
            $head->setOriginalLore($armorLore);
            $head->setCustomColor(new Color(255, 165, 0));
            $head->getNamedTag()->setString(SetManager::SET, "koth");
            $chest->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . "Koth " . TextFormat::GRAY . "Chestplate");
            $chest->setOriginalLore($armorLore);
            $chest->setCustomColor(new Color(255, 165, 0));
            $chest->getNamedTag()->setString(SetManager::SET, "koth");
            $leggings->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . "Koth " . TextFormat::GRAY . "Leggings");
            $leggings->setOriginalLore($armorLore);
            $leggings->setCustomColor(new Color(255, 165, 0));
            $leggings->getNamedTag()->setString(SetManager::SET, "koth");
            $boots->setOriginalCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . "Koth " . TextFormat::GRAY . "Boots");
            $boots->setOriginalLore($armorLore);
            $boots->setCustomColor(new Color(255, 165, 0));
            $boots->getNamedTag()->setString(SetManager::SET, "koth");
        }

        return [$head, $chest, $leggings, $boots];
    }

    /**
     * @param EntityDamageByEntityEvent $event
     */
    public function onDamage(EntityDamageByEntityEvent $event): void
    {
        $victim = $event->getEntity();
        $attacker = $event->getDamager();
        if (!$attacker instanceof NexusPlayer || $victim instanceof NexusPlayer) {
            // The damage boost only applies to PvE.
            return;
        }
        if (SetUtils::isWearingFullSet($attacker, "koth")) {
            // The damage boost only applies to players wearing the full set.
            return;
        }
        $event->setBaseDamage($event->getBaseDamage() * 1.5);
    }

    /**
     * @param PlayerItemUseEvent $event
     */
    public function onItemUse(PlayerItemUseEvent $event): void
    {
        $owner = $event->getPlayer();

        if (SetUtils::isWearingFullSet($event->getPlayer(), "koth") && $event->getItem() instanceof Pickaxe && $owner instanceof NexusPlayer) {

            if (!$owner->isSneaking()) {
                return;
            }

            if ($owner->getCooldownLeft(60 - 21, NexusPlayer::POWERBALL) > 0) {
                $owner->sendTranslatedMessage("actionCooldown", [
                    "amount" => TextFormat::RED . $owner->getCooldownLeft(60 - (21), NexusPlayer::POWERBALL)
                ]);
                return;
            }

            $owner->setCooldown(NexusPlayer::POWERBALL);
            $owner->getWorld()->addSound($owner->getPosition(), new BlazeShootSound(), [$owner]);
            $owner->getCESession()->setPowerball(true);
            $max = 0;
            for ($i = 1; $i <= (3 + mt_rand(0, 1 + 3)); $i++) {
                $location = $owner->getEyePos();
                $powerball = new Powerball(new Location($location->x, $location->y, $location->z, $owner->getWorld(), 0, 0));
                $powerball->spawnToAll();
                $time = mt_rand(100, 130);
                $powerball->setLifeTime($time);
                if ($time > $max) {
                    $max = $time;
                }
                $powerball->setOwningEntity($owner);
                $powerball->setDirectionVector($owner->getDirectionVector()->add(mt_rand(-5, 5) / 10, 0, mt_rand(-5, 5) / 10));
            }
            Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new class($owner) extends Task {

                /** @var NexusPlayer */
                private $player;

                /**
                 *  constructor.
                 *
                 * @param NexusPlayer $player
                 */
                public function __construct(NexusPlayer $player)
                {
                    $this->player = $player;
                }

                public function onRun(): void
                {
                    if ($this->player->isOnline()) {
                        $this->player->getCESession()->setPowerball(false);
                    }
                }
            }, $max);
        }
    }

    public function onBreak(BlockBreakEvent $event): void
    {
        $player = $event->getPlayer();
        if (!$player instanceof NexusPlayer) {
            return;
        }
        if(!SetUtils::isWearingFullSet($player, "koth")) {
            return;
        }

        $item = $event->getItem();
        if (!$item instanceof TieredTool) {
            return;
        }
        if ($item->getCustomName() != $this->getHandItem()->getCustomName()) {
            return;
        }

        $block = $event->getBlock();
        $breakTime = PlayerManager::calculateBlockBreakTime($player, $block);
        $add = ceil($breakTime / 20); //TODO: Balance

        if (in_array($block->getId(), self::$compatibility[$item->getTier()->name()])) $add += mt_rand(2, 4);

        if (PlotManager::isPlotWorld($player->getWorld())) {
            $add *= 0.75;
        }

        $player->getCESession()->addMomentum((int)$add);
    }
}