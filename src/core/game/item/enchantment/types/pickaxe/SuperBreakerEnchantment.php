<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\pickaxe;

use core\game\item\enchantment\Enchantment;
use core\Nexus;
use core\player\NexusPlayer;
use libs\utils\Task;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\math\Vector3;
use pocketmine\world\particle\FlameParticle;
use pocketmine\world\Position;
use pocketmine\world\sound\BlazeShootSound;

class SuperBreakerEnchantment extends Enchantment {

    /**
     * SuperBreakerEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::SUPER_BREAKER, "Super Breaker", self::ELITE, "Chance to gain an insane mining speed boost for a short period of time.", self::BREAK, self::SLOT_PICKAXE, 5);
        $this->callable = function(BlockBreakEvent $event, int $level) {
            $player = $event->getPlayer();
            if(!$player instanceof NexusPlayer) {
                return;
            }
            if($player->getCESession()->hasExplode()) {
                return;
            }
            if($player->getCESession()->hasSuperBreaker()) {
                $event->setInstaBreak(true);
                return;
            }
            $random = mt_rand(1, 300);
            $chance = $level * $player->getCESession()->getItemLuckModifier();
            $distance = $player->getPosition()->distance($event->getBlock()->getPosition());
            if($chance >= $random) {
                $directionVector = $player->getDirectionVector()->multiply($distance);
                $position = Position::fromObject($player->getPosition()->add($directionVector->x, $directionVector->y + $player->getEyeHeight() + 1, $directionVector->z), $player->getWorld());
                $cx = $position->getX();
                $cy = $position->getY();
                $cz = $position->getZ();
                $radius = 0.5;
                for($i = 0; $i < 11; $i += 1.1) {
                    $x = $cx + ($radius * cos($i));
                    $z = $cz + ($radius * sin($i));
                    $pos = new Vector3($x, $cy, $z);
                    $position->getWorld()->addParticle($pos, new FlameParticle(), [$player]);
                }
                $position->getWorld()->addSound($position, new BlazeShootSound(), [$player]);
                $player->getCESession()->setSuperBreaker(true);
                $event->setInstaBreak(true);
                Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new class($player) extends Task {

                    /** @var NexusPlayer */
                    private $player;

                    /**
                     *  constructor.
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
                        if($this->player->isOnline()) {
                            $this->player->getCESession()->setSuperBreaker(false);
                        }
                        $this->cancel();
                    }
                }, 20 * $level);
            }
        };
    }
}