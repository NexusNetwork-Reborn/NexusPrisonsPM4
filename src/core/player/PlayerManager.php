<?php

namespace core\player;

use core\game\item\enchantment\Enchantment;
use core\game\item\enchantment\EnchantmentManager;
use core\game\item\sets\utils\SetUtils;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Pickaxe;
use core\level\block\Meteor;
use core\level\block\Ore;
use core\level\tile\Meteorite;
use core\player\gang\GangException;
use core\player\gang\GangManager;
use core\Nexus;
use core\player\rank\RankException;
use core\player\rank\RankManager;
use core\player\rpg\RPGManager;
use core\player\task\ExecutiveMineHeartbeatTask;
use core\player\task\JetHeartbeatTask;
use core\player\task\JetParticleHeartbeatTask;
use core\player\task\LoadQueueTask;
use core\player\task\SavePlayersTask;
use core\player\task\SetGuardTaxHeartbeatTask;
use core\player\vault\VaultManager;
use Exception;
use pocketmine\block\Block;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\utils\Config;

class PlayerManager {

    /** @var Nexus */
    private $core;

    /** @var CESession[] */
    private $ceSessions = [];

    /** @var string[] */
    private $nicknames = [];

    /** @var GangManager */
    private $gangManager;

    /** @var RankManager */
    private $rankManager;

    /** @var RPGManager */
    private $rpgManager;

    /** @var VaultManager */
    private $vaultManager;

    /** @var LoadQueueTask */
    private $loadQueue;

