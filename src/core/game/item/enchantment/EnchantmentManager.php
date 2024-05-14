<?php
declare(strict_types=1);

namespace core\game\item\enchantment;

use core\game\item\enchantment\task\ShowMomentumTask;
use core\game\item\enchantment\types\armor\AcidBloodEnchantment;
use core\game\item\enchantment\types\armor\AdrenalineEnchantment;
use core\game\item\enchantment\types\armor\AegisEnchantment;
use core\game\item\enchantment\types\armor\AntiGuardEnchantment;
use core\game\item\enchantment\types\armor\AntiVirusEnchantment;
use core\game\item\enchantment\types\armor\ArmoredEnchantment;
use core\game\item\enchantment\types\armor\BloodMagicEnchantment;
use core\game\item\enchantment\types\armor\BloodMoneyEnchantment;
use core\game\item\enchantment\types\armor\CactusEnchantment;
use core\game\item\enchantment\types\armor\ChivalryEnchantment;
use core\game\item\enchantment\types\armor\ConsecrationEnchantment;
use core\game\item\enchantment\types\armor\CrouchEnchantment;
use core\game\item\enchantment\types\armor\CurseEnchantment;
use core\game\item\enchantment\types\armor\DamageLimiterEnchantment;
use core\game\item\enchantment\types\armor\DeflectEnchantment;
use core\game\item\enchantment\types\armor\ElementalMasteryEnchantment;
use core\game\item\enchantment\types\armor\EnlightedEnchantment;
use core\game\item\enchantment\types\armor\EscapistEnchantment;
use core\game\item\enchantment\types\armor\ExperienceAbsorptionEnchantment;
use core\game\item\enchantment\types\armor\ExtinguishEnchantment;
use core\game\item\enchantment\types\armor\FattyEnchantment;
use core\game\item\enchantment\types\armor\FinalStandEnchantment;
use core\game\item\enchantment\types\armor\GearsEnchantment;
use core\game\item\enchantment\types\armor\GodlyOverloadEnchantment;
use core\game\item\enchantment\types\armor\GuardDeflectEnchantment;
use core\game\item\enchantment\types\armor\HexCurseEnchantment;
use core\game\item\enchantment\types\armor\HoudiniEnchantment;
use core\game\item\enchantment\types\armor\InfernoEnchantment;
use core\game\item\enchantment\types\armor\LastStandEnchantment;
use core\game\item\enchantment\types\armor\LeviathanBloodEnchantment;
use core\game\item\enchantment\types\armor\ManeuverEnchantment;
use core\game\item\enchantment\types\armor\OverloadEnchantment;
use core\game\item\enchantment\types\armor\PainkillerEnchantment;
use core\game\item\enchantment\types\armor\ShockwaveEnchantment;
use core\game\item\enchantment\types\armor\SpringsEnchantment;
use core\game\item\enchantment\types\armor\SystemRebootEnchantment;
use core\game\item\enchantment\types\armor\TankEnchantment;
use core\game\item\enchantment\types\armor\TitanBloodEnchantment;
use core\game\item\enchantment\types\armor\TitanExecutionerEnchantment;
use core\game\item\enchantment\types\armor\ToxicMistEnchantment;
use core\game\item\enchantment\types\armor\UnknownEnchantment;
use core\game\item\enchantment\types\armor\VoodooEnchantment;
use core\game\item\enchantment\types\bow\FootrotEnchantment;
use core\game\item\enchantment\types\bow\MalnourishedEnchantment;
use core\game\item\enchantment\types\bow\TrinketBlockEnchantment;
use core\game\item\enchantment\types\EternalLuckEnchantment;
use core\game\item\enchantment\types\HardenedEnchantment;
use core\game\item\enchantment\types\LuckyEnchantment;
use core\game\item\enchantment\types\pickaxe\AlchemyEnchantment;
use core\game\item\enchantment\types\pickaxe\EfficiencyEnchantment;
use core\game\item\enchantment\types\pickaxe\EnergizeEnchantment;
use core\game\item\enchantment\types\pickaxe\EnergyCollectorEnchantment;
use core\game\item\enchantment\types\pickaxe\EnergyHoarderEnchantment;
use core\game\item\enchantment\types\pickaxe\EnrichEnchantment;
use core\game\item\enchantment\types\pickaxe\ExplodeEnchantment;
use core\game\item\enchantment\types\pickaxe\FeedEnchantment;
use core\game\item\enchantment\types\pickaxe\MeteorHunterEnchantment;
use core\game\item\enchantment\types\pickaxe\MeticulousEfficiencyEnchantment;
use core\game\item\enchantment\types\pickaxe\MomentumEnchantment;
use core\game\item\enchantment\types\pickaxe\OreMagnetEnchantment;
use core\game\item\enchantment\types\pickaxe\PowerballEnchantment;
use core\game\item\enchantment\types\pickaxe\ReplenishEnchantment;
use core\game\item\enchantment\types\pickaxe\ShardDiscovererEnchantment;
use core\game\item\enchantment\types\pickaxe\SixthSenseEnchantment;
use core\game\item\enchantment\types\pickaxe\SuperBreakerEnchantment;
use core\game\item\enchantment\types\pickaxe\TimeWarpEnchantment;
use core\game\item\enchantment\types\pickaxe\TransfuseEnchantment;
use core\game\item\enchantment\types\pickaxe\WarpMinerEnchantment;
use core\game\item\enchantment\types\RockHardEnchantment;
use core\game\item\enchantment\types\satchel\AutoSellEnchantment;
use core\game\item\enchantment\types\satchel\DoubleDropEnchantment;
use core\game\item\enchantment\types\satchel\EnergyMirrorEnchantment;
use core\game\item\enchantment\types\satchel\SnatchEnchantment;
use core\game\item\enchantment\types\weapon\AntiGankEnchantment;
use core\game\item\enchantment\types\armor\DoubleSwingEnchantment;
use core\game\item\enchantment\types\weapon\axe\AxemanEnchantment;
use core\game\item\enchantment\types\weapon\axe\BerserkEnchantment;
use core\game\item\enchantment\types\weapon\axe\BleedEnchantment;
use core\game\item\enchantment\types\weapon\axe\BloodlustEnchantment;
use core\game\item\enchantment\types\weapon\axe\DeepWoundsEnchantment;
use core\game\item\enchantment\types\weapon\axe\DisintegrateEnchantment;
use core\game\item\enchantment\types\weapon\axe\FamineEnchantment;
use core\game\item\enchantment\types\weapon\axe\GuardLockEnchantment;
use core\game\item\enchantment\types\weapon\axe\ImpactEnchantment;
use core\game\item\enchantment\types\weapon\axe\PlaguedSmiteEnchantment;
use core\game\item\enchantment\types\weapon\axe\RejuvenateEnchantment;
use core\game\item\enchantment\types\weapon\axe\WeaknessEnchantment;
use core\game\item\enchantment\types\weapon\axe\WhirlwindEnchantment;
use core\game\item\enchantment\types\weapon\BlazeEnchantment;
use core\game\item\enchantment\types\weapon\CannibalismEnchantment;
use core\game\item\enchantment\types\weapon\DemonicFrenzyEnchantment;
use core\game\item\enchantment\types\weapon\EndlessPummelEnchantment;
use core\game\item\enchantment\types\weapon\EnrageEnchantment;
use core\game\item\enchantment\types\weapon\ExecuteEnchantment;
use core\game\item\enchantment\types\weapon\FearlessEnchantment;
use core\game\item\enchantment\types\weapon\FrenzyEnchantment;
use core\game\item\enchantment\types\weapon\FrostbladeEnchantment;
use core\game\item\enchantment\types\weapon\LightningEnchantment;
use core\game\item\enchantment\types\weapon\PerfectStrikeEnchantment;
use core\game\item\enchantment\types\weapon\PummelEnchantment;
use core\game\item\enchantment\types\weapon\SilenceEnchantment;
use core\game\item\enchantment\types\weapon\sword\DeadlyDominationEnchantment;
use core\game\item\enchantment\types\weapon\sword\DemonForgedEnchantment;
use core\game\item\enchantment\types\weapon\sword\DivineLightningEnchantment;
use core\game\item\enchantment\types\weapon\sword\DominateEnchantment;
use core\game\item\enchantment\types\weapon\sword\DragonForgedEnchantment;
use core\game\item\enchantment\types\weapon\sword\FlingEnchantment;
use core\game\item\enchantment\types\weapon\sword\HookEnchantment;
use core\game\item\enchantment\types\weapon\sword\LifestealEnchantment;
use core\game\item\enchantment\types\weapon\sword\MolecularHarvesterEnchantment;
use core\game\item\enchantment\types\weapon\sword\PoisonEnchantment;
use core\game\item\enchantment\types\weapon\sword\ScorchEnchantment;
use core\game\item\enchantment\types\weapon\sword\SolitudeEnchantment;
use core\game\item\enchantment\types\weapon\sword\SwordsmanEnchantment;
use core\game\item\enchantment\types\weapon\sword\ThousandCutsEnchantment;
use core\game\item\enchantment\types\weapon\SystemElectrocutionEnchantment;
use core\game\item\enchantment\types\weapon\TitanTrapEnchantment;
use core\game\item\enchantment\types\weapon\TrapEnchantment;
use core\game\item\types\custom\Satchel;
use core\game\item\types\Rarity;
use core\game\item\types\vanilla\Armor;
use core\game\item\types\vanilla\Axe;
use core\game\item\types\vanilla\Bow;
use core\game\item\types\vanilla\Pickaxe;
use core\game\item\types\vanilla\Sword;
use core\Nexus;
use libs\utils\Utils;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;
use core\game\item\enchantment\types\armor\LeechEnchantment;
use core\game\item\enchantment\types\weapon\BountyHunterEnchantment;

