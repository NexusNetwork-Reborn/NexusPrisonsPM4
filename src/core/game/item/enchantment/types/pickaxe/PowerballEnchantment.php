<?php

declare(strict_types = 1);

namespace core\game\item\enchantment\types\pickaxe;

use core\game\item\enchantment\Enchantment;
use core\game\item\types\vanilla\Pickaxe;
use core\level\entity\types\Powerball;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\entity\Location;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\BlazeShootSound;

class PowerballEnchantment extends Enchantment {

    /**
     * PowerballEnchantment constructor.
     */
    public function __construct() {
        parent::__construct(self::POWERBALL, "Powerball", self::ELITE, "Shift click to launch a powerful ball that harvests ores.", self::INTERACT, self::SLOT_PICKAXE, 3);
        $this->callable = function(PlayerItemUseEvent $event, int $level) {
            /** @var NexusPlayer $owner */
            $owner = $event->getPlayer();
            if(!$owner->isSneaking()) {
                return;
            }
            if (in_array($owner->getWorld()->getFolderName(), [
                "citizen",
                "merchant",
                "king"
            ])){
                return;
            }
            if($owner->getCooldownLeft(60 - ($level * 7), NexusPlayer::POWERBALL) > 0) {
                $owner->sendTranslatedMessage("actionCooldown", [
                    "amount" => TextFormat::RED . $owner->getCooldownLeft(60 - ($level * 7), NexusPlayer::POWERBALL)
                ]);
                return;
            }
            $owner->setCooldown(NexusPlayer::POWERBALL);
            $owner->getWorld()->addSound($owner->getPosition(), new BlazeShootSound(), [$owner]);
            $owner->getCESession()->setPowerball(true);
            $max = 0;
            for($i = 1; $i <= ($level + mt_rand(0, 1 + $level)); $i++) {
                $location = $owner->getEyePos();
                $powerball = new Powerball(new Location($location->x, $location->y, $location->z, $owner->getWorld(), 0, 0));
                $powerball->spawnToAll();
                $time = mt_rand(40 + ($level * 20), 40 + ($level * 30));
                $powerball->setLifeTime($time);
                if($time > $max) {
                    $max = $time;
                }
                $powerball->setOwningEntity($owner);
                $powerball->setDirectionVector($owner->getDirectionVector()->add(mt_rand(-5, 5) / 10, 0, mt_rand(-5, 5) / 10));
            }
            Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new class($owner) extends Task {

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
                        $this->player->getCESession()->setPowerball(false);
                    }
                }
            }, $max);
        };
    }
}