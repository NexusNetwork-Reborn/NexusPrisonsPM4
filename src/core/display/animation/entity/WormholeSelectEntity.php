<?php

namespace core\display\animation\entity;

use core\display\animation\AnimationException;
use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\types\custom\EnchantmentOrb;
use core\game\item\types\Rarity;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\ItemIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;

class WormholeSelectEntity extends ItemBaseEntity implements AnimationEntity {

    /** @var ?EnchantmentInstance */
    protected $enchantment = null;

    /** @var int */
    protected $success;

    /** @var bool */
    protected $updatedName = false;

    /**
     * @return EntitySizeInfo
     */
    protected function getInitialSizeInfo(): EntitySizeInfo {
        return new EntitySizeInfo(1, 1);
    }

    /**
     * @param NexusPlayer $owner
     * @param Position $position
     * @param EnchantmentInstance|null $enchantmentInstance
     * @param int $success
     *
     * @return WormholeSelectEntity
     * @throws AnimationException
     */
    public static function createInteractive(NexusPlayer $owner, Position $position, ?EnchantmentInstance $enchantmentInstance, int $success): WormholeSelectEntity {
        $entity = new WormholeSelectEntity(Location::fromObject($position, $position->getWorld()));
        if($enchantmentInstance) {
            $skin = Nexus::getInstance()->getDisplayManager()->getAnimationManager()->getSkin(ItemIds::DYE, EnchantmentOrb::RARITY_TO_DAMAGE_MAP[$enchantmentInstance->getType()->getRarity()]);
        }
        else {
            $skin = Nexus::getInstance()->getDisplayManager()->getAnimationManager()->getSkin(ItemIds::DYE, EnchantmentOrb::RARITY_TO_DAMAGE_MAP[Enchantment::SIMPLE]);
        }
        if($skin === null) {
            throw new AnimationException("Unable to find the item skin for an interactive animation entity.");
        }
        $entity->setOwningEntity($owner);
        $entity->setEnchantment($enchantmentInstance);
        $entity->setSuccess($success);
        $maxLevel = "";
        if($enchantmentInstance !== null) {
            $enchantment = $enchantmentInstance->getType();
            if($enchantmentInstance->getLevel() === $enchantment->getMaxLevel()) {
                $maxLevel = TextFormat::BOLD;
            }
            $customName = TextFormat::RESET . TextFormat::OBFUSCATED . Rarity::RARITY_TO_COLOR_MAP[Rarity::ENCHANTMENT_RARITY_TO_STRING_MAP[$enchantment->getRarity()]] . $maxLevel . $enchantment->getName() . TextFormat::AQUA . " " . EnchantmentManager::getRomanNumber($enchantmentInstance->getLevel());
        }
        else {
            $customName = TextFormat::RESET . TextFormat::OBFUSCATED . TextFormat::BOLD . TextFormat::AQUA . "Level up";
        }
        $customName .= "\n" . TextFormat::RESET . TextFormat::OBFUSCATED . TextFormat::BOLD . TextFormat::GREEN . "$success% success rate";
        $fail = 100 - $success;
        $customName .= "\n" . TextFormat::RESET . TextFormat::OBFUSCATED . TextFormat::BOLD . TextFormat::RED . "$fail% failure rate";
        $entity->setNameTag($customName);
        $entity->setNameTagVisible();
        $entity->setNameTagAlwaysVisible();
        $entity->setSkin($skin);
        $entity->setStart($position);
        $entity->recalculateBoundingBox();
        $entity->getNetworkProperties()->setFloat(EntityMetadataProperties::BOUNDING_BOX_WIDTH, $entity->getInitialSizeInfo()->getWidth());
        $entity->getNetworkProperties()->setFloat(EntityMetadataProperties::BOUNDING_BOX_HEIGHT, $entity->getInitialSizeInfo()->getHeight());
        return $entity;
    }

    protected function entityBaseTick(int $tickDiff = 1): bool {
        $return = parent::entityBaseTick($tickDiff);
        if($this->finish and (!$this->updatedName)) {
            $this->updateTag($this->success);
        }
        return $return;
    }

    /**
     * @param int $success
     */
    public function updateTag(int $success): void {
        $this->success = $success;
        $maxLevel = "";
        if($this->enchantment !== null) {
            $enchantment = $this->enchantment->getType();
            if($this->enchantment->getLevel() === $enchantment->getMaxLevel()) {
                $maxLevel = TextFormat::BOLD;
            }
            $customName = TextFormat::RESET . Rarity::RARITY_TO_COLOR_MAP[Rarity::ENCHANTMENT_RARITY_TO_STRING_MAP[$enchantment->getRarity()]] . $maxLevel . $enchantment->getName() . TextFormat::AQUA . " " . EnchantmentManager::getRomanNumber($this->enchantment->getLevel());
        }
        else {
            $customName = TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Level up";
        }
        $customName .= "\n" . TextFormat::RESET . TextFormat::BOLD . TextFormat::GREEN . "$this->success% success rate";
        $fail = 100 - $this->success;
        $customName .= "\n" . TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "$fail% failure rate";
        $this->setNameTag($customName);
    }

    /**
     * @param EnchantmentInstance|null $enchantment
     */
    public function setEnchantment(?EnchantmentInstance $enchantment): void {
        $this->enchantment = $enchantment;
    }

    /**
     * @return EnchantmentInstance|null
     */
    public function getEnchantment(): ?EnchantmentInstance {
        return $this->enchantment;
    }

    /**
     * @param int $success
     */
    public function setSuccess(int $success): void {
        $this->success = $success;
    }

    /**
     * @return int
     */
    public function getSuccess(): int {
        return $this->success;
    }

    /**
     * @param EntityDamageEvent $source
     */
    public function attack(EntityDamageEvent $source): void {
        if($source instanceof EntityDamageByEntityEvent) {
            $damager = $source->getDamager();
            if($damager instanceof NexusPlayer) {
                $session = Nexus::getInstance()->getGameManager()->getWormholeManager()->getSession($damager);
                if($session !== null) {
                    $session->handleInteract($this);
                }
            }
        }
    }
}