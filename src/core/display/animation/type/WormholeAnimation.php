<?php

namespace core\display\animation\type;

use core\command\types\RemoveItemSkinCommand;
use core\display\animation\Animation;
use core\display\animation\AnimationException;
use core\display\animation\entity\AnimationEntity;
use core\display\animation\entity\ItemBaseEntity;
use core\player\NexusPlayer;
use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\world\particle\EnchantmentTableParticle;
use pocketmine\world\Position;

class WormholeAnimation extends Animation {

    /** @var ItemBaseEntity */
    protected $item;

    /** @var Position */
    protected $to;

    /**
     * WormholeAnimation constructor.
     *
     * @param NexusPlayer $owner
     * @param Item $item
     * @param Position $to
     *
     * @throws AnimationException
     */
    public function __construct(NexusPlayer $owner, Item $item, Position $to) {
        $this->to = $to;
        if($item instanceof Durable && RemoveItemSkinCommand::hasSkin($item)) {
            $display = RemoveItemSkinCommand::clearSkin($item)[0];
            $this->item = ItemBaseEntity::create($owner, $display, $to);
        } else {
            $this->item = ItemBaseEntity::create($owner, $item, $owner->getPosition());
        }
        $this->item->initialize(Position::fromObject($to->add(-0.5, 2, -0.5), $to->getWorld()), 1, false, 0, function(ItemBaseEntity $entity): void {
            $level = $entity->getWorld();
            if($level === null or $entity->isClosed() or $entity->isFlaggedForDespawn()) {
                return;
            }
            $x = $entity->getPosition()->getX();
            $y = $entity->getPosition()->getY();
            $z = $entity->getPosition()->getZ();
            for($i = 0; $i > -2; $i -= 0.5) {
                $pos = new Vector3($x, $y + $i, $z);
                $level->addParticle($pos, new EnchantmentTableParticle(), [$this->owner]);
            }
        });
        $this->item->spawnTo($owner);
        parent::__construct($owner);
    }

    public function sendTo(NexusPlayer $player): void {

    }

    public function tick(): void {
        if($this->owner->isClosed()) {
            $this->item->flagForDespawn();
            $this->closeAnimation();
            return;
        }
        if($this->item->isFlaggedForDespawn() or $this->item->isClosed()) {
            $this->closeAnimation();
            return;
        }
    }

    /**
     * @return AnimationEntity
     */
    public function getEntity(): AnimationEntity {
        return $this->item;
    }
}