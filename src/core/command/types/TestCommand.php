<?php
declare(strict_types=1);

namespace core\command\types;

use core\command\utils\Command;
use core\display\animation\entity\CrateEntity;
use core\game\boss\task\BossSummonTask;
use core\game\combat\guards\Guard;
use core\game\combat\koth\task\StartKOTHGameTask;
use core\game\item\enchantment\Enchantment;
use core\game\item\types\custom\AethicCrate;
use core\game\item\types\custom\Contraband;
use core\game\item\types\custom\ExecutiveBooster;
use core\game\item\types\custom\ItemNameTag;
use core\game\item\types\custom\Lootbox;
use core\game\item\types\custom\MysteryEnchantmentOrb;
use core\game\item\types\custom\PrestigeToken;
use core\game\item\types\custom\XPBottle;
use core\game\item\types\Rarity;
use core\game\rewards\types\lootbox\PrisonBreakLootbox;
use core\game\rewards\types\MonthlyRewards;
use core\level\task\SpawnRushOreTask;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;

class TestCommand extends Command {

    /**
     * TestCommand constructor.
     */
    public function __construct() {
        parent::__construct("test", "Has a mysterious function, only could be executed by THeRuTHLessCoW.", "/test");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        $type = $args[0] ?? "";
        if(in_array($sender->getName(), Nexus::SUPER_ADMIN)) {
            switch($type) {
                case "boss":
                    BossSummonTask::getBossFight()->getSummonTask()->initiatePhase(3);
                    break;
                case "rush":
                    SpawnRushOreTask::getInstance()->spawnOreRush();
                    break;
                case "koth":
                    $kothManager = $this->getCore()->getGameManager()->getCombatManager();
                    if($kothManager->getKOTHGame() !== null) {
                        $sender->sendMessage(Translation::getMessage("kothRunning"));
                        return;
                    }
                    $kothManager->initiateKOTHGame();
                    $this->getCore()->getScheduler()->scheduleRepeatingTask(new StartKOTHGameTask($this->getCore()), 20);
                    break;
                case "executive":
                    /** @var NexusPlayer $player */
                    foreach(Nexus::getInstance()->getServer()->getOnlinePlayers() as $player) {
                        $player->getDataSession()->resetExecutiveMineTime();
                    }
                    Nexus::getInstance()->getMySQLProvider()->getDatabase()->query("UPDATE executive SET duration = 600;");
                    break;
                case "reset":
                    $sender->sendMessage(Translation::RED . "The Database was RESET!!!");
                    Nexus::getInstance()->getMySQLProvider()->realReset();
            }
        } else {
            $sender->sendMessage(Translation::getMessage("noPermission"));
        }
    }
}