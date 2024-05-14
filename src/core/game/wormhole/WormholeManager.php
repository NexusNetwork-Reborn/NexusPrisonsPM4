<?php

namespace core\game\wormhole;

use core\game\item\enchantment\Enchantment;
use core\game\item\types\Enchantable;
use core\game\item\types\vanilla\Pickaxe;
use core\level\LevelManager;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\item\Item;
use pocketmine\world\Position;

class WormholeManager {

    /** @var Nexus */
    private $core;

    /** @var Wormhole */
    private $wormhole;

    /** @var Wormhole */
    private $executiveWormhole;

    /** @var WormholeSession[] */
    private $sessions = [];

    /**
     * GameManager constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $core->getServer()->getPluginManager()->registerEvents(new WormholeListener($core), $core);
        $this->init();
    }

    public function init(): void {
        $cfg = LevelManager::getSetup();
        $xyz = explode(":", $cfg->getNested("wormhole.xyz"));
        $executiveXYZ = explode(":", $cfg->getNested("executive-mine.wormhole.xyz"));
        $this->wormhole = new Wormhole(new Position((float)$xyz[0], (float)$xyz[1], (float)$xyz[2], $this->core->getServer()->getWorldManager()->getDefaultWorld()), (int)$cfg->getNested("wormhole.radius"));
        $this->executiveWormhole = new Wormhole(new Position((float)$executiveXYZ[0], (float)$executiveXYZ[1], (float)$executiveXYZ[2], $this->core->getServer()->getWorldManager()->getWorldByName("executive")), (int)$cfg->getNested("executive-mine.wormhole.radius"), Enchantment::EXECUTIVE);
    }

    /**
     * @param Item $item
     *
     * @return bool
     */
    public static function canEnterWormhole(Item $item): bool {
        // TODO: Satchels
        return $item instanceof Pickaxe or $item instanceof Enchantable;
    }

    /**
     * @return Wormhole[]
     */
    public function getAllWormholes() : array {
        return [$this->wormhole, $this->executiveWormhole];
    }

    public function getExecutiveWormhole(): Wormhole
    {
        return $this->executiveWormhole;
    }

    /**
     * @return WormholeSession[]
     */
    public function getSessions(): array {
        return $this->sessions;
    }

    /**
     * @param WormholeSession $session
     */
    public function addSession(WormholeSession $session): void {
        $this->sessions[$session->getOwner()->getUniqueId()->toString()] = $session;
    }

    /**
     * @param WormholeSession $session
     */
    public function removeSession(WormholeSession $session): void {
        $uuid = $session->getOwner()->getUniqueId()->toString();
        foreach($session->getEntities() as $entity) {
            if($entity->isClosed()) {
                continue;
            }
            $entity->flagForDespawn();
        }
        if(isset($this->sessions[$uuid])) {
            unset($this->sessions[$uuid]);
        }
    }

    /**
     * @param NexusPlayer $owner
     *
     * @return WormholeSession|null
     */
    public function getSession(NexusPlayer $owner): ?WormholeSession {
        return $this->sessions[$owner->getUniqueId()->toString()] ?? null;
    }
}