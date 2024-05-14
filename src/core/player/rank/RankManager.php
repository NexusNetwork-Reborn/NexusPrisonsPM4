<?php
declare(strict_types=1);

namespace core\player\rank;

use core\Nexus;
use pocketmine\utils\TextFormat;

class RankManager {

    /** @var Nexus */
    private $core;

    /** @var Rank[] */
    private $ranks = [];

    const MAX_VAULTS = 20;

    const MAX_HOMES = 20;

    const MAX_SHOWCASES = 54;

    /**
     * RankManager constructor.
     *
     * @param Nexus $core
     *
     * @throws RankException
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $core->getServer()->getPluginManager()->registerEvents(new RankListener($core), $core);
        $this->init();
    }

    /**
     * @throws RankException
     */
    public function init(): void { // TODO: Take away some perms (effect smh)
        $this->addRank(new Rank("Player", 0.1, TextFormat::GRAY, TextFormat::GRAY . "Player", Rank::PLAYER,
                                "{level}" . TextFormat::RESET . TextFormat::WHITE . "{gangRole}{gang}" . TextFormat::GRAY . "<" . TextFormat::WHITE . "Trainee" . TextFormat::GRAY . ">" . TextFormat::RESET . " {tag}" . TextFormat::GRAY . "{player}: {message}",
                                "{level}" . TextFormat::RESET . TextFormat::WHITE . "{gangRole}{gang} " . TextFormat::RESET . TextFormat::WHITE . "{player}", 1, 1, 1, 0, 0, [
                                    "permission.starter",
                                    "permission.once"
                                ]));
        $this->addRank(new Rank("Plutonian", 0.1, TextFormat::WHITE, TextFormat::WHITE . "<" . TextFormat::DARK_GRAY . "Plutonian" . TextFormat::WHITE . ">", Rank::NOBLE,
                                "{level}" . TextFormat::RESET . TextFormat::WHITE . "{gangRole}{gang}" . TextFormat::WHITE . "<" . TextFormat::DARK_GRAY . "Plutonian" . TextFormat::WHITE . ">" . " {tag}" . TextFormat::DARK_GRAY . "{player}" . TextFormat::WHITE . ": {message}",
                                "{level}" . TextFormat::RESET . "<" . TextFormat::DARK_GRAY . "Plutonian" . TextFormat::WHITE . ">\n" . TextFormat::WHITE . "{gangRole}{gang}" . TextFormat::WHITE . "{player}", 2, 1, 2, 1, 0, [
                                    "permission.plutonian",
                                    "permission.tier1",
                                    "permission.once"
                                ]));
        $this->addRank(new Rank("Divergent", 0.1, TextFormat::WHITE, TextFormat::WHITE . "<" . TextFormat::LIGHT_PURPLE . "Divergent" . TextFormat::WHITE . ">", Rank::IMPERIAL,
                                "{level}" . TextFormat::RESET . TextFormat::WHITE . "{gangRole}{gang}" . TextFormat::WHITE . "<" . TextFormat::LIGHT_PURPLE . "Divergent" . TextFormat::WHITE . ">" . " {tag}" . TextFormat::LIGHT_PURPLE . "{player}" . TextFormat::WHITE . ": {message}",
                                "{level}" . TextFormat::RESET . "<" . TextFormat::LIGHT_PURPLE . "Divergent" . TextFormat::WHITE . ">\n" . TextFormat::WHITE . "{gangRole}{gang}" . TextFormat::WHITE . "{player}", 3, 1, 3, 2, 0, [
                                    "permission.divergent",
                                    "permission.tier1",
                                    "permission.tier2",
                                    "permission.once",
                                ]));
        $this->addRank(new Rank("Ulterior", 0.05, TextFormat::WHITE, TextFormat::BOLD . TextFormat::WHITE . "<" . TextFormat::RESET . TextFormat::DARK_AQUA . "Ulterior" . TextFormat::BOLD . TextFormat::WHITE . ">", Rank::SUPREME,
                                "{level}" . TextFormat::RESET . TextFormat::WHITE . "{gangRole}{gang}" . TextFormat::WHITE . TextFormat::BOLD . "<" . TextFormat::RESET . TextFormat::DARK_AQUA . "Ulterior" . TextFormat::BOLD . TextFormat::WHITE . ">" . TextFormat::RESET . " {tag}" . TextFormat::DARK_AQUA . "{player}" . TextFormat::WHITE . ": {message}",
                                "{level}" . TextFormat::RESET . TextFormat::BOLD . "<" . TextFormat::RESET . TextFormat::DARK_AQUA . "Ulterior" . TextFormat::BOLD . TextFormat::WHITE . ">\n" . TextFormat::RESET . TextFormat::WHITE . "{gangRole}{gang}" . TextFormat::WHITE . "{player}", 4, 2, 4, 3, 0, [
                                    "permission.ulterior",
                                    "permission.tier1",
                                    "permission.tier2",
                                    "permission.tier3",
                                    "permission.once"
                                ]));
        $this->addRank(new Rank("Alien", 0.05, TextFormat::WHITE,TextFormat::BOLD . TextFormat::WHITE . "<" . TextFormat::RESET . TextFormat::BLUE . "Alien" . TextFormat::BOLD . TextFormat::WHITE . ">", Rank::MAJESTY,
                                "{level}" . TextFormat::RESET . TextFormat::WHITE . "{gangRole}{gang}" . TextFormat::WHITE . TextFormat::BOLD . "<" . TextFormat::RESET . TextFormat::BLUE . "Alien" . TextFormat::BOLD . TextFormat::WHITE . ">" . TextFormat::RESET  . " {tag}" . TextFormat::BLUE . "{player}" . TextFormat::WHITE . ": {message}",
                                "{level}" . TextFormat::RESET . TextFormat::BOLD . "<" . TextFormat::RESET . TextFormat::BLUE . "Alien" . TextFormat::BOLD . TextFormat::WHITE . ">\n" . TextFormat::RESET . TextFormat::WHITE . "{gangRole}{gang}" . TextFormat::WHITE . "{player}", 7, 3, 5, 6, 0, [
                                    "permission.alien",
                                    "permission.tier1",
                                    "permission.tier2",
                                    "permission.tier3",
                                    "permission.tier4",
                                    "permission.once",
                                    "permission.join.full"
                                ]));
        $this->addRank(new Rank("Martian", 0.05, TextFormat::WHITE, TextFormat::BOLD . TextFormat::WHITE  . "<" . TextFormat::AQUA . "Martian" . TextFormat::WHITE . ">", Rank::EMPEROR,
                                "{level}" . TextFormat::RESET . TextFormat::WHITE . "{gangRole}{gang}" . TextFormat::WHITE . TextFormat::BOLD . "<" . TextFormat::AQUA . "Martian" . TextFormat::WHITE . ">" . TextFormat::RESET . " {tag}" . TextFormat::AQUA . "{player}" . TextFormat::WHITE . ": {message}",
                                "{level}" . TextFormat::RESET . TextFormat::BOLD . "<" . TextFormat::AQUA . "Martian" . TextFormat::WHITE . ">\n" . TextFormat::RESET . TextFormat::WHITE . "{gangRole}{gang}" . TextFormat::WHITE . "{player}", 10, 4, 6, 8, 0, [
                                    "permission.martian",
                                    "permission.tier1",
                                    "permission.tier2",
                                    "permission.tier3",
                                    "permission.tier4",
                                    "permission.tier5",
                                    "permission.once",
                                    "permission.join.full"
                                ]));
        $this->addRank(new Rank("Martian+", 0, TextFormat::WHITE, TextFormat::BOLD . TextFormat::WHITE  . "<" . TextFormat::YELLOW . "Martian" . TextFormat::GOLD . "+" . TextFormat::WHITE . ">", Rank::EMPEROR_HEROIC,
                                "{level}" . TextFormat::RESET . TextFormat::WHITE . "{gangRole}{gang}" . TextFormat::WHITE . TextFormat::BOLD . "<" . TextFormat::YELLOW . "Martian" . TextFormat::GOLD . "+" . TextFormat::WHITE . ">" . TextFormat::RESET . " {tag}" . TextFormat::YELLOW . "{player}" . TextFormat::WHITE . ": {message}",
                                "{level}" . TextFormat::RESET . TextFormat::BOLD . "<" . TextFormat::YELLOW . "Martian" . TextFormat::GOLD . "+" . TextFormat::WHITE . ">\n" . TextFormat::RESET . TextFormat::WHITE . "{gangRole}{gang}" . TextFormat::WHITE . "{player}", 10, 5, 8, 10, 0.1, [
                                    "permission.martian+",
                                    "permission.tier1",
                                    "permission.tier2",
                                    "permission.tier3",
                                    "permission.tier4",
                                    "permission.tier5",
                                    "permission.tier6",
                                    "permission.once",
                                    "permission.join.full"
                                ]));
        $this->addRank(new Rank("President", 0, TextFormat::WHITE, TextFormat::BOLD . TextFormat::DARK_RED  . "<" . TextFormat::RED . "President" . TextFormat::DARK_RED . ">", Rank::PRESIDENT,
                                "{level}" . TextFormat::RESET . TextFormat::WHITE . "{gangRole}{gang}" . TextFormat::BOLD . TextFormat::DARK_RED . "<" . TextFormat::RED . "President" . TextFormat::DARK_RED . ">" . TextFormat::RESET . " {tag}" . TextFormat::RED . "{player}" . TextFormat::WHITE . ": " . TextFormat::RED . "{message}",
                                "{level}" . TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_RED . "<" . TextFormat::RED . "President" . TextFormat::DARK_RED . ">\n" . TextFormat::WHITE . "{gangRole}{gang}" . TextFormat::RESET . TextFormat::WHITE . "{player}", 10, 6, 12, 10, 0.1, [
                                    "permission.president",
                                    "permission.tier1",
                                    "permission.tier2",
                                    "permission.tier3",
                                    "permission.tier4",
                                    "permission.tier5",
                                    "permission.tier6",
                                    "permission.tier7",
                                    "permission.once",
                                    "permission.join.full"
                                ]));
        $this->addRank(new Rank("Trainee", 0, TextFormat::LIGHT_PURPLE, TextFormat::WHITE  . TextFormat::BOLD . "<" . TextFormat::RESET . TextFormat::LIGHT_PURPLE . "Trainee" . TextFormat::BOLD . TextFormat::WHITE . ">", Rank::TRAINEE,
                                "{level}" . TextFormat::RESET . TextFormat::WHITE . "{gangRole}{gang}" . TextFormat::WHITE . TextFormat::BOLD . "<" . TextFormat::RESET . TextFormat::LIGHT_PURPLE . "Trainee" . TextFormat::BOLD . TextFormat::WHITE . ">" . TextFormat::RESET . " {tag}" . TextFormat::LIGHT_PURPLE . "{player}" . TextFormat::WHITE . ": " . TextFormat::LIGHT_PURPLE . "{message}",
                                "{level}" . TextFormat::RESET . TextFormat::BOLD . "<" . TextFormat::RESET . TextFormat::LIGHT_PURPLE . "Trainee" . TextFormat::BOLD . TextFormat::WHITE . ">\n" . TextFormat::RESET . TextFormat::WHITE . "{gangRole}{gang}" . TextFormat::WHITE . "{player}", 20, 10, 8, 6, 0.05, [
                                    "permission.starter",
                                    "permission.tier1",
                                    "permission.tier2",
                                    "permission.tier3",
                                    "permission.staff",
                                    "permission.join.full",
                                    "permission.once",
                                ]));
        $this->addRank(new Rank("Mod", 0, TextFormat::DARK_PURPLE, TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "<" . TextFormat::DARK_PURPLE . "Mod" . TextFormat::LIGHT_PURPLE . ">", Rank::MODERATOR,
                                "{level}" . TextFormat::RESET . TextFormat::WHITE . "{gangRole}{gang}" . TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "<" . TextFormat::DARK_PURPLE . "Moderator" . TextFormat::LIGHT_PURPLE . ">" . TextFormat::RESET . " {tag}" . TextFormat::DARK_PURPLE . "{player}" . TextFormat::WHITE . ": " . TextFormat::DARK_PURPLE . "{message}",
                                "{level}" . TextFormat::RESET . TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "<" . TextFormat::DARK_PURPLE . "Moderator" . TextFormat::LIGHT_PURPLE . ">\n" . TextFormat::RESET . TextFormat::WHITE . "{gangRole}{gang}" . TextFormat::WHITE . "{player}", 20, 15, 9, 8, 0.1, [
                                    "permission.starter",
                                    //"permission.plutonian",
                                    //"permission.divergent",
                                    //"permission.ulterior",
                                    //"permission.alien",
                                    //"permission.martian",
                                    //"permission.martian+",
                                    "permission.tier1",
                                    "permission.tier2",
                                    "permission.tier3",
                                    "permission.tier4",
                                    "permission.tier5",
                                    "permission.tier6",
                                    "permission.tier7",
                                    "permission.mod",
                                    "permission.join.full",
                                    "permission.staff",
                                    "permission.once",
                                    "pocketmine.command.teleport",
                                    "invsee.inventory.view"
                                ]));
        $this->addRank(new Rank("Admin", 0, TextFormat::DARK_AQUA,  TextFormat::AQUA . TextFormat::BOLD . "<" . TextFormat::DARK_AQUA . "Admin" . TextFormat::AQUA . ">", Rank::ADMIN,
                                "{level}" . TextFormat::RESET . TextFormat::WHITE . "{gangRole}{gang}" . TextFormat::AQUA . TextFormat::BOLD . "<" . TextFormat::DARK_AQUA . "Admin" . TextFormat::AQUA . ">" . TextFormat::RESET . " {tag}" . TextFormat::DARK_AQUA . "{player}" . TextFormat::WHITE . ": " . TextFormat::DARK_AQUA . "{message}",
                                "{level}" . TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . "<" . TextFormat::DARK_AQUA . "Admin" . TextFormat::AQUA . ">\n" . TextFormat::RESET . TextFormat::WHITE . "{gangRole}{gang}" . TextFormat::WHITE . "{player}", 20, 20, 11, 10, 0.1, [
                                    "permission.starter",
                                    "permission.plutonian",
                                    "permission.divergent",
                                    "permission.ulterior",
                                    "permission.alien",
                                    "permission.martian",
                                    "permission.martian+",
                                    "permission.tier1",
                                    "permission.tier2",
                                    "permission.tier3",
                                    "permission.tier4",
                                    "permission.tier5",
                                    "permission.tier6",
                                    "permission.tier7",
                                    "permission.mod",
                                    "permission.join.full",
                                    "permission.staff",
                                    "permission.admin",
                                    "pocketmine.command.teleport",
                                    "pocketmine.command.ban-ip",
                                    "pocketmine.command.pardon-ip",
                                    "pocketmine.command.gamemode",
                                    "permission.lootbox",
                                    "permission.once",
                                    "invsee.inventory.view"
                                ]));
        $this->addRank(new Rank("Manager", 0, TextFormat::RED,  TextFormat::DARK_RED . TextFormat::BOLD . "<" . TextFormat::RED . "Manager" . TextFormat::DARK_RED . ">", Rank::MANAGER,
                                "{level}" . TextFormat::RESET . TextFormat::WHITE . "{gangRole}{gang}" . TextFormat::DARK_RED . TextFormat::BOLD . "<" . TextFormat::RED . "Manager" . TextFormat::DARK_RED . ">" . TextFormat::RESET . " {tag}" . TextFormat::RED . "{player}" . TextFormat::WHITE . ": " . TextFormat::RED . "{message}",
                                "{level}" . TextFormat::RESET . TextFormat::DARK_RED . TextFormat::BOLD . "<" . TextFormat::RED . "Manager" . TextFormat::DARK_RED . ">\n" . TextFormat::RESET . TextFormat::WHITE . "{gangRole}{gang}" . TextFormat::WHITE . "{player}", 20, 20, 13, 10, 0.1, [
                                    "permission.mod",
                                    "permission.join.full",
                                    "permission.staff",
                                    "permission.admin",
                                    "permission.tier1",
                                    "permission.tier2",
                                    "permission.tier3",
                                    "permission.tier4",
                                    "permission.tier5",
                                    "permission.tier6",
                                    "permission.tier7",
                                    "permission.lootbox"
                                ]));
        $this->addRank(new Rank("Executive", 0, TextFormat::DARK_RED,  TextFormat::RED . TextFormat::BOLD . "<" . TextFormat::DARK_RED . "Executive" . TextFormat::RED . ">", Rank::EXECUTIVE,
                                "{level}" . TextFormat::RESET . TextFormat::WHITE . "{gangRole}{gang}" . TextFormat::RED . TextFormat::BOLD . "<" . TextFormat::DARK_RED . "Executive" . TextFormat::RED . ">" . TextFormat::RESET . " {tag}" . TextFormat::DARK_RED . "{player}" . TextFormat::WHITE . ": " . TextFormat::DARK_RED . "{message}",
                                "{level}" . TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "<" . TextFormat::DARK_RED . "Executive" . TextFormat::RED . ">\n" . TextFormat::RESET . TextFormat::WHITE . "{gangRole}{gang}" . TextFormat::WHITE . "{player}", 20, 20, 100, 10, 0.1, [
                                    "permission.mod",
                                    "permission.join.full",
                                    "permission.staff",
                                    "permission.admin",
                                    "permission.tier1",
                                    "permission.tier2",
                                    "permission.tier3",
                                    "permission.tier4",
                                    "permission.tier5",
                                    "permission.tier6",
                                    "permission.tier7",
                                    "permission.lootbox",
                                    "buildertools.command"
                                ]));
        $this->addRank(new Rank("YouTuber", 0, TextFormat::WHITE,  TextFormat::WHITE . TextFormat::BOLD . "<" . TextFormat::RED . "YouTuber" . TextFormat::WHITE . ">", Rank::YOUTUBER,
                                "{level}" . TextFormat::RESET . TextFormat::WHITE . "{gangRole}{gang}" . TextFormat::WHITE . TextFormat::BOLD . "<" . TextFormat::RED . "YouTuber" . TextFormat::WHITE . ">" . TextFormat::RESET . " {tag}" . TextFormat::RED . "{player}" . TextFormat::WHITE . ": {message}",
                                "{level}" . TextFormat::RESET . TextFormat::BOLD . "<" . TextFormat::RED . "YouTuber" . TextFormat::WHITE . ">\n" . TextFormat::RESET . TextFormat::WHITE . "{gangRole}{gang}" . TextFormat::WHITE . "{player}", 10, 7, 6, 6, 0.1, [
                                    "permission.starter",
                                    "permission.plutonian",
                                    "permission.divergent",
                                    "permission.ulterior",
                                    "permission.alien",
                                    "permission.martian",
                                    "permission.martian+",
                                    "permission.tier1",
                                    "permission.tier2",
                                    "permission.tier3",
                                    "permission.tier4",
                                    "permission.tier5",
                                    "permission.tier6",
                                    "permission.join.full",
                                    "permission.once"
                                ]));
        $this->addRank(new Rank("Developer", 0, TextFormat::LIGHT_PURPLE,  TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "<" . TextFormat::DARK_PURPLE . "Developer" . TextFormat::LIGHT_PURPLE . ">", Rank::DEVELOPER,
            "{level}" . TextFormat::RESET . TextFormat::WHITE . "{gangRole}{gang}" . TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "<" . TextFormat::DARK_PURPLE . "Developer" . TextFormat::LIGHT_PURPLE . ">" . TextFormat::RESET . " {tag}" . TextFormat::RED . "{player}" . TextFormat::WHITE . ": " . TextFormat::RED . "{message}",
            "{level}" . TextFormat::RESET . TextFormat::LIGHT_PURPLE . TextFormat::BOLD . "<" . TextFormat::DARK_PURPLE . "Developer" . TextFormat::LIGHT_PURPLE . ">\n" . TextFormat::RESET . TextFormat::WHITE . "{gangRole}{gang}" . TextFormat::WHITE . "{player}", 20, 20, 13, 10, 0.1, [
                "permission.mod",
                "permission.join.full",
                "permission.staff",
                "permission.admin",
                "permission.tier1",
                "permission.tier2",
                "permission.tier3",
                "permission.tier4",
                "permission.tier5",
                "permission.tier6",
                "permission.tier7",
                "permission.lootbox"
            ]));
    }

    /**
     * @param Rank $rank
     *
     * @throws RankException
     */
    public function addRank(Rank $rank): void {
        if(isset($this->ranks[$rank->getIdentifier()]) or isset($this->ranks[$rank->getName()])) {
            throw new RankException("Attempted to override a rank with the identifier of \"{$rank->getIdentifier()}\" and a name of \"{$rank->getName()}\".");
        }
        $this->ranks[$rank->getIdentifier()] = $rank;
        $this->ranks[$rank->getName()] = $rank;
    }

    /**
     * @param int $identifier
     *
     * @return Rank|null
     */
    public function getRankByIdentifier(int $identifier): ?Rank {
        return $this->ranks[$identifier] ?? null;
    }

    /**
     * @return Rank[]
     */
    public function getRanks(): array {
        return array_unique($this->ranks);
    }

    /**
     * @param string $name
     *
     * @return Rank
     */
    public function getRankByName(string $name): ?Rank {
        return $this->ranks[$name] ?? null;
    }
}