class EnchantmentManager {

    /** @var Nexus */
    private $core;

    /** @var Enchantment[] */
    private static $enchantments = [];

    /** @var Enchantment[] */
    private static $enchantmentsClassifiedByName = [];

    /** @var array */
    private static $classifiedEnchantments = [];

    /**
     * ItemManager constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $core->getServer()->getPluginManager()->registerEvents(new EnchantmentListener($core), $core);
        $this->core->getScheduler()->scheduleRepeatingTask(new ShowMomentumTask($core), 20);
        $this->init();
    }

    public function init() {
        self::registerEnchantment(new AcidBloodEnchantment());
        self::registerEnchantment(new AdrenalineEnchantment());
        self::registerEnchantment(new AegisEnchantment());
        self::registerEnchantment(new AntiGuardEnchantment());
        self::registerEnchantment(new AntiVirusEnchantment());
        self::registerEnchantment(new ArmoredEnchantment());
        self::registerEnchantment(new BloodMagicEnchantment());
        self::registerEnchantment(new CactusEnchantment());
        self::registerEnchantment(new ChivalryEnchantment());
        self::registerEnchantment(new ConsecrationEnchantment());
        self::registerEnchantment(new CrouchEnchantment());
        self::registerEnchantment(new CurseEnchantment());
        self::registerEnchantment(new DamageLimiterEnchantment());
        self::registerEnchantment(new DeflectEnchantment());
        self::registerEnchantment(new ElementalMasteryEnchantment());
        self::registerEnchantment(new EnlightedEnchantment());
        self::registerEnchantment(new EscapistEnchantment());
        self::registerEnchantment(new ExtinguishEnchantment());
        self::registerEnchantment(new FattyEnchantment());
        self::registerEnchantment(new FinalStandEnchantment());
        self::registerEnchantment(new GearsEnchantment());
        self::registerEnchantment(new GodlyOverloadEnchantment());
        self::registerEnchantment(new GuardDeflectEnchantment());
        self::registerEnchantment(new HexCurseEnchantment());
        self::registerEnchantment(new HoudiniEnchantment());
        self::registerEnchantment(new InfernoEnchantment());
        self::registerEnchantment(new LastStandEnchantment());
        self::registerEnchantment(new ManeuverEnchantment());
        self::registerEnchantment(new OverloadEnchantment());
        self::registerEnchantment(new PainkillerEnchantment());
        self::registerEnchantment(new ShockwaveEnchantment());
        self::registerEnchantment(new SpringsEnchantment());
        self::registerEnchantment(new SystemRebootEnchantment());
        self::registerEnchantment(new TankEnchantment());
        self::registerEnchantment(new TitanBloodEnchantment());
        self::registerEnchantment(new ToxicMistEnchantment());
        self::registerEnchantment(new UnknownEnchantment());
        self::registerEnchantment(new VoodooEnchantment());
        self::registerEnchantment(new DoubleSwingEnchantment());
        self::registerEnchantment(new ExperienceAbsorptionEnchantment());
        self::registerEnchantment(new LeviathanBloodEnchantment());
        self::registerEnchantment(new TitanExecutionerEnchantment());
        self::registerEnchantment(new BloodMoneyEnchantment());
        self::registerEnchantment(new LeechEnchantment());

        self::registerEnchantment(new AlchemyEnchantment());
        self::registerEnchantment(new EfficiencyEnchantment());
        self::registerEnchantment(new EnergizeEnchantment());
        self::registerEnchantment(new EnergyCollectorEnchantment());
        self::registerEnchantment(new EnergyHoarderEnchantment());
        self::registerEnchantment(new EnrichEnchantment());
        self::registerEnchantment(new ExplodeEnchantment());
        self::registerEnchantment(new FeedEnchantment());
        self::registerEnchantment(new MeteorHunterEnchantment());
        self::registerEnchantment(new MeticulousEfficiencyEnchantment());
        self::registerEnchantment(new MomentumEnchantment());
        self::registerEnchantment(new OreMagnetEnchantment());
        self::registerEnchantment(new PowerballEnchantment());
        self::registerEnchantment(new ReplenishEnchantment());
        self::registerEnchantment(new ShardDiscovererEnchantment());
        self::registerEnchantment(new SixthSenseEnchantment());
        self::registerEnchantment(new SuperBreakerEnchantment());
        self::registerEnchantment(new TimeWarpEnchantment());
        self::registerEnchantment(new TransfuseEnchantment());
        self::registerEnchantment(new WarpMinerEnchantment());

        self::registerEnchantment(new AutoSellEnchantment());
        self::registerEnchantment(new DoubleDropEnchantment());
        self::registerEnchantment(new EnergyMirrorEnchantment());
        self::registerEnchantment(new SnatchEnchantment());

        self::registerEnchantment(new AxemanEnchantment());
        self::registerEnchantment(new BerserkEnchantment());
        self::registerEnchantment(new BleedEnchantment());
        self::registerEnchantment(new BloodlustEnchantment());
        self::registerEnchantment(new DeepWoundsEnchantment());
        self::registerEnchantment(new DisintegrateEnchantment());
        self::registerEnchantment(new FamineEnchantment());
        self::registerEnchantment(new GuardLockEnchantment());
        self::registerEnchantment(new ImpactEnchantment());
        self::registerEnchantment(new RejuvenateEnchantment());
        self::registerEnchantment(new WeaknessEnchantment());
        self::registerEnchantment(new WhirlwindEnchantment());
        self::registerEnchantment(new PlaguedSmiteEnchantment());

        self::registerEnchantment(new DemonForgedEnchantment());
        self::registerEnchantment(new DivineLightningEnchantment());
        self::registerEnchantment(new DominateEnchantment());
        self::registerEnchantment(new DragonForgedEnchantment());
        self::registerEnchantment(new FlingEnchantment());
        self::registerEnchantment(new HookEnchantment());
        self::registerEnchantment(new LifestealEnchantment());
        self::registerEnchantment(new MolecularHarvesterEnchantment());
        self::registerEnchantment(new PoisonEnchantment());
        self::registerEnchantment(new ScorchEnchantment());
        self::registerEnchantment(new SolitudeEnchantment());
        self::registerEnchantment(new SwordsmanEnchantment());
        self::registerEnchantment(new ThousandCutsEnchantment());
        self::registerEnchantment(new DeadlyDominationEnchantment());

        self::registerEnchantment(new AntiGankEnchantment());
        self::registerEnchantment(new BlazeEnchantment());
        self::registerEnchantment(new CannibalismEnchantment());
        self::registerEnchantment(new DemonicFrenzyEnchantment());
        self::registerEnchantment(new EndlessPummelEnchantment());
        self::registerEnchantment(new EnrageEnchantment());
        self::registerEnchantment(new ExecuteEnchantment());
        self::registerEnchantment(new FearlessEnchantment());
        self::registerEnchantment(new FrenzyEnchantment());
        self::registerEnchantment(new FrostbladeEnchantment());
        self::registerEnchantment(new LightningEnchantment());
        self::registerEnchantment(new PummelEnchantment());
        self::registerEnchantment(new SilenceEnchantment());
        self::registerEnchantment(new SystemElectrocutionEnchantment());
        self::registerEnchantment(new TitanTrapEnchantment());
        self::registerEnchantment(new TrapEnchantment());
        self::registerEnchantment(new PerfectStrikeEnchantment());
        self::registerEnchantment(new BountyHunterEnchantment());

        self::registerEnchantment(new FootrotEnchantment());
        self::registerEnchantment(new MalnourishedEnchantment());
        self::registerEnchantment(new TrinketBlockEnchantment());

        self::registerEnchantment(new EternalLuckEnchantment());
        self::registerEnchantment(new HardenedEnchantment());
        self::registerEnchantment(new LuckyEnchantment());
        self::registerEnchantment(new RockHardEnchantment());
    }

    /**
     * @return Enchantment[]
     */
    public static function getEnchantments(): array {
        return self::$enchantments;
    }

