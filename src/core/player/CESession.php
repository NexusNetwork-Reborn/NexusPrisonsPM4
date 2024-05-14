<?php

namespace core\player;

use core\game\fund\FundManager;
use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\ItemManager;
use core\game\item\mask\Mask;
use core\game\item\sets\utils\SetUtils;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Axe;
use core\game\zone\ZoneManager;
use core\Nexus;
use core\translation\Translation;
use muqsit\invmenu\InvMenu;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\item\Bow;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Sword;
use pocketmine\item\Tool;
use pocketmine\permission\DefaultPermissions;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;

class CESession {

    /** @var NexusPlayer */
    private $owner;

    /** @var null|InvMenu */
    private $lastDeath;

    /** @var array */
    private $activeArmorEnchantments = [];

    /** @var array */
    private $activeHeldItemEnchantments = [];

    /** @var float */
    private $armorLuckModifier = 1;

    /** @var float */
    private $itemLuckModifier = 1;

    /** @var bool */
    private $superBreaker = false;

    /** @var bool */
    private $explode = false;

    /** @var bool */
    private $powerball = false;

    /** @var bool */
    private $hidingHealth = false;

    /** @var bool */
    private $dominated = false;

    /** @var bool */
    private $silenced = false;

    /** @var bool */
    private $bleeding = false;

    /** @var bool */
    private $weakened = false;

    /** @var bool */
    private $trapped = false;

    /** @var bool */
    private $trinketBlocked = false;

    /** @var bool */
    private $cursed = false;

    /** @var bool */
    private $hexCursed = false;

    /** @var float */
    private $aegis = 1.0;

    /** @var int */
    private $lastAdrenaline = 0;

    /** @var int */
    private $lastPainkiller = 0;

    /** @var int */
    private $momentum = 0;

    /** @var int */
    private $frenzyHits = 0;

    /** @var int */
    private $lastFrenzyHit = 0;

    /** @var int */
    private $bypassHits = 0;

    /** @var int */
    private $lastBypassHit = 0;

    /** @var int */
    private $thousandCutStacks = 0;

    /** @var int */
    private $lastThousandCutHit = 0;

    /** @var int */
    private $whirlwindHits = 0;

    private $lastWhirlwindHit = 0;

    /** @var null|Position */
    private $offensiveProcLocation = null;

    /** @var int */
    private $lastSystemReboot = 0;

    /**
     * CESession constructor.
     *
     * @param NexusPlayer $owner
     */
    public function __construct(NexusPlayer $owner) {
        $this->owner = $owner;
    }

    public function reset(): void {
        $this->setSuperBreaker(false);
        $this->setExplode(false);
        $this->setPowerball(false);
        $this->setHidingHealth(false);
        $this->setDominated(false);
        $this->setSilenced(false);
        $this->setBleeding(false);
        $this->setWeakened(false);
        $this->setTrapped(false);
        $this->setTrinketBlocked(false);
        $this->setCursed(false);
        $this->setHexCursed(false);
    }

    /**
     * @param InvMenu|null $lastDeath
     */
    public function setLastDeath(?InvMenu $lastDeath): void {
        $this->lastDeath = $lastDeath;
    }

    /**
     * @return InvMenu|null
     */
    public function getLastDeath(): ?InvMenu {
        return $this->lastDeath;
    }

    /**
     * @param NexusPlayer $owner
     */
    public function setOwner(NexusPlayer $owner): void {
        $this->owner = $owner;
    }

    /**
     * @return NexusPlayer
     */
    public function getOwner(): NexusPlayer {
        return $this->owner;
    }

