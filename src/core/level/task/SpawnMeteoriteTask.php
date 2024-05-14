<?php
declare(strict_types=1);

namespace core\level\task;

use core\display\animation\AnimationManager;
use core\display\animation\entity\MeteorBaseEntity;
use core\game\fund\FundManager;
use core\level\LevelManager;
use core\level\tile\Meteorite;
use core\Nexus;
use core\player\NexusPlayer;
use libs\utils\LevelUtils;
use libs\utils\Task;
use pocketmine\block\StainedGlass;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\world\particle\HugeExplodeSeedParticle;
use pocketmine\world\Position;
use pocketmine\world\sound\ExplodeSound;
use pocketmine\world\World;

class SpawnMeteoriteTask extends Task {

    /** @var LevelManager */
    private $manager;

    /** @var World */
    private $world;

    /** @var int */
    private $spawnRate = 600;

    /**
     * SpawnMeteoriteTask constructor.
     *
     * @param LevelManager $manager
     */
    public function __construct(LevelManager $manager) {
        $this->manager = $manager;
        $this->world = Server::getInstance()->getWorldManager()->getDefaultWorld();
    }

    /**
     * @param int $currentTick
     */
    public function onRun(): void {
        if(count(Server::getInstance()->getOnlinePlayers()) <= 0) {
            return;
        }
        if(!Nexus::getInstance()->getGameManager()->getFundManager()->isUnlocked(FundManager::PHASE_THREE)) {
            return;
        }
        if(Nexus::getInstance()->getServerManager()->getAnnouncementManager()->getRestarter()->getRestartProgress() > 5) {
            if(--$this->spawnRate <= 0) {
                $position = $this->findLocation($this->world);
                if($position === null) {
                    return;
                }
                $chunk = $this->world->getOrLoadChunkAtPosition($position);
                if($chunk === null) {
                    return;
                }
                foreach($chunk->getTiles() as $tile) {
                    if($tile instanceof Meteorite) {
                        return;
                    }
                }
                $this->spawnMeteor($position);
                Server::getInstance()->getLogger()->notice("A meteorite is scheduled to spawn at x: {$position->getX()}, y: {$position->getY()}, z: {$position->getZ()},");
                $this->spawnRate = 600;
            }
        }
    }

    /**
     * @param World $world
     *
     * @return Position|null
     */
    protected function findLocation(World $world): ?Position {
        $minX = -330;
        $maxX = 460;
        $minZ = -330;
        $maxZ = 460;
        $x = mt_rand($minX, $maxX);
        $z = mt_rand($minZ, $maxZ);
        $world->loadChunk($x >> 4, $z >> 4);
        $spawn = LevelUtils::getSafeSpawn($world, new Vector3($x, 1, $z), 140);
        if($spawn->y > 1) {
            $areas = Nexus::getInstance()->getServerManager()->getAreaManager()->getAreasInPosition($spawn);
            if($areas !== null) {
                foreach($areas as $area) {
                    if($area->getPvpFlag() === false) {
                        return null;
                    }
                }
            }
            if($world->getBlock($spawn->subtract(0, 1, 0)) instanceof StainedGlass) {
                return null;
            }
            return $spawn;
        }
        return null;
    }

    /**
     * @param Position $position
     *
     * @throws \core\display\animation\AnimationException
     */
    protected function spawnMeteor(Position $position): void {
        $coords = [
            10,
            -10
        ];
        $entity = MeteorBaseEntity::create(AnimationManager::METEOR_ID, Position::fromObject($position->add($coords[mt_rand(0, 1)], 45, $coords[mt_rand(0, 1)]), $this->world));
        $entity->setScale(1.5);
        $entity->initialize($position, 0.35, true, 0, function(MeteorBaseEntity $entity) use($position): void {
            $level = $entity->getWorld();
            if($level !== null) {
                $bb = $entity->getBoundingBox()->expandedCopy(12, 12, 12);
                foreach($level->getNearbyEntities($bb) as $e) {
                    if($e instanceof NexusPlayer) {
                        $ev = new EntityDamageByEntityEvent($entity, $e, EntityDamageEvent::CAUSE_ENTITY_EXPLOSION, 4);
                        $e->attack($ev);
                    }
                }
                $level->addParticle($entity->getPosition(), new HugeExplodeSeedParticle());
                $level->addSound($entity->getPosition(), new ExplodeSound());
                $this->world->setBlock($position, VanillaBlocks::MAGMA());
                $tile = new Meteorite($position->getWorld(), $position);
                $tile->setStack(mt_rand(100, 200));
                if(mt_rand(1, 5) === mt_rand(1, 5)) {
                    $tile->setRefined(true);
                }
                $this->world->addTile($tile);
            }
        });
        $entity->spawnToAll();
    }
}
