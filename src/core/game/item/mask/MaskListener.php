<?php
declare(strict_types=1);

namespace core\game\item\mask;

use core\command\forms\ItemInformationForm;
use core\game\boop\task\DupeLogTask;
use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\event\EarnEnergyEvent;
use core\game\item\types\custom\Satchel;
use core\game\item\types\custom\Shard;
use core\game\item\types\CustomItem;
use core\game\item\types\Interactive;
use core\game\item\types\Rarity;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Pickaxe;
use core\game\plots\PlotManager;
use core\level\block\Ore;
use core\level\block\OreGenerator;
use core\Nexus;
use core\player\NexusPlayer;
use core\provider\event\PlayerLoadEvent;
use core\translation\Translation;
use core\translation\TranslationException;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\SplashPotion;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockFormEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class MaskListener implements Listener {

    /** @var Nexus */
    private $core;

    /**
     * ItemListener constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
    }

    public function onEarnEnergy(EarnEnergyEvent $event) {
        /** @var Armor $helm */
        $helm = $event->getPlayer()->getArmorInventory()->getHelmet();
        if ($helm instanceof Armor && $helm->hasMask(Mask::TINKERER)) {
            // TODO: Make 5% when non-overworld dimensions come out
            $event->addAmount((int)($event->getAmount() * 0.1));
        }
    }

    public function onEntityDamage(EntityDamageByEntityEvent $event) {
        $ent = $event->getEntity();
        $damager = $event->getDamager();
        if($ent instanceof NexusPlayer) {
            $helm = $ent->getArmorInventory()->getHelmet();
            if ($helm instanceof Armor && $helm->hasMask(Mask::SHADOW)) {
                if(mt_rand(1, 100) <= 2) {
                    $event->cancel();
                    $ent->setMotion($ent->getDirectionVector()->multiply(2));
                    $ent->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 160, 1, true));
                }
            } else if($helm instanceof Armor && $helm->hasMask(Mask::FIREFLY)) {
                if(($ent->getHealth() / $ent->getMaxHealth()) < 0.5 && mt_rand(1, 100) <= 3) {
                    $ent->getEffects()->add(new EffectInstance(VanillaEffects::INVISIBILITY(), 60, 1, false));
                }
            }
        }
        if($damager instanceof NexusPlayer) {
            $helm = $damager->getArmorInventory()->getHelmet();
            if ($helm instanceof Armor && $helm->hasMask(Mask::FOCUS)) {
                $event->setBaseDamage(($event->getBaseDamage() * 1.04));
            }
        }
    }

}