    /**
     * @param $identifier
     *
     * @return Enchantment|null
     */
    public static function getEnchantment($identifier): ?Enchantment {
        return self::$enchantments[$identifier] ?? self::$enchantmentsClassifiedByName[$identifier] ?? null;
    }

    /**
     * @param int|null $rarity
     * @param int $exclude
     *
     * @return Enchantment
     */
    public static function getRandomEnchantment(?int $rarity = null): Enchantment {
        if($rarity !== null) {
            /** @var \pocketmine\item\enchantment\Enchantment[] $enchantments */
            $enchantments = self::$classifiedEnchantments[$rarity];
            return $enchantments[array_rand($enchantments)];
        }
        return self::$enchantments[array_rand(self::$enchantments)];
    }

    /**
     * @param int|null $rarity
     * @param int|null $flag
     * @param bool $random
     *
     * @return Enchantment
     */
    public static function getRandomFightingEnchantment(?int $rarity = null, ?int $flag = null, $random = false): Enchantment {
        if($flag !== null and $flag !== Enchantment::SLOT_AXE and $flag !== Enchantment::SLOT_SWORD and $flag !== Enchantment::SLOT_BOW and $flag !== Enchantment::SLOT_ARMOR) {
            throw new \UnexpectedValueException("Expected a fighting type flag. Got: $flag");
        }
        $enchantment = self::$enchantments[array_rand(self::$enchantments)];
        if($rarity !== null and $random === false) {
            /** @var \pocketmine\item\enchantment\Enchantment[] $enchantments */
            $enchantments = self::$classifiedEnchantments[$rarity];
            $enchantment = $enchantments[array_rand($enchantments)];
        }
        if($flag === null) {
            if($enchantment->getPrimaryItemFlags() === Enchantment::SLOT_AXE or $enchantment->getPrimaryItemFlags() === Enchantment::SLOT_SWORD or
                $enchantment->getPrimaryItemFlags() === Enchantment::SLOT_BOW or $enchantment->getPrimaryItemFlags() === Enchantment::SLOT_ARMOR or
                $enchantment->getPrimaryItemFlags() === Enchantment::SLOT_HEAD or $enchantment->getPrimaryItemFlags() === Enchantment::SLOT_TORSO or
                $enchantment->getPrimaryItemFlags() === Enchantment::SLOT_LEGS or $enchantment->getPrimaryItemFlags() === Enchantment::SLOT_FEET or
                $enchantment->getSecondaryItemFlags() === Enchantment::SLOT_AXE or $enchantment->getSecondaryItemFlags() === Enchantment::SLOT_SWORD or
                $enchantment->getSecondaryItemFlags() === Enchantment::SLOT_BOW or $enchantment->getSecondaryItemFlags() === Enchantment::SLOT_ARMOR or
                $enchantment->getSecondaryItemFlags() === Enchantment::SLOT_HEAD or $enchantment->getSecondaryItemFlags() === Enchantment::SLOT_TORSO or
                $enchantment->getSecondaryItemFlags() === Enchantment::SLOT_LEGS or $enchantment->getSecondaryItemFlags() === Enchantment::SLOT_FEET or
                $enchantment->getPrimaryItemFlags() === Enchantment::SLOT_ALL or $enchantment->getSecondaryItemFlags() === Enchantment::SLOT_ALL
            ) {
                if(($random === true and $enchantment->getRarity() <= $rarity) or $random === false) {
                    return $enchantment;
                }
            }
        }
        else {
            if($enchantment->getPrimaryItemFlags() === $flag or $enchantment->getSecondaryItemFlags() === $flag) {
                if(($random === true and $enchantment->getRarity() <= $rarity) or $random === false) {
                    return $enchantment;
                }
            }
        }
        return self::getRandomFightingEnchantment($rarity, $flag, $random);
    }

