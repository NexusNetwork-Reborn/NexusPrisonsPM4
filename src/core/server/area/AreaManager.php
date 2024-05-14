<?php
declare(strict_types=1);

namespace core\server\area;

use core\game\plots\PlotManager;
use core\level\LevelManager;
use core\Nexus;
use pocketmine\world\Position;

class AreaManager {

    /** @var Nexus */
    private $core;

    /** @var Area[] */
    private $areas = [];

    /**
     * AreaManager constructor.
     *
     * @param Nexus $core
     *
     * @throws AreaException
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $core->getServer()->getPluginManager()->registerEvents(new AreaListener($core), $core);
        $this->init();
    }

    /**
     * @throws AreaException
     */
    public function init(): void {
        $world = $this->core->getServer()->getWorldManager()->getDefaultWorld();
        foreach (LevelManager::getSetup()->get("areas") as $name => $data) {
            $this->addArea(new Area($name, LevelManager::stringToPosition($data["pos1"], $world), LevelManager::stringToPosition($data["pos2"], $world), $data["pvp"], $data["edit"]));
        }
//        $this->addArea(new Area("Spawn", new Position(-60, 177, -238, $world), new Position(-176, 1000, -117, $world), false, false));
//        $this->addArea(new Area("Coal", new Position(-253, 242, -176, $world), new Position(-232, 256, -154, $world), false, false));
//        $this->addArea(new Area("Iron", new Position(24, 248, -181, $world), new Position(47, 236, -205, $world), false, false));
//        $this->addArea(new Area("Lapis", new Position(293, 240, 105, $world), new Position(269, 223, 129, $world), false, false));
//        $this->addArea(new Area("Redstone", new Position(262, 243, -76, $world), new Position(237, 228, -49, $world), false, false));
//        $this->addArea(new Area("Gold", new Position(115, 245, 77, $world), new Position(92, 230, 101, $world), false, false));
//        $this->addArea(new Area("Diamond", new Position(-92, 234, 293, $world), new Position(-117, 250, 318, $world), false, false));
//        $this->addArea(new Area("Emerald", new Position(89, 244, 267, $world), new Position(65, 259, 292, $world), false, false));
        $world = $this->core->getServer()->getWorldManager()->getWorldByName("lounge");
        $this->addArea(new Area("Lounge", new Position(-10000, 0, -10000, $world), new Position(10000, 1000, 10000, $world), false, true));
        $world = $this->core->getServer()->getWorldManager()->getWorldByName("executive");
        $this->addArea(new Area("Executive Mine", new Position(-10000, 0, -10000, $world), new Position(10000, 1000, 10000, $world), false, true));
    }

    /**
     * @param Position $position
     *
     * @return bool
     */
    public function canDamage(Position $position): bool {
        if(PlotManager::isPlotWorld($position->getWorld())) {
            return false;
        }
        $areas = Nexus::getInstance()->getServerManager()->getAreaManager()->getAreasInPosition($position);
        if($areas !== null) {
            foreach($areas as $area) {
                if($area->getPvpFlag() === false) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @param Area $area
     */
    public function addArea(Area $area): void {
        $this->areas[] = $area;
    }

    /**
     * @param Position $position
     *
     * @return Area[]|null
     */
    public function getAreasInPosition(Position $position): ?array {
        $areas = $this->getAreas();
        $areasInPosition = [];
        foreach($areas as $area) {
            if($area->isPositionInside($position) === true) {
                $areasInPosition[] = $area;
            }
        }
        if(empty($areasInPosition)) {
            return null;
        }
        return $areasInPosition;
    }

    /**
     * @return Area[]
     */
    public function getAreas(): array {
        return $this->areas;
    }
}