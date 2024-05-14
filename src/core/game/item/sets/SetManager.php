<?php

declare(strict_types=1);

namespace core\game\item\sets;

use core\game\item\sets\type\DemolitionSet;
use core\game\item\sets\type\GhostSet;
use core\game\item\sets\type\KothSet;
use core\game\item\sets\type\LunacySet;
use core\game\item\sets\type\ObsidianSet;
use core\game\item\sets\type\PlagueDoctorSet;
use core\game\item\sets\type\SantaSet;
use core\game\item\sets\type\UnderlingSet;
use core\game\item\sets\type\YetiSet;
use core\game\item\types\vanilla\Armor;
use core\Nexus;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;

class SetManager
{

    const SET = "set";

    /** @var Set[] */
    private array $sets = [];

    /** @var Nexus */
    private Nexus $core;

    /**
     * @param Nexus $core
     */
    public function __construct(Nexus $core)
    {
        $this->core = $core;
        $this->init();
    }

    public function init(): void
    {
        ItemFactory::getInstance()->register(new Armor(ItemIds::LEATHER_HELMET, 364, 3, Armor::TIER_LEATHER, Armor::SLOT_HEAD, 41, "Leather Helmet"), true);
        ItemFactory::getInstance()->register(new Armor(ItemIds::LEATHER_CHESTPLATE, 529, 8, Armor::TIER_LEATHER, Armor::SLOT_CHESTPLATE, 41, "Leather Tunic"), true);
        ItemFactory::getInstance()->register(new Armor(ItemIds::LEATHER_LEGGINGS, 496, 6, Armor::TIER_LEATHER, Armor::SLOT_LEGGINGS, 41, "Leather Leggings"), true);
        ItemFactory::getInstance()->register(new Armor(ItemIds::LEATHER_BOOTS, 430, 3, Armor::TIER_LEATHER, Armor::SLOT_BOOTS, 41, "Leather Boots"), true);


        $this->addSet(new UnderlingSet());
        $this->addSet(new LunacySet());
        $this->addSet(new YetiSet());
        $this->addSet(new SantaSet());
        $this->addSet(new KothSet());
        $this->addSet(new PlagueDoctorSet());
        $this->addSet(new DemolitionSet());
        $this->addSet(new GhostSet());
        $this->addSet(new ObsidianSet());
    }

    /**
     * @param Set $set
     */
    private function addSet(Set $set): void
    {
        $this->sets[strtolower($set->getName())] = $set;
    }

    /**
     * @param string $name
     * @return Set|null
     */
    public function getSet(string $name): ?Set
    {
        return $this->sets[strtolower($name)] ?? null;
    }

    /**
     * @return Set[]
     */
    public function getSets(): array
    {
        return $this->sets;
    }
}