    /**
     * @param int|null $rarity
     * @param int|null $flag
     * @param bool $random
     *
     * @return Enchantment
     */
    public static function getRandomMiningEnchantment(?int $rarity = null, ?int $flag = null, $random = false): Enchantment {
        if($flag !== null and $flag !== Enchantment::SLOT_PICKAXE and $flag !== Enchantment::SLOT_SATCHEL) {
            throw new \UnexpectedValueException("Expected a mining type flag. Got: $flag");
        }
        $enchantment = self::$enchantments[array_rand(self::$enchantments)];
        if($rarity !== null and $random === false) {
            /** @var \pocketmine\item\enchantment\Enchantment[] $enchantments */
            $enchantments = self::$classifiedEnchantments[$rarity];
            $enchantment = $enchantments[array_rand($enchantments)];
        }
        if($flag === null) {
            if($enchantment->getPrimaryItemFlags() === Enchantment::SLOT_PICKAXE or $enchantment->getPrimaryItemFlags() === Enchantment::SLOT_SATCHEL) {
                if(($random === true and $enchantment->getRarity() <= $rarity) or $random === false) {
                    return $enchantment;
                }
            }
        }
        else {
            if($enchantment->getPrimaryItemFlags() === $flag) {
                if(($random === true and $enchantment->getRarity() <= $rarity) or $random === false) {
                    return $enchantment;
                }
            }
        }
        return self::getRandomMiningEnchantment($rarity, $flag, $random);
    }

