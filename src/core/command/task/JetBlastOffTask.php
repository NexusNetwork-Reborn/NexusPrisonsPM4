<?php

declare(strict_types = 1);

namespace core\command\task;

use core\player\NexusPlayer;
use libs\utils\Task;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\LavaParticle;
use pocketmine\world\sound\FizzSound;

class JetBlastOffTask extends Task {

    /** @var NexusPlayer */
    private $player;

    /** @var int */
    private $time = 3;

    /**
     * JetBlastOffTask constructor.
     *
     * @param NexusPlayer $player
     */
    public function __construct(NexusPlayer $player) {
        $this->player = $player;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(): void {
        if($this->player === null or $this->player->isClosed()) {
            $this->cancel();
            return;
        }
        if($this->time > 0) {
            $level = $this->player->getWorld();
            $position = $this->player->getPosition();
            for($i = 0; $i < 3; $i++) {
                $level->addParticle($position, new LavaParticle());
            }
            $level->addSound($position, new FizzSound());
            $this->player->sendTitle(TextFormat::YELLOW . TextFormat::BOLD . "$this->time", TextFormat::YELLOW . "...");
            $this->time--;
            return;
        }
        $this->player->sendTitle(TextFormat::GOLD . TextFormat::BOLD . "BLASTOFF", TextFormat::GOLD . "Woohoo!");
        $vector = $this->player->getDirectionVector();
        $this->player->setMotion(new Vector3($vector->x * 0.2 , 4.1, $vector->z * 0.2));
        $this->player->setUsingJet();
        $this->cancel();
        return;
    }
}
