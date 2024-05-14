<?php
declare(strict_types=1);

namespace core\player\gang;

use core\Nexus;
use core\player\NexusPlayer;
use core\provider\event\PlayerLoadEvent;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;

class GangListener implements Listener {

    /** @var Nexus */
    private $core;

    /**
     * GangListener constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
    }

    /**
     * @priority HIGHEST
     *
     * @param PlayerLoadEvent $event
     */
    public function onPlayerLoad(PlayerLoadEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        $faction = $player->getDataSession()->getGang();
        if($faction === null) {
            return;
        }
        foreach($faction->getOnlineMembers() as $member) {
            $member->sendMessage(Translation::GREEN . "{$player->getName()} is now online!");
        }
    }

    /**
     * @priority HIGHEST
     *
     * @param PlayerQuitEvent $event
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        if(!$player instanceof NexusPlayer) {
            return;
        }
        if(!$player->isLoaded()) {
            return;
        }
        $faction = $player->getDataSession()->getGang();
        if($faction === null) {
            return;
        }
        foreach($faction->getOnlineMembers() as $member) {
            $member->sendMessage(Translation::RED . "{$player->getName()} is now offline!");
        }
    }

    /**
     * @priority LOWEST
     *
     * @param EntityDamageEvent $event
     *
     * @throws TranslationException
     */
    public function onEntityDamage(EntityDamageEvent $event): void {
        $entity = $event->getEntity();
        if($entity instanceof NexusPlayer and $entity->isLoaded()) {
            $faction = $entity->getDataSession()->getGang();
            if($faction === null) {
                return;
            }
            if($event instanceof EntityDamageByEntityEvent) {
                $damager = $event->getDamager();
                if(!$damager instanceof NexusPlayer) {
                    return;
                }
                if(!$damager->isLoaded()) {
                    $event->cancel();
                    return;
                }
                $damagerFaction = $damager->getDataSession()->getGang();
                if($damagerFaction === null) {
                    return;
                }
                if($faction->isInGang($damager->getName()) or $faction->isAlly($damagerFaction)) {
                    $damager->sendMessage(Translation::getMessage("attackGangAssociate"));
                    $event->cancel();
                    return;
                }
            }
        }
    }
}