    /**
     * @param NexusPlayer $player
     * @param Block $block
     *
     * @return int
     */
    public static function calculateBlockBreakTime(NexusPlayer $player, Block $block): int {
        $item = $player->getInventory()->getItemInHand();
        $breakTime = ceil($block->getBreakInfo()->getBreakTime($item) * 20);
        $session = null;
        if($player->hasCESession()) $session = $player->getCESession();
        if($player->getEffects()->has(VanillaEffects::HASTE())) {
            $breakTime /= 1 + (.5 * $player->getEffects()->get(VanillaEffects::HASTE())->getEffectLevel());
        }
        if($player->isUnderwater()) {
            $breakTime *= 5;
        }
        if(!$player->isOnGround()) {
            $breakTime *= 5;
        }
        $level = $block->getPosition()->getWorld();
        if($level !== null and $level->getTile($block->getPosition()) instanceof Meteorite) {
            return 30;
        }
        if($block instanceof Meteor) {
            if($item->hasEnchantment(EnchantmentManager::getEnchantment(Enchantment::METEOR_HUNTER))) {
                $breakTime /= 1 + $item->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::METEOR_HUNTER));
            }
        }
        if($block instanceof Ore) {
            if($session !== null && $session->hasExplode()) {
                return 1;
            }
            if($session !== null && $session->hasSuperBreaker() and $item->hasEnchantment(EnchantmentManager::getEnchantment(Enchantment::SUPER_BREAKER))) {
                return 2;
            }
            $breakTime *= 4;
            if($session !== null && $session->hasMomentum()) {
                $breakTime *= (1 - ($session->getMomentumSpeed() / 100));
            }
            if($item instanceof Pickaxe) {
                $grinder = $item->getAttribute(Pickaxe::GRINDER);
                $breakTime *= (1 - ($grinder / 100));
            }
        }

        foreach ($player->getArmorInventory()->getContents() as $armor) {
            if($armor instanceof Armor) {
                $level = $player->getInventory()->getItemInHand()->getEnchantmentLevel(EnchantmentManager::getEnchantment(Enchantment::DOUBLE_SWING));

                if($level > 0) {
                    $random = mt_rand(1, 500);
                    $chance = $level * $player->getCESession()->getArmorLuckModifier();

                    if($chance > $random) {
                        $breakTime *= 0.5;
                    }
                }
            }
        }

        if(SetUtils::isWearingFullSet($player, "demolition")) {
            $breakTime *= 0.5;
        }
        
        return ceil($breakTime);
    }

    /**
     * VaultManager constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $core->getServer()->getPluginManager()->registerEvents(new PlayerListener($core), $core);
        try {
            $this->init();
        }
        catch(Exception $exception) {
            $this->core->getLogger()->error("Failed to load the manager for players!");
            $this->core->getLogger()->logException($exception);
        }
    }

    /**
     * @throws GangException
     * @throws RankException
     */
    public function init(): void {
        $config = new Config($this->core->getDataFolder() . "nicks.json", Config::JSON);
        foreach($config->getAll() as $player => $nick) {
            $this->nicknames[$player] = $nick;
        }
        $this->gangManager = new GangManager($this->core);
        $this->rankManager = new RankManager($this->core);
        $this->rpgManager = new RPGManager($this->core);
        $this->vaultManager = new VaultManager($this->core);
        $this->loadQueue = new LoadQueueTask();
        $this->core->getScheduler()->scheduleRepeatingTask(new JetHeartbeatTask($this->core), 2);
        $this->core->getScheduler()->scheduleRepeatingTask(new SetGuardTaxHeartbeatTask($this->core), 1);
        $this->core->getScheduler()->scheduleRepeatingTask($this->loadQueue, 10);
        $this->core->getScheduler()->scheduleRepeatingTask(new SavePlayersTask($this->core), 6000);
        $this->core->getScheduler()->scheduleRepeatingTask(new ExecutiveMineHeartbeatTask($this->core), 30 * 20);

    }

    public function saveNicknames(): void {
        $config = new Config($this->core->getDataFolder() . "nicks.json", Config::JSON);
        $config->setAll($this->nicknames);
        $config->save();
    }

    /**
     * @param string $prefix
     *
     * @return array
     */
    public function getPlayersByNickname(string $prefix): array {
        $found = [];
        $prefix = strtolower($prefix);
        foreach($this->nicknames as $player => $nickname) {
            if(stripos($nickname, $prefix) === 0){
                $found[$nickname] = $player;
            }
        }
        return $found;
    }

    /**
     * @param NexusPlayer $player
     *
     * @return string|null
     */
    public function getNickname(NexusPlayer $player): ?string {
        return $this->nicknames[$player->getName()] ?? null;
    }


    /**
     * @param string $name
     *
     * @return bool
     */
    public function nicknameExist(string $name): bool {
        return in_array($name, $this->nicknames);
    }

    /**
     * @param NexusPlayer $player
     * @param string $name
     */
    public function setNickname(NexusPlayer $player, string $name): void {
        $display = "~$name";
        $this->nicknames[$player->getName()] = $name;
        $player->setDisplayName($display);
    }

    /**
     * @param NexusPlayer $player
     */
    public function removeNickname(NexusPlayer $player): void {
        if(isset($this->nicknames[$player->getName()])) {
            unset($this->nicknames[$player->getName()]);
            $player->setDisplayName($player->getName());
        }
    }

    /**
     * @param NexusPlayer $player
     *
     * @return CESession
     */
    public function getCESession(NexusPlayer $player): CESession {
        if(isset($this->ceSessions[$player->getUniqueId()->toString()])) {
            $this->ceSessions[$player->getUniqueId()->toString()]->setOwner($player);
            return $this->ceSessions[$player->getUniqueId()->toString()];
        }
        $session = new CESession($player);
        $this->ceSessions[$player->getUniqueId()->toString()] = $session;
        return $session;
    }

    /**
     * @param CESession $session
     */
    public function setCESession(CESession $session): void {
        $this->ceSessions[$session->getOwner()->getUniqueId()->toString()] = $session;
    }

    /**
     * @return GangManager
     */
    public function getGangManager(): GangManager {
        return $this->gangManager;
    }

    /**
     * @return RankManager
     */
    public function getRankManager(): RankManager {
        return $this->rankManager;
    }

    /**
     * @return RPGManager
     */
    public function getRPGManager(): RPGManager {
        return $this->rpgManager;
    }

    /**
     * @return VaultManager
     */
    public function getVaultManager(): VaultManager {
        return $this->vaultManager;
    }

    /**
     * @return LoadQueueTask
     */
    public function getLoadQueue(): LoadQueueTask {
        return $this->loadQueue;
    }
}