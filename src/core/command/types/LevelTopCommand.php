<?php

declare(strict_types = 1);

namespace core\command\types;

use core\command\utils\args\IntegerArgument;
use core\command\utils\Command;
use core\game\item\enchantment\EnchantmentManager;
use core\player\rpg\XPUtils;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class LevelTopCommand extends Command {

    /**
     * BalanceTopCommand constructor.
     */
    public function __construct() {
        parent::__construct("leveltop", "Show the highest-leveled players.", "/leveltop [page = 1]", ["lvltop"]);
        $this->registerArgument(0, new IntegerArgument("page", true));
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * @throws TranslationException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if(isset($args[0])) {
            if(!is_numeric($args[0])) {
                $sender->sendMessage(Translation::getMessage("invalidAmount"));
                return;
            }
            $page = (int)$args[0];
        }
        else {
            $page = 1;
        }
        $place = (($page - 1) * 10);
        $stmt = $this->getCore()->getMySQLProvider()->getDatabase()->prepare("SELECT username, xp, prestige FROM stats ORDER BY prestige DESC, xp DESC LIMIT 10 OFFSET " . $place);
        $stmt->execute();
        $stmt->bind_result($name, $xp, $prestige);
        ++$place;
        $sender->sendMessage(" ");
        $text = TextFormat::GOLD . TextFormat::BOLD . "Mining Top List " . TextFormat::RESET . TextFormat::GRAY . "Page $page\n ";
        while($stmt->fetch()) {
            $text .= "\n" . TextFormat::BOLD . TextFormat::GOLD . "$place. " . TextFormat::RESET . TextFormat::WHITE . $name . TextFormat::GOLD . " - " . $this->getLevelTag($xp, $prestige);
            $place++;
        }
        $stmt->close();
        $sender->sendMessage($text);
        $sender->sendMessage(" ");
    }

    /**
     * @param int $xp
     * @param int $prestige
     *
     * @return string
     */
    public function getLevelTag(int $xp, int $prestige): string {
        $level = XPUtils::xpToLevel($xp);
        if($prestige <= 0) {
            return TextFormat::RESET . TextFormat::WHITE . $level . TextFormat::GRAY . " (" . number_format($xp) . ")" . TextFormat::RESET;
        }
        return TextFormat::RESET . TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "<" . TextFormat::AQUA . EnchantmentManager::getRomanNumber($prestige) . TextFormat::LIGHT_PURPLE . ">" . TextFormat::RESET . TextFormat::WHITE . $level . TextFormat::GRAY . " (" . number_format($xp) . ")" . TextFormat::RESET;
    }
}