<?php
declare(strict_types=1);

namespace core\player\rank;

use core\player\NexusPlayer;
use pocketmine\utils\TextFormat;

class Rank {

    const PLAYER = 0;

    const NOBLE = 1;

    const IMPERIAL = 2;

    const SUPREME = 3;

    const MAJESTY = 4;

    const EMPEROR = 5;

    const EMPEROR_HEROIC = 6;

    const PRESIDENT = 7;

    const TRAINEE = 8;

    const MODERATOR = 9;

    const ADMIN = 10;

    const MANAGER = 11;

    const EXECUTIVE = 12;

    const YOUTUBER = 13;

    const FAMOUS = 14;

    const DEVELOPER = 15;

    /** @var string */
    private $name;

    /** @var string */
    private $coloredName;

    /** @var int */
    private $identifier;

    /** @var string */
    private $chatFormat;

    /** @var string */
    private $tagFormat;

    /** @var array */
    private $permissions;

    /** @var int */
    private $homes;

    /** @var int */
    private $vaults;

    /** @var int */
    private $auctionLimit;

    /** @var string */
    private $chatColor;

    /** @var float */
    private $feeDeduction;

    /** @var int */
    private $extraQuests;

    /** @var float */
    private $booster;

    /**
     * Rank constructor.
     *
     * @param string $name
     * @param float $feeDeduction
     * @param string $chatColor
     * @param string $coloredName
     * @param int $identifier
     * @param string $chatFormat
     * @param string $tagFormat
     * @param int $homes
     * @param int $vaults
     * @param int $auctionLimit
     * @param int $extraQuests
     * @param float $booster
     * @param array $permissions
     */
    public function __construct(string $name, float $feeDeduction, string $chatColor, string $coloredName, int $identifier, string $chatFormat, string $tagFormat, int $homes, int $vaults, int $auctionLimit, int $extraQuests, float $booster, array $permissions = []) {
        $this->name = $name;
        $this->feeDeduction = $feeDeduction;
        $this->chatColor = $chatColor;
        $this->coloredName = $coloredName;
        $this->identifier = $identifier;
        $this->chatFormat = $chatFormat;
        $this->tagFormat = $tagFormat;
        $this->homes = $homes;
        $this->vaults = $vaults;
        $this->auctionLimit = $auctionLimit;
        $this->extraQuests = $extraQuests;
        $this->booster = $booster;
        $this->permissions = $permissions;
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
    public function getColoredName(): string {
        return $this->coloredName;
    }

    /**
     * @return string
     */
    public function getChatColor(): string {
        return $this->chatColor;
    }

    /**
     * @return int
     */
    public function getIdentifier(): int {
        return $this->identifier;
    }

    /**
     * @param NexusPlayer $player
     * @param string $message
     * @param array $args
     *
     * @return string
     */
    public function getChatFormatFor(NexusPlayer $player, string $message, array $args = []): string {
        $format = $this->chatFormat;
        foreach($args as $arg => $value) {
            $format = str_replace("{" . $arg . "}", $value, $format);
        }
        $tag = "";
        if(!empty($player->getDataSession()->getCurrentTag())) {
            $tag = TextFormat::RESET . TextFormat::DARK_GRAY . "[" . TextFormat::WHITE . $player->getDataSession()->getCurrentTag() . TextFormat::RESET . TextFormat::DARK_GRAY . "]" . " ";
        }
        $format = str_replace("{tag}", $tag, $format);
        $format = str_replace("{player}", $player->getDisplayName(), $format);
        return str_replace("{message}", $message, $format);
    }

    /**
     * @param NexusPlayer $player
     * @param array $args
     *
     * @return string
     */
    public function getTagFormatFor(NexusPlayer $player, array $args = []): string {
        $format = $this->tagFormat;
        foreach($args as $arg => $value) {
            $format = str_replace("{" . $arg . "}", $value, $format);
        }
        $tag = "";
        if(!empty($player->getDataSession()->getCurrentTag())) {
            $tag = TextFormat::RESET . TextFormat::DARK_GRAY . "[" . TextFormat::WHITE . $player->getDataSession()->getCurrentTag() . TextFormat::RESET . TextFormat::DARK_GRAY . "]" . " ";
        }
        $format = str_replace("{tag}", $tag, $format);
        $format = str_replace("{player}", $player->getDisplayName(), $format);
        return $format;
    }

    /**
     * @return string[]
     */
    public function getPermissions(): array {
        return $this->permissions;
    }

    /**
     * @param NexusPlayer $player
     *
     * @return int
     */
    public function getHomeLimit(NexusPlayer $player): int {
        return min($this->homes + $player->getDataSession()->getAdditionalHomes(), RankManager::MAX_HOMES);
    }

    /**
     * @param NexusPlayer $player
     *
     * @return int
     */
    public function getVaultsLimit(NexusPlayer $player): int {
        return min($this->vaults + $player->getDataSession()->getAdditionalVaults(), RankManager::MAX_VAULTS);
    }

    /**
     * @return int
     */
    public function getAuctionLimit(): int {
        return $this->auctionLimit;
    }

    /**
     * @return float
     */
    public function getFeeDeduction(): float {
        return $this->feeDeduction;
    }

    /**
     * @return int
     */
    public function getExtraQuests(): int {
        return $this->extraQuests;
    }

    /**
     * @return float
     */
    public function getBooster(): float {
        return $this->booster;
    }

    /**
     * @return string
     */
    public function __toString() {
        return $this->name;
    }
}