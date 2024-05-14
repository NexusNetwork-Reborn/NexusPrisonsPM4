<?php
declare(strict_types=1);

namespace core\level\task;

use core\display\animation\AnimationManager;
use core\display\animation\entity\MeteorBaseEntity;
use core\game\fund\FundManager;
use core\game\item\types\vanilla\Armor;
use core\level\LevelManager;
use core\level\tile\Meteor;
use core\level\tile\Meteorite;
use core\Nexus;
use core\player\NexusPlayer;
use libs\utils\LevelUtils;
use libs\utils\Task;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\StainedGlass;
use pocketmine\block\utils\CoralType;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\HugeExplodeSeedParticle;
use pocketmine\world\Position;
use pocketmine\world\sound\ExplodeSound;
use pocketmine\world\World;

class SpawnMeteorTask extends Task {

    /** @var LevelManager */
    private $manager;

    /** @var World */
    private $world;

    /** @var int */
    private $spawnRate = 2700;

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
        if(!Nexus::getInstance()->getGameManager()->getFundManager()->isUnlocked(FundManager::PHASE_ONE)) {
            return;
        }
        if(Nexus::getInstance()->getServerManager()->getAnnouncementManager()->getRestarter()->getRestartProgress() > 5) {
            if(--$this->spawnRate <= 0) {
                $position = $this->findLocation($this->world);
                if($position === null) {
                    return;
                }
                $x = $position->getX();
                $y = $position->getY();
                $z = $position->getZ();
                $zone = Nexus::getInstance()->getGameManager()->getZoneManager()->getZoneInPosition($position);
                Server::getInstance()->broadcastMessage(TextFormat::BOLD . TextFormat::GOLD . "(!) A meteor is falling from the sky at:");
                $add = "";
                if($zone !== null) {
                    switch($zone->getTierId()) {
                        case Armor::TIER_CHAIN:
                            $name = "Chain";
                            break;
                        case Armor::TIER_GOLD:
                            $name = "Gold";
                            break;
                        case Armor::TIER_IRON:
                            $name = "Iron";
                            break;
                        case Armor::TIER_DIAMOND | Armor::TIER_LEATHER:
                            $name = "Diamond";
                            break;
                        default:
                            return;
                    }
                    $add .= TextFormat::GRAY . " ($name Zone)";
                }
                $add .= TextFormat::WHITE . " ETA: 30 Seconds";
                Server::getInstance()->broadcastMessage(TextFormat::RESET . TextFormat::WHITE . "$x" . TextFormat::GRAY . "x, " . TextFormat::WHITE . "$y" . TextFormat::GRAY . "y, " . TextFormat::WHITE . "$z" . TextFormat::GRAY . "z" . $add);
                Server::getInstance()->broadcastMessage(TextFormat::RESET . TextFormat::GRAY . "Meteors are from lost galaxies that give massive amounts of energy and loot.");
                $this->spawnMeteor($position);
                Server::getInstance()->getLogger()->notice("A meteor is scheduled to spawn at x: {$position->getX()}, y: {$position->getY()}, z: {$position->getZ()},");
                $this->spawnRate = 2700;
            }
        }
    }

    /**
     * @param World $world
     *
     * @return Position|null
     */
    protected function findLocation(World $world): ?Position {
        $setup = LevelManager::getSetup();
        $xz1 = explode(":", $setup->getNested("meteor-bounds.xz1"));
        $xz2 = explode(":", $setup->getNested("meteor-bounds.xz2"));
        $minX = (int)$xz1[0];
        $maxX = (int)$xz2[0];
        $minZ = (int)$xz1[1];
        $maxZ = (int)$xz2[1];
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
        $entity = MeteorBaseEntity::create(AnimationManager::METEOR_ID, Position::fromObject($position->add($coords[mt_rand(0, 1)], 45, $coords[mt_rand(0, 1)]), $position->getWorld()));
        $entity->setScale(3.0);
        $entity->initialize($position, 0.15, true, 0, function(MeteorBaseEntity $entity) use($position): void {
            $world = $entity->getWorld();
            if($world !== null) {
                $bb = $entity->getBoundingBox()->expandedCopy(12, 12, 12);
                foreach($world->getNearbyEntities($bb) as $e) {
                    if($e instanceof NexusPlayer) {
                        $ev = new EntityDamageByEntityEvent($entity, $e, EntityDamageEvent::CAUSE_ENTITY_EXPLOSION, 4);
                        $e->attack($ev);
                    }
                }
                $world->addParticle($entity->getPosition(), new HugeExplodeSeedParticle());
                $world->addSound($entity->getPosition(), new ExplodeSound());
                $x = $position->getX();
                $y = $position->getY();
                $z = $position->getZ();
                $blocks = [];
                for($i = $x - 11; $i <= $x + 11; ++$i) {
                    for($j = $z - 11; $j <= $z + 11; ++$j) {
                        $location = LevelUtils::getSafeSpawn($world, new Vector3($i, 1, $j), $y + 6);
                        $blocks[] = $location;
                    }
                }
                foreach($blocks as $block) {
                    if($world->getBlock($block)->getId() == BlockLegacyIds::AIR) {
                        $coral = VanillaBlocks::CORAL_BLOCK()->setCoralType(CoralType::BRAIN())->setDead(true);
                        $world->setBlock($block, $coral);
                        $tile = new Meteor($world, $block);
                        $world->addTile($tile);
                    }
                }
            }
        });
    }

    /**
     * @return int
     */
    public function getTimeLeft(): int {
        return $this->spawnRate;
    }
}