    /**
     * @param Enchantment $enchantment
     */
    public static function registerEnchantment(Enchantment $enchantment): void {
        EnchantmentIdMap::getInstance()->register($enchantment->getRuntimeId(), $enchantment);
        self::$enchantments[$enchantment->getRuntimeId()] = $enchantment;
        self::$enchantmentsClassifiedByName[$enchantment->getName()] = $enchantment;
        self::$classifiedEnchantments[$enchantment->getRarity()][] = $enchantment;
    }

    /**
     * @param int $integer
     *
     * @return string
     */
    public static function getRomanNumber(int $integer): string {
        if($integer <= 0) {
            return (string)$integer;
        }
        $characters = [
            'M' => 1000,
            'CM' => 900,
            'D' => 500,
            'CD' => 400,
            'C' => 100,
            'XC' => 90,
            'L' => 50,
            'XL' => 40,
            'X' => 10,
            'IX' => 9,
            'V' => 5,
            'IV' => 4,
            'I' => 1
        ];
        $romanString = "";
        while($integer > 0) {
            foreach($characters as $rom => $arb) {
                if($integer >= $arb) {
                    $integer -= $arb;
                    $romanString .= $rom;
                    break;
                }
            }
        }
        return $romanString;
    }

    /**
     * @param \pocketmine\item\enchantment\Enchantment $enchantment
     *
     * @return Enchantment|null
     */
    public static function getExecutiveEnchantmentByPremature(\pocketmine\item\enchantment\Enchantment $enchantment): ?Enchantment {
        foreach(self::getEnchantments() as $e) {
            if($e instanceof Enchantment) {
                $premature = $e->getPremature();
                if($premature !== null) {
                    if(EnchantmentIdMap::getInstance()->toId($enchantment) === $premature) {
                        return $e;
                    }
                }
            }
        }
        return null;
    }

