<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\weapon\sword;

use core\game\item\enchantment\Enchantment;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\item\enchantment\ItemFlags;
use pocketmine\utils\TextFormat;

class ThousandCutsEnchantment extends Enchantment implements Listener
{

    /** @var array */
    private static array $reducedDPS = [];

    public function __construct()
    {
        parent::__construct(self::THOUSAND_CUTS,
            "Thousand Cuts",
            self::ULTIMATE,
            "Chance on attack to deal +1 stack to nearby enemy players, dealing True HP and reducing Outgoing DmG to affected enemies by up to 12% for 15s (Max Stacks:10).",
            self::DAMAGE,
            ItemFlags::SWORD,
            5
        );

        $this->callable = function (EntityDamageByEntityEvent $event, int $level, float &$damage) : void {
            $damager = $event->getDamager();
            $entity = $event->getEntity();

            if($event->isCancelled() || !$damager instanceof NexusPlayer) return;

            $random = mt_rand(1, 500);
            $chance = $level * $damager->getCESession()->getItemLuckModifier();

            if($chance > $random) {
                $damager->getCESession()->addThousandCutStack();

                $stack = $damager->getCESession()->getThousandCutsStacks();
                $hurt = round($stack / 10, 2);

                if($stack > 10) $stack = 10;

                $damager->addCEPopup(TextFormat::BOLD . TextFormat::GRAY . "* [" . TextFormat::RESET . TextFormat::GOLD . $stack . " TC Stacks" . TextFormat::BOLD . TextFormat::GRAY . "* ]");

                $entity->setHealth($entity->getHealth() - round(($hurt * (1 + ($level / 4))), 2));

                if($entity instanceof NexusPlayer) {
                    self::$reducedDPS[$entity->getUniqueId()->toString()] = [time(), $stack];
                }
            }
        };

        Nexus::getInstance()->getServer()->getPluginManager()->registerEvents($this, Nexus::getInstance());
    }

    /**
     * @param EntityDamageByEntityEvent $event
     */
    public function onDamage(EntityDamageByEntityEvent $event) : void
    {
        $player = $event->getDamager();

        if($player instanceof NexusPlayer && isset(self::$reducedDPS[$player->getUniqueId()->toString()])) {
            if(self::$reducedDPS[$player->getUniqueId()->toString()][0] + 15 > time()) {
                $event->setBaseDamage($event->getBaseDamage() * (0.98 - (self::$reducedDPS[$player->getUniqueId()->toString()][1] / 100)));
            } else {
                unset(self::$reducedDPS[$player->getUniqueId()->toString()]);
            }
        }
    }
}