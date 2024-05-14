<?php
declare(strict_types=1);

namespace core\game\item\cluescroll;

use core\game\item\cluescroll\types\ApplyItemChallenge;
use core\game\item\cluescroll\types\DiscoverContrabandChallenge;
use core\game\item\cluescroll\types\FailEnchantmentChallenge;
use core\game\item\cluescroll\types\KillMerchantChallenge;
use core\game\item\cluescroll\types\LoseCoinFlipChallenge;
use core\game\item\cluescroll\types\OpenContrabandChallenge;
use core\game\item\cluescroll\types\OpenShardChallenge;
use core\game\item\cluescroll\types\TinkerEquipmentChallenge;
use core\game\item\cluescroll\types\TradeItemChallenge;
use core\game\item\cluescroll\types\WinCoinFlipChallenge;
use core\game\item\ItemException;
use core\game\item\types\custom\Absorber;
use core\game\item\types\custom\ChargeOrb;
use core\game\item\types\custom\ChargeOrbSlot;
use core\game\item\types\custom\ClueScroll;
use core\game\item\types\custom\EnergyBooster;
use core\game\item\types\custom\WhiteScroll;
use core\game\item\types\custom\XPBooster;
use core\game\item\types\Rarity;
use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\item\Item;

class ScrollManager {

    /** @var Nexus */
    private $core;

    /** @var Challenge[] */
    private $challenges = [];

    /** @var int */
    private $challengeBaseId = 0;

    /**
     * PassManager constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $this->init();
        $core->getServer()->getPluginManager()->registerEvents(new ScrollListener($core), $core);
    }

    public function init(): void {
        $rarities = [
            Rarity::SIMPLE,
            Rarity::UNCOMMON,
            Rarity::ELITE,
            Rarity::ULTIMATE,
            Rarity::LEGENDARY,
            Rarity::GODLY
        ];
        foreach($rarities as $rarity) {
            $this->addChallenge(new ApplyItemChallenge(++$this->challengeBaseId, Absorber::class, $rarity));
            $this->addChallenge(new ApplyItemChallenge(++$this->challengeBaseId, WhiteScroll::class, $rarity));
            $this->addChallenge(new ApplyItemChallenge(++$this->challengeBaseId, XPBooster::class, $rarity));
            $this->addChallenge(new DiscoverContrabandChallenge(++$this->challengeBaseId, $rarity));
            $this->addChallenge(new FailEnchantmentChallenge(++$this->challengeBaseId, $rarity));
            $this->addChallenge(new LoseCoinFlipChallenge(++$this->challengeBaseId, $rarity));
            $this->addChallenge(new OpenContrabandChallenge(++$this->challengeBaseId, $rarity));
            $this->addChallenge(new OpenShardChallenge(++$this->challengeBaseId, $rarity));
            $this->addChallenge(new TinkerEquipmentChallenge(++$this->challengeBaseId, $rarity));
            $this->addChallenge(new TradeItemChallenge(++$this->challengeBaseId, $rarity));
            $this->addChallenge(new WinCoinFlipChallenge(++$this->challengeBaseId, $rarity));
        }
        foreach($rarities as $rarity) {
            $this->addChallenge(new KillMerchantChallenge(++$this->challengeBaseId, $rarity));
        }
    }

    /**
     * @param NexusPlayer $player
     *
     * @return Item[]
     */
    public static function getScrolls(NexusPlayer $player): array {
        $scrolls = [];
        if($player->isClosed()) {
            return [];
        }
        foreach($player->getInventory()->getContents() as $item) {
            if(ClueScroll::isInstanceOf($item)) {
                $scrolls[] = $item;
            }
        }
        return $scrolls;
    }

    /**
     * @return Challenge[]
     */
    public function getChallenges(): array {
        return $this->challenges;
    }

    /**
     * @param int $id
     *
     * @return Challenge|null
     */
    public function getChallenge(int $id): ?Challenge {
        return $this->challenges[$id] ?? null;
    }

    /**
     * @param string $rarity
     *
     * @return Challenge|null
     */
    public function getRandomChallengeByRarity(string $rarity): ?Challenge {
        $challenges = $this->challenges[$rarity] ?? [];
        if(empty($challenges)) {
            return null;
        }
        return $challenges[array_rand($challenges)];
    }

    /**
     * @param Challenge $challenge
     *
     * @throws ItemException
     */
    public function addChallenge(Challenge $challenge): void {
        if(isset($this->challenges[$challenge->getId()])) {
            throw new ItemException("Attempt to override an existing challenge with the id of: " . $challenge->getName());
        }
        $this->challenges[$challenge->getId()] = $challenge;
        $this->challenges[$challenge->getRarity()][] = $challenge;
    }
}