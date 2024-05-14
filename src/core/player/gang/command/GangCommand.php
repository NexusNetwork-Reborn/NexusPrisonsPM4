<?php
declare(strict_types=1);

namespace core\player\gang\command;

use core\command\utils\Command;
use core\player\gang\command\subCommands\AllySubCommand;
use core\player\gang\command\subCommands\AnnounceSubCommand;
use core\player\gang\command\subCommands\ChatSubCommand;
use core\player\gang\command\subCommands\CreateSubCommand;
use core\player\gang\command\subCommands\DemoteSubCommand;
use core\player\gang\command\subCommands\DepositSubCommand;
use core\player\gang\command\subCommands\DisbandSubCommand;
use core\player\gang\command\subCommands\EnemySubCommand;
use core\player\gang\command\subCommands\FlagsSubCommand;
use core\player\gang\command\subCommands\ForceDeleteSubCommand;
use core\player\gang\command\subCommands\HelpSubCommand;
use core\player\gang\command\subCommands\InfoSubCommand;
use core\player\gang\command\subCommands\InviteSubCommand;
use core\player\gang\command\subCommands\JoinSubCommand;
use core\player\gang\command\subCommands\KickSubCommand;
use core\player\gang\command\subCommands\LeaderSubCommand;
use core\player\gang\command\subCommands\LeaveSubCommand;
use core\player\gang\command\subCommands\ListSubCommand;
use core\player\gang\command\subCommands\NeutralSubCommand;
use core\player\gang\command\subCommands\PromoteSubCommand;
use core\player\gang\command\subCommands\TopSubCommand;
use core\player\gang\command\subCommands\UnallySubCommand;
use core\player\gang\command\subCommands\VaultSubCommand;
use core\player\gang\command\subCommands\WithdrawSubCommand;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;

class GangCommand extends Command {

    /**
     * GangCommand constructor.
     */
    public function __construct() {
        $this->addSubCommand(new AllySubCommand());
        $this->addSubCommand(new AnnounceSubCommand());
        $this->addSubCommand(new ChatSubCommand());
        $this->addSubCommand(new CreateSubCommand());
        $this->addSubCommand(new DemoteSubCommand());
        $this->addSubCommand(new DepositSubCommand());
        $this->addSubCommand(new DisbandSubCommand());
        $this->addSubCommand(new EnemySubCommand());
        $this->addSubCommand(new FlagsSubCommand());
        $this->addSubCommand(new ForceDeleteSubCommand());
        $this->addSubCommand(new HelpSubCommand());
        $this->addSubCommand(new InfoSubCommand());
        $this->addSubCommand(new InviteSubCommand());
        $this->addSubCommand(new JoinSubCommand());
        $this->addSubCommand(new KickSubCommand());
        $this->addSubCommand(new LeaderSubCommand());
        $this->addSubCommand(new LeaveSubCommand());
        $this->addSubCommand(new ListSubCommand());
        $this->addSubCommand(new NeutralSubCommand());
        $this->addSubCommand(new PromoteSubCommand());
        $this->addSubCommand(new TopSubCommand());
        $this->addSubCommand(new UnallySubCommand());
        $this->addSubCommand(new VaultSubCommand());
        $this->addSubCommand(new WithdrawSubCommand());
        parent::__construct("gang", "Manage gang", "/gang help <1-4>", ["g", "f"]);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if(isset($args[0])) {
            $subCommand = $this->getSubCommand($args[0]);
            if($subCommand !== null) {
                $subCommand->execute($sender, $commandLabel, $args);
                return;
            }
            $sender->sendTranslatedMessage("usageMessage", [
                "usage" => $this->getUsage()
            ]);
            return;
        }
        $sender->sendTranslatedMessage("usageMessage", [
            "usage" => $this->getUsage()
        ]);
        return;
    }
}