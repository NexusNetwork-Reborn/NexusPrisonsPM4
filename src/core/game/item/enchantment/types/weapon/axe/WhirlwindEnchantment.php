<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon\axe;

use core\game\item\enchantment\Enchantment;
use core\game\item\types\vanilla\Armor;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\enchantment\ItemFlags;
use pocketmine\utils\TextFormat;

class WhirlwindEnchantment extends Enchantment
{

    /** @var array */
    private static array $enhancedDurableDamage = [];

    public function __construct()
    {
        parent::__construct(self::WHIRLWIND,
            "Whirlwind",
            self::LEGENDARY,
            "Chance on attack to deal +1 stack to nearby enemy players, dealing True HP and increasing incoming Durability Damage to affected enemies for 12s (+25% per stack) (Max Stacks: 8).",
            self::DAMAGE,
            ItemFlags::AXE,
            4
        );

        $this->callable = function (EntityDamageByEntityEvent $event, int $level, float &$damage) : void {
            $damager = $event->getDamager();
            $entity = $event->getEntity();

            if($event->isCancelled() || !$damager instanceof NexusPlayer) return;

            $random = mt_rand(1, 500);
            $chance = ($level * 1.5) * $damager->getCESession()->getItemLuckModifier();

            if($chance > $random) {
                $damager->getCESession()->addWhirlwindStack();

                $stack = $damager->getCESession()->getWhirlwindStacks();
                $hurt = round($stack / 10, 2);

                if($stack > 10) $stack = 10;

                $damager->addCEPopup(TextFormat::BOLD . TextFormat::GRAY . "* [" . TextFormat::RESET . TextFormat::GOLD . $stack . "Whirlwind Stacks" . TextFormat::BOLD . TextFormat::GRAY . "* ]");

                $entity->setHealth($entity->getHealth() - round(($hurt * (1 + ($level / 3))), 2));

                if($entity instanceof NexusPlayer) {
                    self::$enhancedDurableDamage[$entity->getUniqueId()->toString()] = [time(), $stack];
                }
            }
        };
    }

    /**
     * @param EntityDamageByEntityEvent $event
     * @priority HIGH
     */
    public function onDamage(EntityDamageByEntityEvent $event) : void
    {
        $player = $event->getEntity();
        $damager = $event->getDamager();

        if($damager instanceof NexusPlayer && $player instanceof NexusPlayer && isset(self::$enhancedDurableDamage[$player->getUniqueId()->toString()])) {
            if(self::$enhancedDurableDamage[$player->getUniqueId()->toString()][0] + 12 > time()) {
                foreach ($damager->getArmorInventory()->getContents() as $armor) {
                    if($armor instanceof Armor) $armor->applyDamage((int)ceil($event->getFinalDamage() * (0.25 * self::$enhancedDurableDamage[$player->getUniqueId()->toString()][1])));
                }
            } else {
                unset(self::$enhancedDurableDamage[$player->getUniqueId()->toString()]);
            }
        }
    }
}