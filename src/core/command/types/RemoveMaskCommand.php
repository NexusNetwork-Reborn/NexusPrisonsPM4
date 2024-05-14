<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\Command;
use core\game\item\types\custom\Mask;
use core\game\item\types\custom\MultiMask;
use core\game\item\types\vanilla\Armor;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\item\Durable;
use pocketmine\world\sound\AnvilUseSound;

class RemoveMaskCommand extends Command {

    /**
     * RepairCommand constructor.
     */
    public function __construct() {
        parent::__construct("removemask", "Remove current mask", "/removemask", ["rmmask"]);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if($sender instanceof NexusPlayer) {
            $item = $sender->getInventory()->getItemInHand();
            if((!$item instanceof Armor) or $item->getArmorSlot() !== Armor::SLOT_HEAD) {
                $sender->sendMessage(Translation::getMessage("invalidItem"));
                return;
            }
            $masks = $item->getMasks();
            if(!empty($masks)) {
                if(count($masks) > 1) {
                    $names = [];
                    foreach($masks as $mask) {
                        $names[] = $mask->getName();
                    }
                    $sender->addItem((new MultiMask($names))->toItem());
                }
                else {
                    $sender->addItem((new Mask(array_shift($masks)->getName()))->toItem());
                }
                $sender->getInventory()->setItemInHand($item->setMasks([]));
                $sender->getWorld()->addSound($sender->getPosition(), new AnvilUseSound(), [$sender]);
            }
            else {
                $sender->sendMessage(Translation::RED . "You have no masks attached to this helmet!");
            }
            return;
        }
        $sender->sendMessage(Translation::getMessage("noPermission"));
    }
}