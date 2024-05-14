<?php
declare(strict_types=1);

namespace core\game\item\trinket\entity;

use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\SplashPotion;
use pocketmine\item\PotionType;
use pocketmine\nbt\tag\CompoundTag;

class ResistancePotion extends SplashPotion {

    public function __construct(Location $location, ?Entity $shootingEntity, ?CompoundTag $nbt = null) {
        parent::__construct($location, $shootingEntity, PotionType::HARMING(), $nbt);
    }

    /**
     * @return EffectInstance[]
     */
    public function getPotionEffects(): array {
        return [
            new EffectInstance(VanillaEffects::RESISTANCE(), 600, 1)
        ];
    }
}