    public function setActiveArmorEnchantments(): void {
        $this->activeArmorEnchantments = [];
        if($this->owner->isClosed()) {
            return;
        }
        $inventory = $this->owner->getArmorInventory();
        foreach($inventory->getContents(true) as $index => $item) {
            if($index === 0) {
                if($item instanceof Armor) {
                    $masks = $item->getMasks();
                    if(count($masks) > 1) {
                        $mask = "multi";
                    }
                    elseif(!empty($item->getMasks())) {
                        $mask = array_shift($masks);
                        $mask = strtolower(preg_replace('/\s+/', '_', $mask->getName()));
                    }
                    if(isset($mask)) {
                        $skin = Mask::getSkinWithMask($this->owner, $mask);
                        if($skin->getSkinId() !== $this->owner->getSkin()->getSkinId()) {
                            $this->owner->setSkin($skin);
                            $this->owner->sendSkin();
                        }
                    }
                }
                else {
                    $skin = $this->owner->getOriginalSkin();
                    if($skin->getSkinId() !== $this->owner->getSkin()->getSkinId()) {
                        $this->owner->setSkin($skin);
                        $this->owner->sendSkin();
                    }
                    continue;
                }
            }
            if(!$item->hasEnchantments()) {
                continue;
            }
            if(!ItemManager::canUseTool($this->owner, $item)) {
                $name = $item->hasCustomName() ? $item->getCustomName() : $item->getName();
                $level = ItemManager::getLevelToUseTool($item);
                $this->owner->sendAlert(Translation::RED . "You need to be Level $level to use $name", 10);
                continue;
            }
            if($item instanceof Armor && $item->getEnergyPrice() > 0) {
                $name = $item->hasCustomName() ? $item->getCustomName() : $item->getName();
                $this->owner->sendAlert(Translation::RED . "You need to unlock $name with cosmic energy first", 10);
                continue;
            }
            $limit = 1000;
            $zone = $this->owner->getZone();
            if($zone !== null) {
                if($item instanceof Armor and $item->getTierId() > $zone->getTierId()) {
                    $limit = ZoneManager::getArmorMaxLevel($zone->getTierId());
                }
            }
            foreach($item->getEnchantments() as $enchantment) {
                $type = $enchantment->getType();
                if(!$type instanceof Enchantment) {
                    continue;
                }
                if($limit <= 0) {
                    break;
                }
                $level = $enchantment->getLevel();
                $set = min($level, $limit);
                if(!$this->owner->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
                    $limit -= $set;
                }
                if($enchantment->getType()->getRarity() === Enchantment::EXECUTIVE) {
                    if(!Nexus::getInstance()->getGameManager()->getFundManager()->isUnlocked(FundManager::PHASE_SEVEN)) {
                        $this->owner->sendTranslatedAlert("fundDisabled", [
                            "feature" => TextFormat::RED . "Executive Enchantments"
                        ], 60);
                        continue;
                    }
                }
                if($enchantment->getType()->getRarity() === Enchantment::ENERGY) {
                    if(!Nexus::getInstance()->getGameManager()->getFundManager()->isUnlocked(FundManager::PHASE_EIGHT)) {
                        $this->owner->sendTranslatedAlert("fundDisabled", [
                            "feature" => TextFormat::RED . "Energy Enchantments"
                        ], 60);
                        continue;
                    }
                }
                if(isset($this->activeArmorEnchantments[$type->getEventType()][EnchantmentIdMap::getInstance()->toId($type)])) {
                    $this->activeArmorEnchantments[$type->getEventType()][$type->getRuntimeId()] = new EnchantmentInstance($enchantment->getType(), $this->activeArmorEnchantments[$type->getEventType()][EnchantmentIdMap::getInstance()->toId($enchantment->getType())]->getLevel() + $set);
                }
                $this->activeArmorEnchantments[$type->getEventType()][$type->getRuntimeId()] = new EnchantmentInstance($enchantment->getType(), $set);
            }
        }

        $lucky = $this->getEnchantmentLevelArmor(EnchantmentManager::getEnchantment(Enchantment::LUCKY));

        if($lucky > 0) {
            $this->armorLuckModifier = $lucky > EnchantmentManager::getEnchantment(Enchantment::LUCKY)->getMaxLevel() ? (EnchantmentManager::getEnchantment(Enchantment::LUCKY) * 0.8) : ($lucky * 0.8);
        } else {
            if(SetUtils::isWearingFullSet($this->owner, "santa")) $this->armorLuckModifier = 3.2;
        }

        $eternalLuck = $this->getEnchantmentLevelArmor(EnchantmentManager::getEnchantment(Enchantment::ETERNAL_LUCK));
        if($eternalLuck > 0) {
            $this->armorLuckModifier = $eternalLuck > EnchantmentManager::getEnchantment(Enchantment::ETERNAL_LUCK)->getMaxLevel() ? (EnchantmentManager::getEnchantment(Enchantment::ETERNAL_LUCK)->getMaxLevel() * 0.8) : ($eternalLuck * 0.8);
            $this->armorLuckModifier += 4;
        }

        if(!SetUtils::isWearingFullSet($this->owner, "ghost")) $this->owner->getEffects()->remove(VanillaEffects::SPEED());
        $this->owner->getEffects()->remove(VanillaEffects::JUMP_BOOST());
        $this->owner->getEffects()->remove(VanillaEffects::REGENERATION());

        if($this->silenced === true) {
            $this->armorLuckModifier /= 2;
        }
    }

