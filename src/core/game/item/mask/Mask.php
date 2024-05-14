<?php

namespace core\game\item\mask;

use core\Nexus;
use core\player\NexusPlayer;
use libs\utils\Utils;
use pocketmine\entity\InvalidSkinException;
use pocketmine\entity\Skin;

abstract class Mask {

    const ALL_MASKS = [self::GUARD, self::PILGRIM, self::PRISONER, self::BUFF, self::CACTUS, self::CUPID, self::TINKERER, self::SHADOW, self::JAILOR, self::FOCUS, self::FIREFLY];

    const GUARD = "Guard";
    const PILGRIM = "Pilgrim";
    const PRISONER = "Prisoner";

    const BUFF = "Buff";
    const CACTUS = "Cactus";
    const CUPID = "Cupid";
    const TINKERER = "Tinkerer";
    const SHADOW = "Shadow";
    const JAILOR = "Jailor";
    const FOCUS = "Focus";
    const FIREFLY = "Firefly";

    /** @var string */
    private $name;

    /** @var string */
    private $description;

    /** @var string[] */
    private $abilities;

    /**
     * Mask constructor.
     *
     * @param string $name
     * @param string $description
     * @param array $abilities
     */
    public function __construct(string $name, string $description, array $abilities) {
        $this->name = $name;
        $this->description = $description;
        $this->abilities = $abilities;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return string
     */
    abstract public function getColoredName(): string;

    /**
     * @return string
     */
    public function getDescription(): string {
        return $this->description;
    }

    /**
     * @return string[]
     */
    public function getAbilities(): array {
        return $this->abilities;
    }

    /**
     * @param NexusPlayer $player
     * @param string $maskName
     *
     * @return Skin
     */
    public static function getSkinWithMask(NexusPlayer $player, string $maskName): Skin {
        $path = Nexus::getInstance()->getDataFolder() . DIRECTORY_SEPARATOR . "geometry" . DIRECTORY_SEPARATOR;
        $geometryData = file_get_contents($path . "masked.json");
        $geometryId = "geometry.masked.default";

        $skin = $player->getSkin();

        $length = strlen($skin->getSkinData());
        $size = 128;
        if ($length === (64 * 32 * 4) or $length === (64 * 64 * 4)) {
            $size = 64;
        }

        $skinData = Utils::getSkinDataFromPNG($path . "skins" . DIRECTORY_SEPARATOR . $size . DIRECTORY_SEPARATOR . "$maskName.png");
        $skinId = $size . "_" . $maskName;

        $headSize = ($size == 64) ? (64 * 16 * 4) : (128 * 32 * 4);

        $justHead = substr($skinData, 0, $headSize);
        $skinData = $justHead . substr($skin->getSkinData(), $headSize);

        try {
            $ret = new Skin($skinId, $skinData, $skin->getCapeData(), $geometryId, $geometryData);
        } catch (InvalidSkinException $exception) {
            return $skin;
        }

        return $ret;
    }
}