    /**
     * @param Item $item
     * @param \pocketmine\item\enchantment\Enchantment $enchantment
     *
     * @return bool
     */
    public static function canEnchant(Item $item, \pocketmine\item\enchantment\Enchantment $enchantment): bool {
        if($enchantment instanceof Enchantment) {
            $premature = $enchantment->getPremature();
            if($premature !== null) {
                $premature = self::getEnchantment($premature);
                if($item->hasEnchantment($premature)) {
                    if($item->getEnchantmentLevel($premature) >= $premature->getMaxLevel()) {
                        return true;
                    }
                    return false;
                }
                elseif($item->hasEnchantment($enchantment)) {
                    if($item->getEnchantmentLevel($enchantment) < $enchantment->getMaxLevel()) {
                        return true;
                    }
                    return false;
                }
                else {
                    return false;
                }
            }
        }
        foreach($item->getEnchantments() as $e) {
            $e = $e->getType();
            if($e instanceof Enchantment) {
                $premature = $e->getPremature();
                if($premature !== null) {
                    if($enchantment->getRuntimeId() === $premature) {
                        return false;
                    }
                }
            }
        }
        if($item->hasEnchantment($enchantment)) {
            if($item->getEnchantmentLevel($enchantment) < $enchantment->getMaxLevel()) {
                return true;
            }
            return false;
        }
        return (self::flagsQualify($item, $enchantment->getPrimaryItemFlags()) or self::flagsQualify($item, $enchantment->getSecondaryItemFlags()));
    }

    /**
     * @param Item $item
     * @param int $flag
     *
     * @return bool
     */
    private static function flagsQualify(Item $item, int $flag): bool {
        switch($flag) {
            case Enchantment::SLOT_ALL:
                if($item instanceof Durable) {
                    return true;
                }
                break;
            case Enchantment::SLOT_FEET:
                if($item instanceof Armor and $item->getArmorSlot() === Armor::SLOT_BOOTS) {
                    return true;
                }
                break;
            case Enchantment::SLOT_HEAD:
                if($item instanceof Armor and $item->getArmorSlot() === Armor::SLOT_HEAD) {
                    return true;
                }
                break;
            case Enchantment::SLOT_TORSO:
                if($item instanceof Armor and $item->getArmorSlot() === Armor::SLOT_CHESTPLATE) {
                    return true;
                }
                break;
            case Enchantment::SLOT_LEGS:
                if($item instanceof Armor and $item->getArmorSlot() === Armor::SLOT_LEGGINGS) {
                    return true;
                }
                break;
            case Enchantment::SLOT_ARMOR:
                if($item instanceof Armor) {
                    return true;
                }
                break;
            case Enchantment::SLOT_SWORD:
                if($item instanceof Sword) {
                    return true;
                }
                break;
            case Enchantment::SLOT_AXE:
                if($item instanceof Axe) {
                    return true;
                }
                break;
            case Enchantment::SLOT_BOW:
                if($item instanceof Bow) {
                    return true;
                }
                break;
            case Enchantment::SLOT_PICKAXE:
                if($item instanceof Pickaxe) {
                    return true;
                }
                break;
            case Enchantment::SLOT_SATCHEL:
                if(Satchel::isInstanceOf($item)) {
                    return true;
                }
                break;
        }
        return false;
    }

