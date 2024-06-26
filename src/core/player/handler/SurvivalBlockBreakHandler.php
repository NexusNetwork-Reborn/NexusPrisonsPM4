<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace core\player\handler;

use core\Nexus;
use core\player\NexusPlayer;
use core\player\PlayerManager;
use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\BlockEventPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\world\particle\BlockPunchParticle;
use pocketmine\world\sound\BlockPunchSound;
use function abs;

final class SurvivalBlockBreakHandler{

    public const DEFAULT_FX_INTERVAL_TICKS = 5;

    /** @var NexusPlayer */
    private $player;

    /** @var Vector3 */
    private $blockPos;

    /** @var Block */
    private $block;

    /** @var int */
    private $targetedFace;

    /** @var int */
    private $fxTicker = 0;

    /** @var int */
    private $fxTickInterval;

    /** @var int */
    private $maxPlayerDistance;

    /** @var float */
    private $breakSpeed;

    /** @var float */
    private $breakProgress = 0;

    private function __construct(NexusPlayer $player, Vector3 $blockPos, Block $block, int $targetedFace, int $maxPlayerDistance, int $fxTickInterval = self::DEFAULT_FX_INTERVAL_TICKS) {
        $this->player = $player;
        $this->blockPos = $blockPos;
        $this->block = $block;
        $this->targetedFace = $targetedFace;
        $this->fxTickInterval = $fxTickInterval;
        $this->maxPlayerDistance = $maxPlayerDistance;
        $this->breakSpeed = $this->calculateBreakProgressPerTick();
//        $progress = $this->player->getBlockBreakProgress($this->blockPos);
//        $start = true;
//        if($progress !== null) {
//            if($this->block == $progress[0]) {
//                $this->breakProgress = $progress[1];
//                $start = false;
//            }
//            $this->player->removeBlockBreakProgress($this->blockPos);
//        }
        if($this->breakSpeed > 0 /*&& $start*/) {
            $this->player->getWorld()->broadcastPacketToViewers(
                $this->blockPos,
                LevelEventPacket::create(LevelEvent::BLOCK_START_BREAK, (int)(65535 * $this->breakSpeed), $this->blockPos)
            );
        }
    }

    public static function createIfNecessary(NexusPlayer $player, Vector3 $blockPos, Block $block, int $targetedFace, int $maxPlayerDistance, int $fxTickInterval = self::DEFAULT_FX_INTERVAL_TICKS): ?self {
        $breakInfo = $block->getBreakInfo();
        if(!$breakInfo->breaksInstantly()) {
            return new self($player, $blockPos, $block, $targetedFace, $maxPlayerDistance, $fxTickInterval);
        }
        return null;
    }

    /**
     * Returns the calculated break speed as percentage progress per game tick.
     */
    private function calculateBreakProgressPerTick(): float {
        if(!$this->block->getBreakInfo()->isBreakable()) {
            return 0.0;
        }
        //TODO: improve this to take stuff like swimming, ladders, enchanted tools into account, fix wrong tool break time calculations for bad tools (pmmp/PocketMine-MP#211)
        $breakTimePerTick = PlayerManager::calculateBlockBreakTime($this->player, $this->block);
        if($breakTimePerTick > 0) {
            return 1 / $breakTimePerTick;
        }
        return 1;
    }

    public function update(): bool {
        if($this->player->getPosition()->distanceSquared($this->blockPos->add(0.5, 0.5, 0.5)) > $this->maxPlayerDistance ** 2 || $this->block->getId() == BlockLegacyIds::BEDROCK) {
            return false;
        }
        $newBreakSpeed = $this->calculateBreakProgressPerTick();
        if(abs($newBreakSpeed - $this->breakSpeed) > 0.0001) {
            $this->breakSpeed = $newBreakSpeed;
            //TODO: sync with client
        }
        $this->breakProgress += $this->breakSpeed;
        if(($this->fxTicker++ % $this->fxTickInterval) === 0 and $this->breakProgress < 1) {
            $speed = 65535 * $this->breakSpeed;
            if($this->player->getWorld()->getBlock($this->blockPos)->getId() == BlockLegacyIds::BEDROCK) {
                return false;
            }
            if($this->player->getBlockBreakFactor() !== null && $this->player->getBlockBreakFactor() > 0) {
                $speed *= (1 / $this->player->getBlockBreakFactor());
            }
            $this->player->getWorld()->broadcastPacketToViewers(
                $this->blockPos,
                LevelEventPacket::create(3602, (int)($speed), $this->blockPos)
            );
            $this->player->getWorld()->addSound($this->blockPos, new BlockPunchSound($this->block));
            $this->player->broadcastAnimation(new ArmSwingAnimation($this->player), $this->player->getViewers());
        }
        return $this->breakProgress < 1;
    }

    public function getBlockPos(): Vector3 {
        return $this->blockPos;
    }

    public function getTargetedFace(): int {
        return $this->targetedFace;
    }

    public function setTargetedFace(int $face): void {
        Facing::validate($face);
        $this->targetedFace = $face;
    }

    public function getBreakSpeed(): float {
        return $this->breakSpeed;
    }

    public function getBreakProgress(): float {
        return $this->breakProgress;
    }

    //public const ORE_IDS = [14, 15, 16, 21, 56, 73, 129, 57, 41, 42, 173, 22, 152, 133];

    public function __destruct() {
        if($this->player->getWorld()->isInLoadedTerrain($this->blockPos)) {
//            $this->player->setBlockBreakProgress($this->blockPos, $this->block, $this->breakProgress);
//            $areaManager = Nexus::getInstance()->getServerManager()->getAreaManager();
//            $areas = $areaManager->getAreasInPosition($this->block->getPosition());
//            if($areas !== null) {
//                foreach($areas as $area) {
//                    if(!$area->getEditFlag()) {
//                        $this->player->getWorld()->broadcastPacketToViewers(
//                            $this->blockPos,
//                            LevelEventPacket::create(LevelEvent::BLOCK_STOP_BREAK, 0, $this->blockPos)
//                        );
//                        return;
//                    }
//                }
//            }
            //if(!in_array($this->block->getId(), self::ORE_IDS)) {
                $this->player->getWorld()->broadcastPacketToViewers(
                    $this->blockPos,
                    LevelEventPacket::create(LevelEvent::BLOCK_STOP_BREAK, (int)(65535 * $this->breakSpeed), $this->blockPos)
                );
            //}
        }
    }
}

