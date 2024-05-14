<?php

namespace core\game\item\types\customies;

use customiesdevs\customies\item\CreativeInventoryInfo;
use customiesdevs\customies\item\ItemComponents;
use customiesdevs\customies\item\ItemComponentsTrait;
use pocketmine\item\Item;
use pocketmine\item\ItemIdentifier;

class ItemSkinScroll extends Item implements ItemComponents {
    use ItemComponentsTrait;

    public const SIMPLE = ["amethyst_pickaxe", "quartz_pickaxe", "rodinium_pickaxe", "ruby_pickaxe", "wolfram_pickaxe", "bronze_pickaxe", "cobalt_pickaxe", "ember_pickaxe", "lapis_pickaxe"];

    public const UNCOMMON = ["bismuth_kopis", "royal_sword", "ruby_greatsword", "steel_sabre", "iolite_claymore", "iridium_spatha", "jade_ikalaka", "kalite_cinqueda", "lapis_spatha", "lunite_falchion", "neodymium_falchion", "nether_falcata", "obsidian_rapier", "pinksteel_katana", "adra_kopis", "bonesteel_katana", "ruby_sword"];

    public const ELITE = ["malachite_pickaxe", "neodymium_pickaxe", "obsidian_pickaxe", "osmium_pickaxe", "azure_blade", "blacksteel_magmasword", "blacksteel_ribsword", "butterfly_blade", "iron_piercer", "jade_forester", "iolite_sword", "ptau_katana", "quartz_ikalaka", "aquamarine_sword", "azure_gladius", "bronze_gladius", "rosegold_rapier", "ruthenium_ikalaka", "sanguinite_falcata", "shadowflame_spatha", "titanium_kopis", "topaz_gladius", "tourmaline_cinqueda", "vanadium_katana", "wolfram_cinqueda"];

    public const ULTIMATE = ["ptau_pickaxe", "relic_pickaxe", "ruthenium_pickaxe", "sanguinite_pickaxe", "azure_pickaxe", "bat_pickaxe", "bismuth_pickaxe", "blacksteel_magmapickaxe", "blue_steel_pickaxe", "damascus_steel_pickaxe", "dark_pickaxe", "durganite_pickaxe", "ender_pickaxe", "icefire_pickaxe", "iridium_pickaxe", "jade_pickaxe", "lead_pickaxe", "livewood_pickaxe", "lunite_pickaxe", "rosegold_pickaxe"];

    public const LEGENDARY = ["titanium_pickaxe", "topaz_pickaxe", "emerald_pickaxe", "tourmaline_pickaxe", "vanadium_pickaxe", "adra_pickaxe", "amethyst_pickaxe", "aquamarine_pickaxe", "bone_pickaxe", "bonesteel_pickaxe", "holy_pickaxe", "iolite_pickaxe", "permafrost_pickaxe", "pinksteel_pickaxe", "platinum_pickaxe", "quartz_macesword", "grand_blade", "lead_falcata", "livewood_rapier", "lyntane_falchion", "malachite_cinque", "osmium_rapier", "permafrost_cinqueda", "platinum_gladius", "relic_ikalaka", "amethyst_sword", "blue_steel_sword", "bone_kopis", "cobalt_falcata", "damascus_spatha", "dark_spatha", "durganite_falcata", "ember_falcata", "emerald_kopis", "ender_katana", "ethereal_rapier", "holy_falchion", "icefire_falchion", "rodinium_ikalaka"];

    public const GODLY = ["shadowflame_pickaxe", "unobtanium_pickaxe", "lyntane_pickaxe", "nebonite_pickaxe", "root_blade", "scissorsword", "unobtanium_broadhead", "nebonite_gladius"];


    public function __construct(ItemIdentifier $identifier, string $name = "Item Skin Scroll")
    {
        parent::__construct($identifier, $name);

        $this->initComponent("skin_scroll", new CreativeInventoryInfo(CreativeInventoryInfo::CATEGORY_ITEMS));
    }
}