    /**
     * @param int $flag
     *
     * @return string
     */
    public static function flagToString(int $flag): string {
        switch($flag) {
            case Enchantment::SLOT_FEET:
                return "Boots";
                break;
            case Enchantment::SLOT_TORSO:
                return "Chestplate";
                break;
            case Enchantment::SLOT_ARMOR:
                return "Armor";
                break;
            case Enchantment::SLOT_HEAD:
                return "Helmet";
                break;
            case Enchantment::SLOT_LEGS:
                return "Leggings";
                break;
            case Enchantment::SLOT_SWORD:
                return "Sword";
                break;
            case Enchantment::SLOT_BOW:
                return "Bow";
                break;
            case Enchantment::SLOT_PICKAXE:
                return "Pickaxe";
                break;
            case Enchantment::SLOT_AXE:
                return "Axe";
                break;
            case Enchantment::SLOT_SATCHEL:
                return "Satchel";
                break;
            case Enchantment::SLOT_ALL:
                return "Universal";
                break;
        }
        return "None";
    }


    /**
     * @param int $rarity
     *
     * @return string
     */
    public static function rarityToString(int $rarity): string {
        switch($rarity) {
            case Enchantment::SIMPLE:
                return "Common";
                break;
            case Enchantment::UNCOMMON:
                return "Uncommon";
                break;
            case Enchantment::ELITE:
                return "Elite";
                break;
            case Enchantment::ULTIMATE:
                return "Ultimate";
                break;
            case Enchantment::LEGENDARY:
                return "Legendary";
                break;
            case Enchantment::GODLY:
                return "Godly";
                break;
            case Enchantment::EXECUTIVE:
                return "Executive";
                break;
            case Enchantment::ENERGY:
                return "Energy";
                break;
            default:
                return "Unknown";
                break;
        }
    }

    /**
     * @param Item $item
     *
     * @return string[]
     */
    public static function getLoreForItem(Item $item): array {
        $simple = [];
        $uncommon = [];
        $elite = [];
        $ultimate = [];
        $legendary = [];
        $godly = [];
        $executive = [];
        $energy = [];
        foreach($item->getEnchantments() as $enchantment) {
            $type = $enchantment->getType();
            if($type instanceof Enchantment) {
                switch($type->getRarity()) {
                    case Enchantment::SIMPLE:
                        $simple[] = $enchantment;
                        break;
                    case Enchantment::UNCOMMON:
                        $uncommon[] = $enchantment;
                        break;
                    case Enchantment::ELITE:
                        $elite[] = $enchantment;
                        break;
                    case Enchantment::ULTIMATE:
                        $ultimate[] = $enchantment;
                        break;
                    case Enchantment::LEGENDARY:
                        $legendary[] = $enchantment;
                        break;
                    case Enchantment::GODLY:
                        $godly[] = $enchantment;
                        break;
                    case Enchantment::EXECUTIVE:
                        $executive[] = $enchantment;
                        break;
                    case Enchantment::ENERGY:
                        $energy[] = $enchantment;
                        break;
                    default:
                        break;
                }
            }
        }
        $lore = [];
        $enchantments = array_merge($energy, $executive, $godly, $legendary, $ultimate, $elite, $uncommon, $simple);
        foreach($enchantments as $enchantment) {
            $type = $enchantment->getType();
            if($type instanceof Enchantment) {
                $maxLevel = "";
                if($enchantment->getLevel() === $type->getMaxLevel()) {
                    $maxLevel = TextFormat::BOLD;
                }
                $text = TextFormat::RESET . $maxLevel . Rarity::RARITY_TO_COLOR_MAP[Rarity::ENCHANTMENT_RARITY_TO_STRING_MAP[$type->getRarity()]] . $type->getName() . " " . TextFormat::AQUA . self::getRomanNumber($enchantment->getLevel());
                if($item instanceof Pickaxe) {
                    $amount = 0;
                    if($type->getRuntimeId() === Enchantment::WARP_MINER) {
                        $amount = $enchantment->getLevel() * 2000;
                    }
                    if($type->getRuntimeId() === Enchantment::TIME_WARP) {
                        $amount = $enchantment->getLevel() * 4000;
                    }
                    if($amount > 0) {
                        $text .= TextFormat::RESET . TextFormat::GRAY . " (" . TextFormat::WHITE . number_format($item->getWarpMinerMined()) . TextFormat::GRAY . " / " . TextFormat::WHITE . number_format($amount) . TextFormat::GRAY . ")";
                    }
                }
                if($type->getRuntimeId() === Enchantment::TRINKET_BLOCK) {
                    $text .= TextFormat::RESET . TextFormat::GRAY . " (100k energy/shot)";
                }
                if($type->getRuntimeId() === Enchantment::SYSTEM_REBOOT) {
                    $price = 15000000 / $enchantment->getLevel();
                    $number = strtolower(Utils::shrinkNumber($price, 0));
                    $text .= TextFormat::RESET . TextFormat::GRAY . " ($number energy/activation)";
                }
                if($type->getRuntimeId() === Enchantment::DIVINE_LIGHTNING) {
                    $text .= TextFormat::RESET . TextFormat::GRAY . " (70k energy/swing)";
                }
                $lore[] = $text;
            }
        }
        return $lore;
    }