    public function resetActiveHeldItemEnchantments(): void {
        $this->activeHeldItemEnchantments = [];
    }

    public function setActiveHeldItemEnchantments(): void {
        $this->activeHeldItemEnchantments = [];
        $this->itemLuckModifier = 1;
        $item = $this->owner->getInventory()->getItemInHand();
        if(!$item->hasEnchantments()) {
            return;
        }
        if(!ItemManager::canUseTool($this->owner, $item)) {
            $name = $item->hasCustomName() ? $item->getCustomName() : $item->getName();
            $level = ItemManager::getLevelToUseTool($item);
            $this->owner->sendAlert(Translation::RED . "You need to be Level $level to use $name", 10);
            return;
        }
        if($item instanceof Sword or $item instanceof Axe or $item instanceof Bow or $item instanceof Tool) {
            $limit = 1000;
            $zone = $this->owner->getZone();
            if($zone !== null) {
                if(($item instanceof Axe or $item instanceof Sword) and $item->getTierId() > $zone->getTierId()) {
                    $limit = ZoneManager::getWeaponMaxLevel($zone->getTierId());
                }
            }
            foreach($item->getEnchantments() as $enchantment) {
                $type = $enchantment->getType();
                if(!$type instanceof Enchantment) {
                    continue;
                }
                if($limit <= 0) {
                    break;
                }
                $level = $enchantment->getLevel();
                $set = min($level, $limit);
                if(!$this->owner->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
                    $limit -= $set;
                }
                if($enchantment->getType()->getRarity() === Enchantment::EXECUTIVE) {
                    if(!Nexus::getInstance()->getGameManager()->getFundManager()->isUnlocked(FundManager::PHASE_SEVEN)) {
                        $this->owner->sendTranslatedAlert("fundDisabled", [
                            "feature" => TextFormat::RED . "Executive Enchantments"
                        ], 60);
                        continue;
                    }
                }
                if($enchantment->getType()->getRarity() === Enchantment::ENERGY) {
                    if(!Nexus::getInstance()->getGameManager()->getFundManager()->isUnlocked(FundManager::PHASE_EIGHT)) {
                        $this->owner->sendTranslatedAlert("fundDisabled", [
                            "feature" => TextFormat::RED . "Energy Enchantments"
                        ], 60);
                        continue;
                    }
                }
                if(isset($this->activeHeldItemEnchantments[$type->getEventType()][$type->getRuntimeId()])) {
                    $this->activeHeldItemEnchantments[$type->getEventType()][$type->getRuntimeId()] = new EnchantmentInstance($enchantment->getType(), $this->activeHeldItemEnchantments[$type->getEventType()][$enchantment->getRuntimeId()]->getLevel() + $level);
                }
                $this->activeHeldItemEnchantments[$type->getEventType()][$type->getRuntimeId()] = new EnchantmentInstance($enchantment->getType(), $level);
            }
        }
        $lucky = $this->getEnchantmentLevelHeldItem(EnchantmentManager::getEnchantment(Enchantment::LUCKY));
        if($lucky > 0) {
            $this->itemLuckModifier += $lucky > EnchantmentManager::getEnchantment(Enchantment::LUCKY)->getMaxLevel() ? (EnchantmentManager::getEnchantment(Enchantment::LUCKY)->getMaxLevel() * 0.25) : ($lucky * 0.25);
        }
        $eternalLuck = $this->getEnchantmentLevelHeldItem(EnchantmentManager::getEnchantment(Enchantment::ETERNAL_LUCK));
        if($eternalLuck > 0) {
            $this->itemLuckModifier += $eternalLuck > EnchantmentManager::getEnchantment(Enchantment::ETERNAL_LUCK)->getMaxLevel() ? (EnchantmentManager::getEnchantment(Enchantment::ETERNAL_LUCK)->getMaxLevel() * 0.3) : ($eternalLuck * 0.3);
            $this->itemLuckModifier += 1.3;
        }
        if($this->hasAegis()) {
            $this->itemLuckModifier *= $this->aegis;
        }
    }

