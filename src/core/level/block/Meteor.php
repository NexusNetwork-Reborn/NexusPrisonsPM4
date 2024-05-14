<?php
declare(strict_types=1);

namespace core\level\block;

use core\game\fund\FundManager;
use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\event\DiscoverContrabandEvent;
use core\game\item\event\EarnEnergyEvent;
use core\game\item\types\custom\Contraband;
use core\game\item\types\Rarity;
use core\game\item\types\vanilla\Pickaxe;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockToolType;
use pocketmine\block\Opaque;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\ToolTier;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\SmokeParticle;
use pocketmine\world\sound\FireExtinguishSound;

class Meteor extends Opaque {

    /** @var bool */
    private $updated = false;

    /**
     * Meteor constructor.
     *
     * @param BlockIdentifier $idInfo
     * @param string $name
     */
    public function __construct(BlockIdentifier $idInfo, string $name) {
        parent::__construct($idInfo, $name, new BlockBreakInfo(20.0, BlockToolType::PICKAXE, ToolTier::WOOD()->getHarvestLevel()));
    }

    /**
     * @param Item $item
     *
     * @return array
     */
    public function getDropsForCompatibleTool(Item $item): array {
        switch(mt_rand(1, 7)) {
            case 1:
                $drops = VanillaBlocks::IRON_ORE()->getDropsForCompatibleTool($item);
                break;
            case 2:
                $drops = VanillaBlocks::LAPIS_LAZULI_ORE()->getDropsForCompatibleTool($item);
                break;
            case 3:
                $drops = VanillaBlocks::REDSTONE_ORE()->getDropsForCompatibleTool($item);
                break;
            case 4:
                $drops = VanillaBlocks::GOLD_ORE()->getDropsForCompatibleTool($item);
                break;
            case 5:
                $drops = VanillaBlocks::DIAMOND_ORE()->getDropsForCompatibleTool($item);
                break;
            case 6:
                $drops = VanillaBlocks::EMERALD_ORE()->getDropsForCompatibleTool($item);
                break;
            default:
                $drops = VanillaBlocks::COAL_ORE()->getDropsForCompatibleTool($item);
                break;
        }
        $newDrops = [];
        foreach($drops as $drop){
            $newDrops[] = $drop->setCount(mt_rand(32, 64));
        }
        return $newDrops;
    }

    public function onBreak(Item $item, Player $player = null): bool {
        if($player instanceof NexusPlayer) {
            if($player->isCreative(true)) {
                return parent::onBreak($item, $player);
            }
        }
        if($this->getBreakInfo()->isToolCompatible($item)) {
            if($player instanceof NexusPlayer) {
                if($item->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::MOMENTUM)) > 0) {
                    $player->getCESession()->addMomentum(mt_rand(20, 30));
                }
                $level = $item->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::METEOR_HUNTER));
                if($level < 0) {
                    $level = 0;
                }
                $level *= $player->getCESession()->getItemLuckModifier();
                $energy = mt_rand(1000, 2000);
                if($item->hasEnchantment(EnchantmentManager::getEnchantment(Enchantment::ENERGY_COLLECTOR))) {
                    $energy *= (1 + ($item->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::ENERGY_COLLECTOR)) * 0.45));
                }
                if($item->hasEnchantment(EnchantmentManager::getEnchantment(Enchantment::ENERGY_HOARDER))) {
                    $energy *= (4 + ($item->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::ENERGY_COLLECTOR)) * 0.35));
                }
                $player->getDataSession()->addToXP(mt_rand(1000, 5000));
                $ev = new EarnEnergyEvent($player, (int)$energy);
                $ev->call();
                if($item instanceof Pickaxe) {
                    $item->addEnergy($ev->getAmount(), $player);
                }
                if($level >= mt_rand(1, 200)) {
                    $rarities = [
                        Rarity::SIMPLE,
                        Rarity::UNCOMMON,
                        Rarity::ELITE,
                        Rarity::ULTIMATE
                    ];
                    if(mt_rand(1, 25) === 1) {
                        $rarity = Rarity::GODLY;
                    }
                    elseif(mt_rand(1, 10) === 1) {
                        $rarity = Rarity::LEGENDARY;
                    }
                    else {
                        $rarity = $rarities[array_rand($rarities)];
                    }
                    $ev = new DiscoverContrabandEvent($player, $rarity);
                    $ev->call();
                    $drop = (new Contraband($rarity))->toItem()->setCount(1);
                    $name = TextFormat::RESET . TextFormat::WHITE . $drop->getName();
                    if($drop->hasCustomName()) {
                        $name = $drop->getCustomName();
                    }
                    $name .= TextFormat::RESET . TextFormat::GRAY . " * " . TextFormat::WHITE . $drop->getCount();
                    $player->sendTitle(TextFormat::GOLD . "Discovered", $name);
                    $player->playBlastSound();
                    $player->getInventory()->addItem($drop);
                }
            }
            $this->getPosition()->getWorld()->setBlock($this->getPosition(), VanillaBlocks::AIR(), true);
            return true;
        }
        return false;
    }

    public function onScheduledUpdate(): void {
        $world = $this->getPosition()->getWorld();
        $tile = $world->getTile($this->getPosition());
        if((!Nexus::getInstance()->getGameManager()->getFundManager()->isUnlocked(FundManager::PHASE_ONE))) {
            if($tile instanceof \core\level\tile\Meteor) {
                $world->removeTile($tile);
            }
            $world->addParticle($this->getPosition()->add(0, 1, 0), new SmokeParticle());
            $world->addSound($this->getPosition(), new FireExtinguishSound());
            $world->setBlock($this->getPosition(), VanillaBlocks::AIR());
            return;
        }
        if(!$this->updated) {
            $world->scheduleDelayedBlockUpdate($this->getPosition(), mt_rand(4800, 5600));
            $this->updated = true;
            return;
        }
        if($tile instanceof \core\level\tile\Meteor) {
            $world->removeTile($tile);
        }
        $world->addParticle($this->getPosition()->add(0, 1, 0), new SmokeParticle());
        $world->addSound($this->getPosition(), new FireExtinguishSound());
        $world->setBlock($this->getPosition(), VanillaBlocks::AIR());
    }
}
