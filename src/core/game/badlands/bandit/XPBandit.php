<?php

namespace core\game\badlands\bandit;

use pocketmine\entity\EntitySizeInfo;

class XPBandit extends BaseBandit {

    public function getName(): string
    {
        return "Xp Bandit";
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(2, 1);
    }
}