    /**
     * @return array
     */
    public function getActiveEnchantments(): array {
        $active = [];
        foreach($this->activeArmorEnchantments as $eventType => $enchantments) {
            foreach($enchantments as $id => $level) {
                $active[$eventType][$id] = $level;
            }
        }
        foreach($this->activeHeldItemEnchantments as $eventType => $enchantments) {
            foreach($enchantments as $id => $level) {
                $active[$eventType][$id] = $level;
            }
        }
        return $active;
    }

    /**
     * @param \pocketmine\item\enchantment\Enchantment $enchantment
     *
     * @return int
     */
    public function getEnchantmentLevel(\pocketmine\item\enchantment\Enchantment $enchantment): int {
        if($enchantment instanceof Enchantment) {
            if(isset($this->activeArmorEnchantments[$enchantment->getEventType()][$enchantment->getRuntimeId()])) {
                return $this->activeArmorEnchantments[$enchantment->getEventType()][$enchantment->getRuntimeId()]->getLevel();
            }
            if(isset($this->activeHeldItemEnchantments[$enchantment->getEventType()][$enchantment->getRuntimeId()])) {
                return $this->activeHeldItemEnchantments[$enchantment->getEventType()][$enchantment->getRuntimeId()]->getLevel();
            }
        }
        return 0;
    }

    /**
     * @param \pocketmine\item\enchantment\Enchantment $enchantment
     *
     * @return int
     */
    public function getEnchantmentLevelArmor(\pocketmine\item\enchantment\Enchantment $enchantment): int {
        if($enchantment instanceof Enchantment) {
            if(isset($this->activeArmorEnchantments[$enchantment->getEventType()][$enchantment->getRuntimeId()])) {
                return $this->activeArmorEnchantments[$enchantment->getEventType()][$enchantment->getRuntimeId()]->getLevel();
            }
        }
        return 0;
    }

    /**
     * @param \pocketmine\item\enchantment\Enchantment $enchantment
     *
     * @return int
     */
    public function getEnchantmentLevelHeldItem(\pocketmine\item\enchantment\Enchantment $enchantment): int {
        if($enchantment instanceof Enchantment) {
            if(isset($this->activeHeldItemEnchantments[$enchantment->getEventType()][$enchantment->getRuntimeId()])) {
                return $this->activeHeldItemEnchantments[$enchantment->getEventType()][$enchantment->getRuntimeId()]->getLevel();
            }
        }
        return 0;
    }

    /**
     * @return float
     */
    public function getItemLuckModifier(): float {
        return $this->itemLuckModifier;
    }

    /**
     * @return float
     */
    public function getArmorLuckModifier(): float {
        return $this->armorLuckModifier;
    }

    /**
     * @param bool $superBreaker
     */
    public function setSuperBreaker(bool $superBreaker): void {
        $this->superBreaker = $superBreaker;
    }

    /**
     * @return bool
     */
    public function hasSuperBreaker(): bool {
        return $this->superBreaker;
    }

    /**
     * @param bool $explode
     */
    public function setExplode(bool $explode): void {
        $this->explode = $explode;
    }

    /**
     * @return bool
     */
    public function hasExplode(): bool {
        return $this->explode;
    }

    /**
     * @param bool $powerball
     */
    public function setPowerball(bool $powerball): void {
        $this->powerball = $powerball;
    }

    /**
     * @return bool
     */
    public function hasPowerball(): bool {
        return $this->powerball;
    }

