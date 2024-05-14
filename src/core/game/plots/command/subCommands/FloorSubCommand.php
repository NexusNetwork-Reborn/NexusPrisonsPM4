<?php

declare(strict_types = 1);

namespace core\game\plots\command\subCommands;

use core\command\utils\SubCommand;
use core\game\plots\command\inventory\CellFloorInventory;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;

class FloorSubCommand extends SubCommand
{

    public function __construct()
    {
        parent::__construct("floor", "/plot floor");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if(!$sender instanceof NexusPlayer) {
            $sender->sendMessage(Translation::getMessage("noPermission"));
            return;
        }

        if(Nexus::getInstance()->getGameManager()->getPlotManager()->getPlotInPosition($sender->getPosition()) === null) {
            $sender->sendTranslatedMessage(Translation::getMessage("notInPlotFloor"));
            return;
        }

        if (Nexus::getInstance()->getGameManager()->getPlotManager()->getPlotInPosition($sender->getPosition())->getOwner() == null){
            $sender->sendTranslatedMessage(Translation::getMessage("notPlotOwnerFloor"));
            return;
        }
        if(Nexus::getInstance()->getGameManager()->getPlotManager()->getPlotInPosition($sender->getPosition())->getOwner()->getUsername() !== $sender->getName() || Nexus::getInstance()->getGameManager()->getPlotManager()->getPlotInPosition($sender->getPosition())->getOwner() === null) {
            $sender->sendTranslatedMessage(Translation::getMessage("notPlotOwnerFloor"));
            return;
        }

        $window = new CellFloorInventory(Nexus::getInstance()->getGameManager()->getPlotManager()->getPlotInPosition($sender->getPosition()));
        $window->send($sender);
    }
}