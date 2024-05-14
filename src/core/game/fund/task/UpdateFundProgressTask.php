<?php
declare(strict_types=1);

namespace core\game\fund\task;

use core\game\fund\FundManager;
use core\Nexus;
use core\player\rpg\XPUtils;
use libs\utils\Task;
use pocketmine\utils\TextFormat;

class UpdateFundProgressTask extends Task {

    /** @var FundManager */
    private $manager;

    /**
     * UpdateFundProgressTask constructor.
     *
     * @param FundManager $manager
     */
    public function __construct(FundManager $manager) {
        $this->manager = $manager;
    }

    public function onRun(): void {
        $connector = Nexus::getInstance()->getMySQLProvider()->getConnector();
        $connector->executeSelectQuery("SELECT sum(balance) FROM stats", function(array $rows) {
            foreach($rows as [
                "sum(balance)" => $balance
            ]) {
                $this->manager->setGlobalBalance((int)$balance);
                foreach(FundManager::PHASES as $index => $phase) {
                    $index++;
                    if($this->manager->isUnlocked($phase)) {
                        continue;
                    }
                    $progress = $this->manager->getFundProgressBalance($phase);
                    if($progress >= 100) {
                        Nexus::getInstance()->getServer()->broadcastMessage(TextFormat::GOLD . TextFormat::BOLD . "(!) Phase " . TextFormat::WHITE . $index . TextFormat::GOLD . " /fund UNLOCKED!");
                        Nexus::getInstance()->getServer()->broadcastMessage(TextFormat::GRAY . "You can now use $phase!");
                        $this->manager->setUnlocked($phase);
                    }
                }
            }
            Nexus::getInstance()->getLogger()->notice("[Fund] Updated fund progress! (1/2)");
        });
        $connector->executeSelectQuery("SELECT username, xp, prestige FROM stats ORDER BY prestige DESC, xp DESC LIMIT 10", function(array $rows) {
            $ranks = [];
            foreach($rows as [
                "xp" => $xp,
                "prestige" => $prestige
            ]) {
                $xp = (int)$xp;
                $prestige = (int)$prestige;
                if($prestige > 0) {
                    $ranks[] = 100 + $prestige;
                }
                else {
                    $ranks[] = XPUtils::xpToLevel($xp);
                }
            }
            $this->manager->setGlobalRanks($ranks);
            foreach(FundManager::PHASES as $index => $phase) {
                $index++;
                if($this->manager->isUnlocked($phase)) {
                    continue;
                }
                $progress = $this->manager->getFundProgressRanks($phase);
                if($progress >= 100) {
                    Nexus::getInstance()->getServer()->broadcastMessage(TextFormat::GOLD . TextFormat::BOLD . "(!) Phase " . TextFormat::WHITE . $index . TextFormat::GOLD . " /fund UNLOCKED!");
                    Nexus::getInstance()->getServer()->broadcastMessage(TextFormat::GRAY . "You can now use $phase!");
                    $this->manager->setUnlocked($phase);
                }
            }
            Nexus::getInstance()->getLogger()->notice("[Fund] Updated fund progress! (2/2)");
        });
    }
}