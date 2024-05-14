<?php

declare(strict_types = 1);

namespace core\game\kit;

use core\game\kit\types\gkit\Ares;
use core\game\kit\types\gkit\Blacksmith;
use core\game\kit\types\gkit\Cyborg;
use core\game\kit\types\gkit\Executioner;
use core\game\kit\types\gkit\GrimReaper;
use core\game\kit\types\gkit\Hero;
use core\game\kit\types\gkit\HeroicAtheos;
use core\game\kit\types\gkit\HeroicBroteas;
use core\game\kit\types\gkit\HeroicColossus;
use core\game\kit\types\gkit\HeroicEnchanter;
use core\game\kit\types\gkit\HeroicIapetus;
use core\game\kit\types\gkit\HeroicSlaughter;
use core\game\kit\types\gkit\HeroicVulkarion;
use core\game\kit\types\gkit\HeroicWarlock;
use core\game\kit\types\gkit\HeroicZenith;
use core\game\kit\types\Heroic;
use core\game\kit\types\Imperial;
use core\game\kit\types\Emperor;
use core\game\kit\types\Lootbox;
use core\game\kit\types\Majesty;
use core\game\kit\types\President;
use core\game\kit\types\Supreme;
use core\game\kit\types\Player;
use core\game\kit\types\Noble;
use core\Nexus;

class KitManager {

    /** @var Nexus */
	private $core;

	/** @var Kit[] */
	private $kits = [];

	/** @var GodKit[] */
	private $godKits = [];

    /**
     * KitManager constructor.
     *
     * @param Nexus $core
     *
     * @throws KitException
     */
	public function __construct(Nexus $core) {
		$this->core = $core;
		$this->init();
	}

    /**
     * @throws KitException
     */
	public function init(): void {
        $this->addKit(new Noble());
        $this->addKit(new Imperial());
        $this->addKit(new Supreme());
        $this->addKit(new Majesty());
        $this->addKit(new Emperor());
        $this->addKit(new Heroic());
        $this->addKit(new President());
        $this->addKit(new Lootbox());
        $this->addKit(new Ares());
        $this->addKit(new Blacksmith());
        $this->addKit(new Cyborg());
        $this->addKit(new Executioner());
        $this->addKit(new GrimReaper());
        $this->addKit(new Hero());
        $this->addKit(new HeroicAtheos());
        $this->addKit(new HeroicBroteas());
        $this->addKit(new HeroicColossus());
        $this->addKit(new HeroicEnchanter());
        $this->addKit(new HeroicIapetus());
        $this->addKit(new HeroicSlaughter());
        $this->addKit(new HeroicVulkarion());
        $this->addKit(new HeroicWarlock());
        $this->addKit(new HeroicZenith());
	}

    /**
     * @param string $kit
     *
     * @return Kit|GodKit|null
     */
	public function getKitByName(string $kit) : ?Kit {
		return $this->kits[$kit] ?? $this->godKits[$kit] ?? null;
	}

    /**
     * @return Kit[]
     */
	public function getKits(): array {
	    return $this->kits;
    }

    /**
     * @return GodKit[]
     */
    public function getGodKits(): array {
        return $this->godKits;
    }

	/**
	 * @param Kit $kit
	 *
	 * @throws KitException
	 */
	public function addKit(Kit $kit) : void {
		if(isset($this->kits[$kit->getName()])) {
			throw new KitException("Attempted to override a kit with the name of \"{$kit->getName()}\" and a class of \"" . get_class($kit) . "\".");
		}
        if(isset($this->godKits[$kit->getName()])) {
            throw new KitException("Attempted to override a god kit with the name of \"{$kit->getName()}\" and a class of \"" . get_class($kit) . "\".");
        }
		if($kit instanceof GodKit) {
		    $this->godKits[$kit->getName()] = $kit;
        }
		else {
            $this->kits[$kit->getName()] = $kit;
        }
	}
}