<?php

namespace core\game\boss;

use core\game\boss\task\BossSummonTask;
use core\game\item\slotbot\SlotBotTicket;
use core\game\item\types\custom\Contraband;
use core\game\item\types\custom\Energy;
use core\game\item\types\custom\GKitFlare;
use core\game\item\types\custom\HadesLootbag;
use core\game\item\types\custom\Lootbox;
use core\game\item\types\custom\Token;
use core\game\item\types\Rarity;
use core\game\rewards\RewardsManager;
use core\game\rewards\types\lootbox\CodeGreenLootbox;
use core\level\LevelManager;
use core\Nexus;
use core\player\NexusPlayer;
use core\game\boss\entity\Hades;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\item\BlazeRod;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\Position;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class BossFight {

    private int $maxParticipants = 0;

    private bool $isOpenForJoin = false;

    private bool $isStarted = false;

    private ?BossSummonTask $summonTask = null;

    /** @var array<string, NexusPlayer> */
    private array $participants;

    /** @var array<string, int> */
    private array $leaderboard;

    public function __construct(array $participants = [], BossSummonTask $summonTask = null) {
        $this->participants = $participants;
        $this->summonTask = $summonTask;
    }

    public function leaderboardUpdate(string $playerName, int|float $damage) : void {
        $damage = (int) $damage;
        if(isset($this->leaderboard[$playerName])){
            $this->leaderboard[$playerName] += $damage;
        } else {
            $this->leaderboard[$playerName] = $damage;
        }
    }

    public function openForJoining(){
        $this->isOpenForJoin = true;
    }

    public function isOpenForJoining() : bool{
        return $this->isOpenForJoin;
    }

    public function closeJoining(){
        $this->isOpenForJoin = false;
    }

    public function isStarted(): bool
    {
        return $this->isStarted;
    }

    public function participantCount() : int {
        return count($this->participants);
    }

    public function getMaxParticipants(): int
    {
        return $this->maxParticipants;
    }

    public function addPlayer(NexusPlayer $player)
    {
        if ($this->isOpenForJoin) {
            $this->participants[$player->getXuid()] = $player;
        }
    }

    public function removePlayer(NexusPlayer $player){
        if(isset($this->participants[$player->getXuid()])){
            unset($this->participants[$player->getXuid()]);
            if($this->participantCount() == 0 && $this->isStarted()) {
                $this->end(false);
            }
        }
    }

    public function inArena(NexusPlayer $player): bool
    {
        return isset($this->participants[$player->getXuid()]);
    }

    /**
     * @throws \pocketmine\scheduler\CancelTaskException
     */
    public function start(BossSummonTask $task){
        $this->maxParticipants = $this->participantCount();
        $this->isStarted = true;
        $this->summonTask = $task;
        $this->leaderboard = [];
        $level = Server::getInstance()->getWorldManager()->getWorldByName(LevelManager::getSetup()->getNested("boss.world"));
        //$positions = [new Position(-103, 105, -108, $level), new Position(-119, 105, -124, $level), new Position(-103, 105, -140, $level), new Position(-87, 105, -124, $level)];
        $positions = [];
        foreach (LevelManager::getSetup()->getNested("boss.player-spawn") as $pos) {
            $v = explode(":", $pos);
            $positions[] = new Position((float)$v[0], (float)$v[1], (float)$v[2], $level);
        }
        foreach ($this->participants as $participant){
            $participant->teleport($positions[array_rand($positions)]);
            $participant->setAllowFlight(false);
            $participant->setFlying(false);
        }
        $pos = explode(":", LevelManager::getSetup()->getNested("boss.xyz"));
        $avgLvl = 0;
        foreach ($this->participants as $participant){
            if($participant->getDataSession()->getPrestige() > 0) {
                $total = 100;
            } else {
                $total = $participant->getDataSession()->getTotalXPLevel();
            }
            $avgLvl += $total;
        }
        $avgLvl /= max(1, $this->participantCount());
        $ent = new Hades(new Location((float)$pos[0], (float)$pos[1], (float)$pos[2], $level, 0, 0), CompoundTag::create());
        $ent->setMaxHealth((int) (min(5000, (500 * $this->participantCount()) + (15 * $avgLvl))));
        $ent->setHealth((int) (min(5000, (500 * $this->participantCount()) + (15 * $avgLvl))));
        $ent->attackDamage = 4 + (1.5 * $this->participantCount());
        $ent->setWhistles((int) ($ent->getHealth() / 1000) - 1);
//        $ent = Entity::createEntity(
//            "nexus:hades",
//            Server::getInstance()->getWorldManager()->getWorldByName("spawn"),
//            Entity::createBaseNBT(new Position(-103, 105, -124))
//        );
        foreach (Nexus::getInstance()->getServer()->getOnlinePlayers() as $player) {
            $ent->spawnTo($player);
        }
        $this->boss = $ent;
        if($this->participantCount() == 0) {
            $this->end(false, true);
        }
    }

    public function getBoss(): ?Hades
    {
        return $this->boss;
    }

    private ?Entity $boss = null;

    public function end(bool $win = true, bool $cancel = false){
        //$prefix = TextFormat::RED . "[" . TextFormat::BOLD . TextFormat::AQUA . "nexus " . TextFormat::GOLD . TextFormat::BOLD . "Bosses" . TextFormat::RESET . TextFormat::RED . "] " . TextFormat::RESET . TextFormat::GREEN;
        /** @var NexusPlayer $player */
        foreach ($this->participants as $player) {
            $player->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
            //$player->sendMessage($prefix . "The players emerge victorious out of Hades' domain, and yet they must remain vigilant, for Hades will return once again.");
            // TODO: Send victory title?
        }
        $this->isStarted = false;
        $message = TextFormat::YELLOW . "-+- " . TextFormat::AQUA . "Hades Leaderboard" . TextFormat::YELLOW . " -+-\n";
        $i = 1;
        $totalDamage = 0;
        foreach ($this->leaderboard as $damageDealt){
            $totalDamage += $damageDealt;
        }
        if($cancel) {
            Server::getInstance()->broadcastMessage(TextFormat::YELLOW . TextFormat::BOLD . "(!) " . TextFormat::RED . TextFormat::UNDERLINE . "HADES" . TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " has been cancelled due to lack of participants!");
        } elseif($win) {
            arsort($this->leaderboard);
            //$sender = new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage());
            foreach ($this->leaderboard as $participant => $damageDealt) {
                $receiver = Server::getInstance()->getPlayerExact($participant);
                $level = 10;
                if($receiver !== null && $receiver instanceof NexusPlayer) {
                    if($receiver->isLoaded()) {
                        $current = $receiver->getDataSession()?->getTotalXPLevel() ?? 30;
                        $level = max(10, $current - 30);
                    }
                }
                if ($i >= 11) continue;

                if($i === 1) {
                    $rewards = [
                        (new HadesLootbag())->toItem()->setCount(1),
                        (new Token())->toItem()->setCount(intval(9 * ($level/10))),
                    ];
                    $rewards[] = (new SlotBotTicket("Normal"))->toItem()->setCount(min(3, intval($level / 20)));
                    if($level >= 55) {
                        $rewards[] = (new Lootbox(RewardsManager::CODE_GREEN, time() + 604800))->toItem()->setCount(1);
                    }
                    if($level >= 50) {
                        $rewards[] = (new GKitFlare(null, false))->toItem()->setCount(1);
                    }
                    $fail = false;
                    $drop = false;
                    foreach($rewards as $item) {
                        //$receiver = Server::getInstance()->getPlayerExact($participant);
                        if($receiver !== null) {
                            if($receiver instanceof NexusPlayer && $receiver->isLoaded() && $receiver->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                                $fail = true;
                                $receiver->getDataSession()->getInbox()->getInventory()->addItem($item);
                            } elseif($receiver->getInventory()->canAddItem($item)) {
                                $receiver->getInventory()->addItem($item);
                            } else {
                                $receiver->getWorld()->dropItem($receiver->getPosition(), $item);
                                $drop = true;
                            }
                        }
                    }
                    if($fail) {
                        $receiver->sendTranslatedMessage("inboxAlert");
                    }
                    if($drop) {
                        $receiver->sendTranslatedMessage("dropAlert");
                    }
                } elseif($i === 2) {
                    $rewards = [
                        (new Token())->toItem()->setCount(intval(6 * ($level/10)))
                    ];
                    if($level >= 50) {
                        $rewards[] = (new GKitFlare(null, false))->toItem()->setCount(1);
                        $rewards[] = (new SlotBotTicket("Normal"))->toItem()->setCount(1);
                    }
                    if($level >= 30) {
                        $rewards[] = (new Contraband(Rarity::GODLY))->toItem()->setCount(1);
                    }
                    $fail = false;
                    $drop = false;
                    foreach($rewards as $item) {
                        //$receiver = Server::getInstance()->getPlayerExact($participant);
                        if($receiver !== null) {
                            if($receiver instanceof NexusPlayer && $receiver->isLoaded() && $receiver->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                                $fail = true;
                                $receiver->getDataSession()->getInbox()->getInventory()->addItem($item);
                            } elseif($receiver->getInventory()->canAddItem($item)) {
                                $receiver->getInventory()->addItem($item);
                            } else {
                                $receiver->getWorld()->dropItem($receiver->getPosition(), $item);
                                $drop = true;
                            }
                        }
                    }
                    if($fail) {
                        $receiver->sendTranslatedMessage("inboxAlert");
                    }
                    if($drop) {
                        $receiver->sendTranslatedMessage("dropAlert");
                    }
                } elseif($i === 3) {
                    $rewards = [
                        (new Token())->toItem()->setCount(intval(3 * ($level/10)))
                    ];
                    if($level >= 30) {
                        $rewards[] = (new Contraband(Rarity::LEGENDARY))->toItem()->setCount(1);
                    }
                    if($level >= 50) {
                        $rewards[] = (new GKitFlare(null, false))->toItem()->setCount(1);
                    }
                    $fail = false;
                    $drop = false;
                    foreach($rewards as $item) {
                        //$receiver = Server::getInstance()->getPlayerExact($participant);
                        if($receiver !== null) {
                            if($receiver instanceof NexusPlayer && $receiver->isLoaded() && $receiver->getDataSession()->getInbox()->getInventory()->canAddItem($item)) {
                                $fail = true;
                                $receiver->getDataSession()->getInbox()->getInventory()->addItem($item);
                            } elseif($receiver->getInventory()->canAddItem($item)) {
                                $receiver->getInventory()->addItem($item);
                            } else {
                                $receiver->getWorld()->dropItem($receiver->getPosition(), $item);
                                $drop = true;
                            }
                        }
                    }
                    if($fail) {
                        $receiver->sendTranslatedMessage("inboxAlert");
                    }
                    if($drop) {
                        $receiver->sendTranslatedMessage("dropAlert");
                    }
                } else {
                    $energy = (new Energy((int)(($damageDealt/$totalDamage) * 1000000)))->toItem()->setCount(1);
                    //$receiver = Server::getInstance()->getPlayerExact($participant);
                    if($receiver !== null) {
                        if($receiver instanceof NexusPlayer && $receiver->isLoaded() && $receiver->getDataSession()->getInbox()->getInventory()->canAddItem($energy)) {
                            $receiver->getDataSession()->getInbox()->getInventory()->addItem($energy);
                            $receiver->sendTranslatedMessage("inboxAlert");
                        } else if($receiver->getInventory()->canAddItem($energy)) {
                            $receiver->getInventory()->addItem($energy);
                        } else {
                            $receiver->getWorld()->dropItem($receiver->getPosition(), $energy);
                            $receiver->sendTranslatedMessage("dropAlert");
                        }
                    }
                }

//                        if ($damageDealt/$totalDamage >= 0.1) {
//                            Server::getInstance()->dispatchCommand($sender, "lootbag $participant boss 1");
//                        }
                $message .= $this->getColorFromSpot($i) . "$i. $participant [" . round((float)($damageDealt / $totalDamage) * 100, 2) . "%]\n";
                $i++;
            }
            Server::getInstance()->broadcastMessage($message);
            Server::getInstance()->broadcastMessage(TextFormat::YELLOW . TextFormat::BOLD . "(!) " . TextFormat::RED . TextFormat::UNDERLINE . "HADES" . TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " has been defeated!");
            //Server::getInstance()->broadcastMessage($prefix . "Hades has fallen in today's battle, but he promises to return once again.");
        } else {
            $this->boss->flagForDespawn();
            Server::getInstance()->broadcastMessage(TextFormat::YELLOW . TextFormat::BOLD . "(!) " . TextFormat::RED . TextFormat::UNDERLINE . "HADES" . TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . " has defeated the players!");
        }
        //Nexus::getInstance()->getScheduler()->scheduleDelayedTask($this->summonTask->setPhase(), 288000);
        $this->boss = null;
        $this->participants = [];
        $this->leaderboard = [];
    }

    private function getColorFromSpot(int $spot) : string {
        return match ($spot) {
            1 => TextFormat::GOLD,
            2 => TextFormat::GREEN,
            3 => TextFormat::BLUE,
            default => TextFormat::GRAY,
        };
    }

    public function getItem() : BlazeRod {
        return VanillaItems::BLAZE_ROD();
    }

    public function getName(bool $locked = false) : string {
//        if($locked) {
//            return TextFormat::RED . TextFormat::BOLD .  "???";
//        }
//        if($this->isStarted() || $this->isOpenForJoining()) {
//            return TextFormat::RED . TextFormat::BOLD . "Hades";
//        }
//        return TextFormat::RED . TextFormat::BOLD .  "???";
        return TextFormat::RED . TextFormat::BOLD . "Hades";
    }

    public function getColor() : string {
        return TextFormat::RED;
    }

    public function canAccess(NexusPlayer $player) : bool {
        if(!$player->isLoaded()) {
            return false;
        }
        return $player->getDataSession()->getPrestige() > 0 or $player->getDataSession()->getTotalXPLevel() >= $this->getLevel();
    }

    public function getLevel() : int {
        return 30;
    }

    public function getSummonTask() : ?BossSummonTask {
        return $this->summonTask;
    }

}