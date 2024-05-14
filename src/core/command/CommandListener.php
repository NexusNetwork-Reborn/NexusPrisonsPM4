<?php

/***
 *    ___                                          _
 *   / __\___  _ __ ___  _ __ ___   __ _ _ __   __| | ___
 *  / /  / _ \| '_ ` _ \| '_ ` _ \ / _` | '_ \ / _` |/ _ \
 * / /__| (_) | | | | | | | | | | | (_| | | | | (_| | (_) |
 * \____/\___/|_| |_| |_|_| |_| |_|\__,_|_| |_|\__,_|\___/
 *
 * Commando - A Command Framework virion for PocketMine-MP
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * Written by @CortexPE <https://CortexPE.xyz>
 *
 */
declare(strict_types=1);

namespace core\command;

use core\command\utils\Command;
use core\command\utils\IArgumentable;
use core\game\plots\PlotManager;
use core\Nexus;
use core\player\NexusPlayer;
use core\player\rank\Rank;
use pocketmine\command\CommandMap;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandEnum;
use pocketmine\network\mcpe\protocol\types\command\CommandOverload;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use pocketmine\Server;
use ReflectionClass;
use function array_unshift;

class CommandListener implements Listener {

    /** @var Nexus */
    private $core;

    /** @var CommandMap */
    protected $map;

    /**
     * CommandListener constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $this->map = Server::getInstance()->getCommandMap();
    }

    /**
     * @priority NORMAL
     * @param PlayerMoveEvent $event
     */
    public function onPlayerMove(PlayerMoveEvent $event): void {
        /** @var NexusPlayer $player */
        $player = $event->getPlayer();
        $world = $event->getTo()->getWorld();
        if($world === null) {
            return;
        }
        if(!$player->isLoaded()) {
            return;
        }
        if(PlotManager::isPlotWorld($world)) {
            return;
        }
        if($player->isFlying() or $player->getAllowFlight()) {
            if($player->getDataSession()->getRank()->getIdentifier() < Rank::TRAINEE or $player->getDataSession()->getRank()->getIdentifier() > Rank::EXECUTIVE) {
                $player->setFlying(false);
                $player->setAllowFlight(false);
            }
        }
    }

    /**
     * @param DataPacketSendEvent $ev
     *
     * @priority        LOWEST
     * @ignoreCancelled true
     */
    public function onPacketSend(DataPacketSendEvent $ev): void {
        $pks = $ev->getPackets();
        foreach($pks as $pk) {
            if($pk instanceof AvailableCommandsPacket) {
                foreach($pk->commandData as $commandName => $commandData) {
                    $cmd = $this->map->getCommand($commandName);
                    if($cmd instanceof Command) {
                        $pk->commandData[$commandName]->overloads = self::generateOverloads($cmd);
                    }
                }
            }
        }
    }

    /**
     * @param CommandSender $cs
     * @param Command $command
     *
     * @return CommandParameter[][]
     */
    private static function generateOverloads(Command $command): array {
        $overloads = [];
        $values = [];
        foreach($command->getSubCommands() as $label => $subCommand) {
            $scParam = new CommandParameter();
            $scParam->paramName = $label;
            $scParam->paramType = AvailableCommandsPacket::ARG_FLAG_VALID | AvailableCommandsPacket::ARG_FLAG_ENUM;
            $scParam->isOptional = false;
            $scParam->enum = new CommandEnum($label, [$label]);

            $overloadList = self::generateOverloadList($subCommand);
            if(!empty($overloadList)){
                foreach($overloadList as $overload) {
                    $overloads[] = new CommandOverload(false, [$scParam, ...$overload->getParameters()]);
                }
            } else {
                $overloads[] = new CommandOverload(false, [$scParam]);
            }
        }
        $scEnum = new CommandEnum($command->getName() . "SubCommands", $values);
        foreach(self::generateOverloadList($command) as $overload) {
            $overloads[] = $overload;
        }
        return $overloads;
    }

    /**
     * @param IArgumentable $argumentable
     *
     * @return CommandOverload[]
     */
    private static function generateOverloadList(IArgumentable $argumentable): array {
        $input = $argumentable->getArgumentList();
        $combinations = [];
        $outputLength = array_product(array_map("count", $input));
        $indexes = [];
        foreach($input as $k => $charList){
            $indexes[$k] = 0;
        }
        do {
            /** @var CommandParameter[] $set */
            $set = [];
            foreach($indexes as $k => $index){
                $param = $set[$k] = clone $input[$k][$index]->getNetworkParameterData();

                if (isset($param->enum) && $param->enum instanceof CommandEnum) {
                    $refClass = new ReflectionClass(CommandEnum::class);
                    $refProp = $refClass->getProperty("enumName");
                    $refProp->setAccessible(true);
                    $refProp->setValue($param->enum, "enum#" . spl_object_id($param->enum));
                }
            }
            $combinations[] =  new CommandOverload(false, $set);

            foreach($indexes as $k => $v){
                $indexes[$k]++;
                $lim = count($input[$k]);
                if($indexes[$k] >= $lim){
                    $indexes[$k] = 0;
                    continue;
                }
                break;
            }
        } while(count($combinations) !== $outputLength);

        return $combinations;
    }
}