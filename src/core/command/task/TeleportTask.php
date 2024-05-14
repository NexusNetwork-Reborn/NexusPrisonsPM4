<?php

declare(strict_types = 1);

namespace core\command\task;

use core\player\NexusPlayer;
use core\player\rank\Rank;
use libs\utils\Task;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\permission\DefaultPermissions;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use pocketmine\world\sound\EndermanTeleportSound;

class TeleportTask extends Task {

    /** @var NexusPlayer */
    private $player;

    /** @var Position */
    private $position;

    /** @var Position */
    private $originalLocation;

    /** @var int */
    private $time;

    /** @var int */
    private $maxTime;

    /**
     * TeleportTask constructor.
     *
     * @param NexusPlayer $player
     * @param Position $position
     * @param int $time
     */
    public function __construct(NexusPlayer $player, Position $position, int $time) {
        $this->player = $player;
        if($player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            $player->setTakeFallDamage(false);
            $player->teleport($position);
            $player->getWorld()->addSound($player->getPosition(), new EndermanTeleportSound());
            $this->player = null;
            return;
        }
        $areas = $player->getCore()->getServerManager()->getAreaManager()->getAreasInPosition($player->getPosition());
        if($areas !== null) {
            foreach($areas as $area) {
                if($area->getPvpFlag() === false) {
                    $player->setTakeFallDamage(false);
                    $player->teleport($position);
                    $player->getWorld()->addSound($player->getPosition(), new EndermanTeleportSound());
                    $this->player = null;
                    return;
                }
            }
        }
        if($player->getDataSession()->getRank()->getIdentifier() < Rank::EMPEROR_HEROIC) {
            if($player->getCooldownLeft(180, NexusPlayer::TELEPORT) > 0) {
                $player->sendTranslatedMessage("actionCooldown", [
                    "amount" => TextFormat::RED . $player->getCooldownLeft(180, NexusPlayer::TELEPORT)
                ]);
                $this->player = null;
                return;
            }
        }
        $this->player->getEffects()->add(new EffectInstance(VanillaEffects::NAUSEA(), $time * 20, 1, false));
        $this->position = $position;
        $this->originalLocation = $player->getPosition();
        $this->time = $time;
        $this->maxTime = $time;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(): void {
        if($this->player === null or $this->player->isClosed()) {
            $this->cancel();
            return;
        }
        if(!$this->player->getPosition()->floor()->equals($this->originalLocation->floor())) {
            $this->player->setTeleporting(false);
            $this->player->sendTitle(TextFormat::DARK_RED . TextFormat::BOLD . "Failed to teleport", TextFormat::GRAY . "You must stand still!");
            $this->cancel();
            return;
        }
        if($this->time >= 0) {
            $this->player->sendTitle(TextFormat::GOLD . TextFormat::BOLD . "Teleporting in", TextFormat::GRAY . "$this->time seconds" . str_repeat(".", ($this->maxTime - $this->time) % 4));
            $this->time--;
            return;
        }
        $this->player->setTeleporting(false);
        $this->player->teleport($this->position);
        $this->player->getWorld()->addSound($this->player->getPosition(), new EndermanTeleportSound());
        $this->player->setTakeFallDamage(false);
        $this->player->setCooldown(NexusPlayer::TELEPORT);
        $this->cancel();
    }

    public function onCancel(): void {
        if($this->player !== null and (!$this->player->isClosed())) {
            $this->player->getEffects()->remove(VanillaEffects::NAUSEA());
        }
    }
}
