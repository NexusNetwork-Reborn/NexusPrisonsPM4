<?php
declare(strict_types=1);

namespace core\game\combat\merchants;

use core\game\item\ItemManager;
use core\level\FakeChunkLoader;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Location;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;

class MerchantShop {

    /** @var Position */
    private $merchantSpawn;

    /** @var Block */
    private $ore;

    /** @var Position[] */
    private $spots;

    /** @var BodyGuard[] */
    private $tempGuards = [];

    /** @var OreMerchant */
    private $tempMerchant = null;

    /**
     * MerchantShop constructor.
     *
     * @param Position $merchantSpawn
     * @param Block $ore
     * @param array $spots
     */
    public function __construct(Position $merchantSpawn, Block $ore, array $spots) {
        $this->merchantSpawn = $merchantSpawn;
        $x = $merchantSpawn->getFloorX() >> 4;
        $z = $merchantSpawn->getFloorZ() >> 4;
        $merchantSpawn->getWorld()->registerChunkLoader(new FakeChunkLoader($x, $z), $x, $z);
        $this->ore = $ore;
        $this->spots = $spots;
    }

    /**
     * @param int $id
     * @param bool $heroic
     */
    public function spawnShop(int $id, bool $heroic = false): void {
        $guards = [];
        $world = $this->merchantSpawn->getWorld();
        $spots = $this->spots;
        if (!$heroic) {
            $spots = [
                array_shift($spots),
                end($spots)
            ];
        }
        foreach ($spots as $spot) {
            $guards[] = BodyGuard::create(Location::fromObject($spot, $world, 0, 0), $this->ore);
        }
        foreach ($guards as $guard) {
            $guard->spawnToAll();
        }
        $merchant = OreMerchant::create(Location::fromObject($this->merchantSpawn, $world, 0, 0), $this->ore, $id, $guards);
        $merchant->spawnToAll();
        $color = ItemManager::getColorByOre($this->ore);
        $x = $this->merchantSpawn->getFloorX();
        $y = $this->merchantSpawn->getFloorY();
        $z = $this->merchantSpawn->getFloorZ();
        $this->resetTempData();
        $this->tempGuards = $guards;
        $this->tempMerchant = $merchant;
        if ($heroic) {
            $heroic = TextFormat::BOLD . TextFormat::RED . "Heroic ";
        } else {
            $heroic = "";
        }
        Server::getInstance()->broadcastMessage(TextFormat::GOLD . TextFormat::BOLD . "(!)" . TextFormat::RESET . TextFormat::GOLD . " A " . $heroic . TextFormat::RESET . $color . $this->ore->getName() . " Merchant" . TextFormat::GOLD . " traveled to " . TextFormat::RESET . TextFormat::WHITE . "$x" . TextFormat::GRAY . "x, " . TextFormat::WHITE . "$y" . TextFormat::GRAY . "y, " . TextFormat::WHITE . "$z" . TextFormat::GRAY . "z");
    }

    /**
     * @return Position
     */
    public function getMerchantSpawn(): Position {
        return $this->merchantSpawn;
    }

    /**
     * @return Block
     */
    public function getOre(): Block {
        return $this->ore;
    }

    /**
     * @return Position[]
     */
    public function getSpots(): array {
        return $this->spots;
    }

    /**
     * @return null|OreMerchant
     */
    public function getTempMerchant(): ?OreMerchant {
        return $this->tempMerchant;
    }

    /**
     * @return bool
     */
    public function resetTempData(): bool {
        if ($this->tempMerchant !== null) {
            if (!$this->tempMerchant->isClosed()) {
                if ($this->tempMerchant->isActive()) {
                    return false;
                } else {
                    if (!$this->tempMerchant->isClosed()) {
                        $this->tempMerchant->flagForDespawn();
                    }
                }
            }

            $this->tempMerchant = null;
        }
        if (count($this->tempGuards) > 0) {
            foreach ($this->tempGuards as $guard) {
                if (!$guard->isClosed()) {
                    $guard->flagForDespawn();
                }
            }
            $this->tempGuards = [];
        }
        return true;
    }
}