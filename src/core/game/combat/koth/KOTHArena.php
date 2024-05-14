<?php

declare(strict_types=1);

namespace core\game\combat\koth;

use core\game\combat\koth\event\KOTHCaptureEvent;
use core\game\item\types\custom\KOTHLootbag;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use libs\utils\Utils;
use pocketmine\world\Position;
use pocketmine\utils\TextFormat;
use Ramsey\Uuid\Uuid;

class KOTHArena
{

    /** @var Position */
    private $firstPosition;

    /** @var Position */
    private $secondPosition;

    /** @var int[] */
    private $progresses = [];

    /** @var int */
    private $objectiveTime;

    /** @var bool */
    private $started = false;

    /**
     * KOTHArena constructor.
     *
     * @param string $name
     * @param Position $firstPosition
     * @param Position $secondPosition
     * @param int $objectiveTime
     *
     * @throws KOTHException
     */
    public function __construct(string $name, Position $firstPosition, Position $secondPosition, int $objectiveTime)
    {
        $this->firstPosition = $firstPosition;
        $this->secondPosition = $secondPosition;
        if ($firstPosition->getWorld() === null or $secondPosition->getWorld() === null) {
            throw new KOTHException("KOTH arena \"$name\" position levels are invalid.");
        }
        if ($firstPosition->getWorld()->getFolderName() !== $secondPosition->getWorld()->getFolderName()) {
            throw new KOTHException("KOTH arena \"$name\" position levels are not the same.");
        }
        $this->objectiveTime = $objectiveTime;
    }

    /**
     * @throws TranslationException
     */
    public function tick(): void
    {
        arsort($this->progresses);

        $top_five = array_filter(array_slice($this->progresses, 0, 5, true), function (string $uuid): bool {
            return Nexus::getInstance()->getServer()->getPlayerByUUID(Uuid::fromString($uuid)) !== null;
        }, ARRAY_FILTER_USE_KEY);

        $top_five_formatted = implode(TextFormat::EOL, array_map(function (int $progress, string $uuid): string {
            $player = Nexus::getInstance()->getServer()->getPlayerByUUID(Uuid::fromString($uuid));
            $progress = round($progress / $this->objectiveTime * 100, 2);
            return Utils::centerAlignText(TextFormat::AQUA . $player->getName() . TextFormat::WHITE . " - " . TextFormat::GREEN . $progress . "%%", 50);
        }, $top_five, array_keys($top_five)));

        $tip = Utils::centerAlignText(TextFormat::BOLD . TextFormat::AQUA . "King of the Hill" . TextFormat::RESET . TextFormat::WHITE
                . " - " . TextFormat::GREEN . "Top Captures", 50) . TextFormat::EOL . TextFormat::EOL . $top_five_formatted;

        /** @var NexusPlayer $player */
        foreach ($this->firstPosition->getWorld()->getPlayers() as $player) {
            $player->sendTip($tip);
            if (!isset($this->progresses[$player->getUniqueId()->toString()])) {
                $this->progresses[$player->getUniqueId()->toString()] = 0;
                continue;
            }
            if ($this->isPositionInside($player->getPosition()) and (!$player->isFlying())) {
                ++$this->progresses[$player->getUniqueId()->toString()];
                $percentage = round(($this->progresses[$player->getUniqueId()->toString()] / $this->objectiveTime) * 100);
                $player->sendTitle(TextFormat::BOLD . TextFormat::GREEN . "Capturing" . str_repeat(".", ($this->objectiveTime - $this->progresses[$player->getUniqueId()->toString()]) % 4), "$percentage%");
            }
            if ($this->progresses[$player->getUniqueId()->toString()] >= $this->objectiveTime) {
                $ev = new KOTHCaptureEvent($player);
                $ev->call();
                $player->getInventory()->addItem((new KOTHLootbag())->toItem()->setCount(1));
                Nexus::getInstance()->getGameManager()->getCombatManager()->endKOTHGame();
                Nexus::getInstance()->getServer()->broadcastMessage(Translation::getMessage("kothEnd", [
                    "player" => TextFormat::YELLOW . $player->getName()
                ]));
                $player->sendMessage(Translation::getMessage("kothReward"));
                $this->progresses = [];
                $this->started = false;
                return;
            }
        }
    }

    /**
     * @return int
     */
    public function getObjectiveTime(): int
    {
        return $this->objectiveTime;
    }

    /**
     * @return Position
     */
    public function getFirstPosition(): Position
    {
        return $this->firstPosition;
    }

    /**
     * @return Position
     */
    public function getSecondPosition(): Position
    {
        return $this->secondPosition;
    }

    /**
     * @param Position $position
     *
     * @return bool
     */
    public function isPositionInside(Position $position): bool
    {
        $level = $position->getWorld();
        if ($level === null) {
            return false;
        }
        $position = $position->floor();
        $firstPosition = $this->firstPosition;
        $secondPosition = $this->secondPosition;
        $minX = min($firstPosition->getX(), $secondPosition->getX());
        $maxX = max($firstPosition->getX(), $secondPosition->getX());
        $minY = min($firstPosition->getY(), $secondPosition->getY());
        $maxY = max($firstPosition->getY(), $secondPosition->getY());
        $minZ = min($firstPosition->getZ(), $secondPosition->getZ());
        $maxZ = max($firstPosition->getZ(), $secondPosition->getZ());
        return $minX <= $position->getX() and $maxX >= $position->getFloorX() and
            $minY <= $position->getY() and $maxY >= $position->getFloorY() and
            $minZ <= $position->getZ() and $maxZ >= $position->getFloorZ() and
            $this->firstPosition->getWorld()->getFolderName() === $level->getFolderName();
    }

    /**
     * @return bool
     */
    public function hasStarted(): bool
    {
        return $this->started;
    }

    /**
     * @param bool $started
     */
    public function setStarted(bool $started): void
    {
        $this->started = $started;
    }
}