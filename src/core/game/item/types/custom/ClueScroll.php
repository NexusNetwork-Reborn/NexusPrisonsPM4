<?php

declare(strict_types = 1);

namespace core\game\item\types\custom;

use core\game\item\types\Interactive;
use core\game\item\types\Rarity;
use core\Nexus;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class ClueScroll extends Interactive {

    const RARITY = "Rarity";

    const QUESTS = "Quests";

    /** @var string */
    private $rarity;

    /** @var int[] */
    private $challenges;

    /**
     * ClueScroll constructor.
     *
     * @param string $rarity
     * @param array $challenges
     * @param string|null $uuid
     */
    public function __construct(string $rarity, array $challenges, ?string $uuid = null) {
        $scrollManager = Nexus::getInstance()->getGameManager()->getItemManager()->getScrollManager();
        $color = Rarity::RARITY_TO_COLOR_MAP[$rarity];
        $customName = TextFormat::RESET . TextFormat::BOLD . $color . "$rarity Clue Scroll";
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::BOLD . $color . "Clue Scroll Challenges";
        $count = 0;
        foreach($challenges as $index => $challenge) {
            ++$count;
            $scrollChallenge = $scrollManager->getChallenge($challenge);
            $format = TextFormat::RESET . TextFormat::WHITE;
            if($index < (count($challenges) - 1)) {
                $format .= TextFormat::ITALIC . TextFormat::GRAY;
            }
            $lore[] = TextFormat::RESET . TextFormat::BOLD . $color . "$count. " . $format . $scrollChallenge->getDescription();
        }
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Complete this Clue Scroll to discover";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "a casket filled with XP, Money, and Items!";
        $this->rarity = $rarity;
        $this->challenges = $challenges;
        parent::__construct(ItemFactory::getInstance()->get(ItemIds::MAP), $customName, $lore, true, true, $uuid);
    }

    /**
     * @return array
     */
    public static function getRequiredTags(): array {
        return [
            self::RARITY => StringTag::class,
            self::QUESTS => CompoundTag::class
        ];
    }

    /**
     * @param Item $item
     *
     * @return $this
     */
    public static function fromItem(Item $item): self {
        $tag = self::getCustomTag($item);
        $rarity = $tag->getString(self::RARITY);
        $challenge = $tag->getCompoundTag(self::QUESTS);
        $uuid = $tag->getString(self::UUID);
        $challenges = [];
        foreach($challenge->getValue() as $index => $nbt) {
            if($nbt instanceof IntTag) {
                $challenges[(int)$index] = $nbt->getValue();
            }
        }
        return new self($rarity, $challenges, $uuid);
    }

    /**
     * @return string
     */
    public function getName(): string {
        $color = Rarity::RARITY_TO_COLOR_MAP[$this->rarity];
        return TextFormat::RESET . TextFormat::BOLD . $color . "$this->rarity Clue Scroll";
    }

    /**
     * @return string[]
     */
    public function getLore(): array {
        $color = Rarity::RARITY_TO_COLOR_MAP[$this->rarity];
        $lore = [];
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::BOLD . $color . "Clue Scroll Challenges";
        $scrollManager = Nexus::getInstance()->getGameManager()->getItemManager()->getScrollManager();
        $count = 0;
        foreach($this->challenges as $index => $challenge) {
            ++$count;
            $scrollChallenge = $scrollManager->getChallenge($challenge);
            $format = TextFormat::RESET . TextFormat::WHITE;
            if($index < (count($this->challenges) - 1)) {
                $format .= TextFormat::ITALIC . TextFormat::GRAY;
            }
            $lore[] = TextFormat::RESET . TextFormat::BOLD . $color . "$count. " . $format . $scrollChallenge->getDescription();
        }
        $lore[] = "";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "Complete this Clue Scroll to discover";
        $lore[] = TextFormat::RESET . TextFormat::GRAY . "a casket filled with XP, Money, and Items!";
        return $lore;
    }

    /**
     * @return CompoundTag
     */
    public function dataToCompoundTag(): CompoundTag {
        $tag = new CompoundTag();
        $tag->setString(self::RARITY, $this->rarity);
        $challenges = new CompoundTag();
        foreach($this->challenges as $index => $challenge) {
            $challenges->setInt((string)$index, $challenge);
        }
        $tag->setTag(self::QUESTS, $challenges);
        $tag->setString(self::UUID, $this->getUniqueId());
        return $tag;
    }

    /**
     * @return int
     */
    public function getCurrentChallenge(): int {
        return end($this->challenges);
    }

    /**
     * @return int[]
     */
    public function getChallenges(): array {
        return $this->challenges;
    }

    public function completeChallenge(): void {
        $this->challenges[] = Nexus::getInstance()->getGameManager()->getItemManager()->getScrollManager()->getRandomChallengeByRarity($this->rarity)->getId();
    }

    /**
     * @return string
     */
    public function getRarity(): string {
        return $this->rarity;
    }
}