    /**
     * @param EnchantmentInstance[] $enchantments
     *
     * @return string[]
     */
    public static function getLoreByList(array $enchantments): array {
        $simple = [];
        $uncommon = [];
        $elite = [];
        $ultimate = [];
        $legendary = [];
        $godly = [];
        $executive = [];
        $energy = [];
        foreach($enchantments as $enchantment) {
            $type = $enchantment->getType();
            if($type instanceof Enchantment) {
                switch($type->getRarity()) {
                    case Enchantment::SIMPLE:
                        $simple[] = $enchantment;
                        break;
                    case Enchantment::UNCOMMON:
                        $uncommon[] = $enchantment;
                        break;
                    case Enchantment::ELITE:
                        $elite[] = $enchantment;
                        break;
                    case Enchantment::ULTIMATE:
                        $ultimate[] = $enchantment;
                        break;
                    case Enchantment::LEGENDARY:
                        $legendary[] = $enchantment;
                        break;
                    case Enchantment::GODLY:
                        $godly[] = $enchantment;
                        break;
                    case Enchantment::EXECUTIVE:
                        $executive[] = $enchantment;
                        break;
                    case Enchantment::ENERGY:
                        $energy[] = $enchantment;
                        break;
                    default:
                        break;
                }
            }
        }
        $lore = [];
        $enchantments = array_merge($energy, $executive, $godly, $legendary, $ultimate, $elite, $uncommon, $simple);
        foreach($enchantments as $enchantment) {
            $type = $enchantment->getType();
            if($type instanceof Enchantment) {
                $maxLevel = "";
                if($enchantment->getLevel() === $type->getMaxLevel()) {
                    $maxLevel = TextFormat::BOLD;
                }
                $text = TextFormat::RESET . $maxLevel . Rarity::RARITY_TO_COLOR_MAP[Rarity::ENCHANTMENT_RARITY_TO_STRING_MAP[$type->getRarity()]] . $type->getName() . " " . TextFormat::AQUA . self::getRomanNumber($enchantment->getLevel());
                $lore[] = $text;
            }
        }
        return $lore;
    }

    /**
     * @param EnchantmentInstance $enchantmentInstance
     *
     * @return int
     */
    public static function getNeededEnergy(EnchantmentInstance $enchantmentInstance): int {
        $type = $enchantmentInstance->getType();
        $level = $enchantmentInstance->getLevel();
        if($type instanceof Enchantment) {
            switch($type->getRarity()) {
                case Enchantment::SIMPLE:
                    return (int)($level / 0.0153) ** 2;
                    break;
                case Enchantment::UNCOMMON:
                    return (int)($level / 0.011025) ** 2;
                    break;
                case Enchantment::ELITE:
                    return (int)($level / 0.00475) ** 2;
                    break;
                case Enchantment::ULTIMATE:
                    return (int)($level / 0.00345) ** 2;
                    break;
                case Enchantment::LEGENDARY:
                    return (int)($level / 0.00096) ** 2;
                    break;
                case Enchantment::GODLY:
                    return (int)($level / 0.00047) ** 2;
                    break;
                case Enchantment::EXECUTIVE:
                case Enchantment::ENERGY:
                    return (int)($level / 0.000275) ** 2;
                    break;
                default:
                    break;
            }
        }
        return 0;
    }
}