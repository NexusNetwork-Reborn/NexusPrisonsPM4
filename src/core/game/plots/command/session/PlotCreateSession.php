<?php

namespace core\game\plots\command\session;

use core\game\plots\command\forms\PlotCreateConfirmationForm;
use core\player\NexusPlayer;
use pocketmine\world\Position;

class PlotCreateSession {

    /** @var null|Position */
    private $firstPosition = null;

    /** @var null|Position */
    private $secondPosition = null;

    /** @var null|Position */
    private $spawn = null;

    /** @var bool */
    private $confirmed = false;

    /**
     * @return Position|null
     */
    public function getFirstPosition(): ?Position {
        return $this->firstPosition;
    }

    /**
     * @param Position|null $firstPosition
     */
    public function setFirstPosition(?Position $firstPosition): void {
        $this->firstPosition = $firstPosition;
    }

    /**
     * @return Position|null
     */
    public function getSecondPosition(): ?Position {
        return $this->secondPosition;
    }

    /**
     * @param Position|null $secondPosition
     */
    public function setSecondPosition(?Position $secondPosition): void {
        $this->secondPosition = $secondPosition;
    }

    /**
     * @return Position|null
     */
    public function getSpawn(): ?Position {
        return $this->spawn;
    }

    /**
     * @param Position|null $spawn
     */
    public function setSpawn(?Position $spawn): void {
        $this->spawn = $spawn;
    }

    /**
     * @param bool $confirmed
     */
    public function setConfirmed(bool $confirmed): void {
        $this->confirmed = $confirmed;
    }

    /**
     * @return bool
     */
    public function isConfirmed(): bool {
        return $this->confirmed;
    }

    /**
     * @param NexusPlayer $player
     */
    public function checkConfirmation(NexusPlayer $player): void {
        if($this->firstPosition !== null) {
            if($this->secondPosition !== null) {
                if($this->spawn !== null) {
                    $player->sendForm(new PlotCreateConfirmationForm($this));
                }
            }
        }
    }
}