<?php

declare(strict_types=1);

namespace core\game\item\cluescroll\types;

use core\game\item\cluescroll\Challenge;
use core\game\item\event\ApplyItemEvent;
use core\game\item\types\custom\ClueScroll;
use core\player\NexusPlayer;
use pocketmine\item\Item;

class ApplyItemChallenge extends Challenge {

    /** @var string */
    private $itemClass;

    /**
     * ApplyItemChallenge constructor.
     *
     * @param int $id
     * @param string $class
     * @param string $rarity
     */
    public function __construct(int $id, string $class, string $rarity) {
        $this->itemClass = $class;
        $callable = function(ApplyItemEvent $event, Item $scroll) {
            $player = $event->getPlayer();
            if(!$player instanceof NexusPlayer) {
                return;
            }
            $scrollInstance = ClueScroll::fromItem($scroll);
            if($scrollInstance === null) {
                return;
            }
            $challenge = $scrollInstance->getCurrentChallenge();
            if($challenge === $this->getId()) {
                if($event->getItem() instanceof $this->itemClass) {
                    $this->celebrate($player, $scroll, $scrollInstance);
                }
            }
        };
        $item = substr($class, strrpos($class, "\\") + 1);
        $parts = preg_split('/(^[^A-Z]+|[A-Z][^A-Z]+)/', $item, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $description = "Apply a " . implode(" ", $parts);
        parent::__construct($id, $description, $rarity, self::APPLY_ITEM, $callable);
    }
}