    /**
     * @return float
     */
    public function getMomentumSpeed(): float {
        if($this->getMomentum() >= 2700) {
            $multiplier = 12.5;
        }
        else {
            $multiplier = 12.5 * ($this->getMomentum() / 2700);
        }
        $item = $this->owner->getInventory()->getItemInHand();

        if($item->hasEnchantment(EnchantmentManager::getEnchantment(Enchantment::MOMENTUM))) {
            if(SetUtils::isWearingFullSet($this->owner, "koth")) $multiplier *= 1.1;
            return round($item->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::MOMENTUM)) * $multiplier, 2);
        } else {
            if(SetUtils::isWearingFullSet($this->owner, "koth")) $multiplier *= 1.1;
            return round(6 * $multiplier, 2);
        }
    }

    /**
     * @return int
     */
    public function getMomentum(): int {
        if($this->momentum < time()) {
            $this->momentum = time();
        }
        return $this->momentum - time();
    }

    /**
     * @return bool
     */
    public function hasMomentum(): bool {
        return $this->owner->getInventory()->getItemInHand()->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::MOMENTUM)) > 0 || SetUtils::isWearingFullSet($this->owner, "koth");
    }

    /**
     * @param int $momentum
     */
    public function addMomentum(int $momentum): void {
        if($this->momentum < time()) {
            $this->momentum = time();
        }
        $this->momentum += $momentum;
    }

    /**
     * @return bool
     */
    public function isHidingHealth(): bool {
        return $this->hidingHealth;
    }

    /**
     * @param bool $hidingHealth
     */
    public function setHidingHealth(bool $hidingHealth): void {
        $this->hidingHealth = $hidingHealth;
    }

    /**
     * @return bool
     */
    public function isDominated(): bool {
        return $this->dominated;
    }

    /**
     * @param bool $dominated
     */
    public function setDominated(bool $dominated): void {
        $this->dominated = $dominated;
        if($dominated) {
            $this->offensiveProcLocation = $this->owner->getPosition();
        }
        else {
            $this->offensiveProcLocation = null;
        }
    }

    /**
     * @return bool
     */
    public function isSilenced(): bool {
        return $this->silenced;
    }

    /**
     * @param bool $silenced
     */
    public function setSilenced(bool $silenced): void {
        $this->silenced = $silenced;
        if($silenced) {
            $this->offensiveProcLocation = $this->owner->getPosition();
        }
        else {
            $this->offensiveProcLocation = null;
        }
    }

    /**
     * @return bool
     */
    public function isBleeding(): bool {
        return $this->bleeding;
    }

    /**
     * @param bool $bleeding
     */
    public function setBleeding(bool $bleeding): void {
        $this->bleeding = $bleeding;
        if($bleeding) {
            $this->offensiveProcLocation = $this->owner->getPosition();
        }
        else {
            $this->offensiveProcLocation = null;
        }
    }

    /**
     * @return bool
     */
    public function isWeakened(): bool {
        return $this->weakened;
    }

    /**
     * @param bool $weakened
     */
    public function setWeakened(bool $weakened): void {
        $this->weakened = $weakened;
        if($weakened) {
            $this->offensiveProcLocation = $this->owner->getPosition();
        }
        else {
            $this->offensiveProcLocation = null;
        }
    }

    /**
     * @return int
     */
    public function getFrenzyHits(): int {
        if((time() - $this->lastFrenzyHit) > 10) {
            $this->frenzyHits = 0;
        }
        return $this->frenzyHits;
    }

    public function addFrenzyHits(): void {
        if($this->lastFrenzyHit === 0) {
            $this->lastFrenzyHit = time();
        }
        elseif((time() - $this->lastFrenzyHit) >= 10) {
            $this->frenzyHits = 0;
        }
        ++$this->frenzyHits;
        $this->lastFrenzyHit = time();
    }

    public function resetFrenzyHits(): void {
        $this->frenzyHits = 0;
        $this->owner->sendPopupTo(TextFormat::RED . TextFormat::BOLD . "* RESET [" . TextFormat::RESET . TextFormat::GRAY . "+0%%%" . TextFormat::RED . TextFormat::BOLD . "] *");
    }

    /**
     * @return int
     */
    public function getBypassHits(): int {
        if((time() - $this->lastBypassHit) > 5) {
            $this->bypassHits = 0;
        }
        return $this->bypassHits;
    }

    public function addBypassHit(): void {
        if($this->lastBypassHit === 0) {
            $this->lastBypassHit = time();
        }
        elseif((time() - $this->lastBypassHit) >= 5) {
            $this->bypassHits = 0;
        }
        ++$this->bypassHits;
        $this->lastBypassHit = time();
    }

    public function resetBypassHits(): void {
        $this->bypassHits = 0;
    }

    /**
     * @param int $amount
     */
    public function setBypassHits(int $amount) : void {
        $this->bypassHits = $amount;
    }

    /**
     * @return int
     */
    public function getThousandCutsStacks(): int {
        if((time() - $this->lastThousandCutHit) > 5) {
            $this->thousandCutStacks = 0;
        }
        return $this->thousandCutStacks;
    }

    public function addThousandCutStack(): void {
        if($this->lastThousandCutHit === 0) {
            $this->lastThousandCutHit = time();
        }
        elseif((time() - $this->lastThousandCutHit) >= 5) {
            $this->thousandCutStacks = 0;
        }
        ++$this->thousandCutStacks;
        $this->lastThousandCutHit = time();
    }

    public function resetThousandCutsStacks(): void {
        $this->thousandCutStacks = 0;
    }

    /**
     * @param int $amount
     */
    public function setThousandCutsStacks(int $amount) : void {
        $this->thousandCutStacks = $amount;
    }

    /**
     * @return int
     */
    public function getWhirlwindStacks(): int {
        if((time() - $this->lastWhirlwindHit) > 5) {
            $this->whirlwindHits = 0;
        }
        return $this->whirlwindHits;
    }

    public function addWhirlwindStack(): void {
        if($this->lastWhirlwindHit === 0) {
            $this->lastWhirlwindHit = time();
        }
        elseif((time() - $this->lastWhirlwindHit) >= 5) {
            $this->whirlwindHits = 0;
        }
        ++$this->whirlwindHits;
        $this->lastWhirlwindHit = time();
    }

    public function resetWhirlwindStacks(): void {
        $this->whirlwindHits = 0;
    }

    /**
     * @param int $amount
     */
    public function setWhirlwindStacks(int $amount) : void {
        $this->whirlwindHits = $amount;
    }

    /**
     * @return int
     */
    public function getLastAdrenaline(): int {
        return $this->lastAdrenaline;
    }

    public function setLastAdrenaline(): void {
        $this->lastAdrenaline = time();
    }

    /**
     * @return int
     */
    public function getLastPainkiller(): int {
        return $this->lastPainkiller;
    }

    public function setLastPainkiller(): void {
        $this->lastPainkiller = time();
    }

    /**
     * @return bool
     */
    public function hasAegis(): bool {
        return $this->aegis < 1;
    }

    /**
     * @param float $aegis
     */
    public function setAegis(float $aegis): void {
        $this->aegis = $aegis;
    }

    /**
     * @return bool
     */
    public function isTrapped(): bool {
        return $this->trapped;
    }

    /**
     * @param bool $trapped
     */
    public function setTrapped(bool $trapped): void {
        $this->trapped = $trapped;
        $this->owner->setImmobile($trapped);
        if($trapped) {
            $this->offensiveProcLocation = $this->owner->getPosition();
        }
        else {
            $this->offensiveProcLocation = null;
        }
    }

    /**
     * @return bool
     */
    public function isTrinketBlocked(): bool {
        return $this->trinketBlocked;
    }

    /**
     * @param bool $trinketBlocked
     */
    public function setTrinketBlocked(bool $trinketBlocked): void {
        $this->trinketBlocked = $trinketBlocked;
        if($trinketBlocked) {
            $this->offensiveProcLocation = $this->owner->getPosition();
        }
        else {
            $this->offensiveProcLocation = null;
        }
    }

    /**
     * @return bool
     */
    public function isCursed(): bool {
        return $this->cursed;
    }

    /**
     * @param bool $cursed
     */
    public function setCursed(bool $cursed): void {
        $this->cursed = $cursed;
        if($cursed) {
            $this->offensiveProcLocation = $this->owner->getPosition();
        }
        else {
            $this->offensiveProcLocation = null;
        }
    }

    /**
     * @return bool
     */
    public function isHexCursed(): bool {
        return $this->hexCursed;
    }

    /**
     * @param bool $hexCursed
     */
    public function setHexCursed(bool $hexCursed): void {
        $this->hexCursed = $hexCursed;
        if($hexCursed) {
            $this->offensiveProcLocation = $this->owner->getPosition();
        }
        else {
            $this->offensiveProcLocation = null;
        }
    }

    /**
     * @return Position|null
     */
    public function getOffensiveProcLocation(): ?Position {
        return $this->offensiveProcLocation;
    }

    /**
     * @param Position|null $offensiveProcLocation
     */
    public function setOffensiveProcLocation(?Position $offensiveProcLocation): void {
        $this->offensiveProcLocation = $offensiveProcLocation;
    }

    /**
     * @return int
     */
    public function getLastSystemReboot(): int {
        return $this->lastSystemReboot;
    }

    public function setLastSystemReboot(): void {
        $this->lastSystemReboot = time();
    }
}