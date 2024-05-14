<?php

namespace core\player\task;

use core\Nexus;
use core\player\NexusPlayer;
use core\player\rank\Rank;
use libs\utils\Task;
use pocketmine\color\Color;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\DustParticle;
use pocketmine\world\sound\FizzSound;

class JetHeartbeatTask extends Task {

    /** @var Nexus */
    private $core;

    /** @var int */
    private $runs = 0;

    /**
     * JetParticleHeartbeatTask constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(): void {
        $this->runs++;
        /** @var NexusPlayer $player */
        foreach($this->core->getServer()->getOnlinePlayers() as $player) {
            if(!$player->isUsingJet()) {
                continue;
            }
            if($player->getLastJet() === 0) {
                continue;
            }
            if(4 >= (time() - $player->getLastJet())) {
                continue;
            }
            if((13 <= (time() - $player->getLastJet())) and $player->isOnGround()) {
                if($player->isUsingJet()) {
                    $player->setUsingJet(false);
                }
                continue;
            }
            $level = $player->getWorld();
            if($level !== null) {
                $position = $player->getPosition();
                $cx = $position->getX();
                $cy = $position->getY();
                $cz = $position->getZ();
                $radius = 0.5;
                $vector = $player->getDirectionVector();
                if(13 <= (time() - $player->getLastJet())) {
                    if(!$player->isOnGround()) {
                        $player->resetFallDistance();
                        $player->sendAlert(TextFormat::RED . TextFormat::BOLD . "Your jetpack has ran out of fuel!", 30);
                        $player->setMotion(new Vector3($vector->x * 1, -0.8, $vector->z * 1));
                        if($this->runs % 5 == 0) {
                            $level->addSound($position, new FizzSound());
                        }
                    }
                }
                else {
                    if($player->isSneaking()) {
                        $player->resetFallDistance();
                        $player->setMotion(new Vector3($vector->x * 1, -0.6, $vector->z * 1));
                    }
                    else {
                        $player->resetFallDistance();
                        $player->setMotion(new Vector3($vector->x * 1, 0.6, $vector->z * 1));
                    }
                }
                if($this->runs % 5 == 0) {
                    for($i = 0; $i < 11; $i += 1.1) {
                        $x = $cx + ($radius * cos($i));
                        $z = $cz + ($radius * sin($i));
                        $pos = new Vector3($x, $cy, $z);
                        if($player->getDataSession()->getRank()->getIdentifier() >= Rank::PRESIDENT) {
                            $level->addParticle($pos, new DustParticle(new Color(mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255))));
                        }
                        else {
                            $level->addParticle($pos, new DustParticle(new Color(255, 255, 255)));
                        }
                    }
                }
            }
        }
    }
}