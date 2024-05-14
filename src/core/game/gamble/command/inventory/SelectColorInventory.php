<?php

namespace core\game\gamble\command\inventory;

use core\game\gamble\CoinFlipEntry;
use core\game\gamble\task\RollCoinFlipTask;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\utils\TextFormat;

class SelectColorInventory extends InvMenu {

    const META = [
        14, 1, 4, 5, 3, 10, 8, 15, 0
    ];

    /** @var int */
    private $amount;

    /** @var null|CoinFlipEntry */
    private $target;

    /**
     * SelectColorInventory constructor.
     *
     * @param int $amount
     * @param CoinFlipEntry|null $target
     */
    public function __construct(int $amount, ?CoinFlipEntry $target = null) {
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InvMenu::TYPE_CHEST));
        $this->amount = $amount;
        $this->target = $target;
        $this->initItems();
        $this->setName(TextFormat::RESET . TextFormat::BOLD . TextFormat::YELLOW . "Select a color");
        $this->setListener(self::readonly(function(DeterministicInvMenuTransaction $transaction): void {
            $slot = $transaction->getAction()->getSlot();
            $player = $transaction->getPlayer();
            if(!$player instanceof NexusPlayer) {
                return;
            }
            if($slot < 9) {
                $color = $this->metaToColor(self::META[$slot]);
                if($this->target === null) {
                    $player->sendMessage(TextFormat::GREEN . TextFormat::BOLD . "(!) " . TextFormat::RESET . TextFormat::GREEN . "Coin Flip queued, waiting for opponent to /coinflip");
                    $player->removeCurrentWindow();
                    $player->getDataSession()->subtractFromBalance($this->amount);
                    Nexus::getInstance()->getGameManager()->getGambleManager()->addCoinFlip($player, new CoinFlipEntry($player, $this->amount, $color, CoinFlipEntry::MONEY));
                }
                else {
                    $target = $this->target->getOwner();
                    if((!$target instanceof NexusPlayer) or (!$target->isOnline()) or (!$target->isLoaded())) {
                        $player->removeCurrentWindow();
                        $player->sendMessage(Translation::getMessage("invalidPlayer"));
                        return;
                    }
                    if($target->getUniqueId()->toString() === $player->getUniqueId()->toString()) {
                        $player->removeCurrentWindow();
                        $player->sendMessage(Translation::getMessage("invalidPlayer"));
                        return;
                    }
                    $cf = $player->getCore()->getGameManager()->getGambleManager()->getCoinFlip($target);
                    if($cf === null) {
                        $player->sendMessage(Translation::getMessage("invalidPlayer"));
                        return;
                    }
                    if($color === $this->target->getColor()) {
                        $player->playErrorSound();
                        return;
                    }
                    $player->getDataSession()->subtractFromBalance($this->amount);
                    $player->removeCurrentWindow();
                    Nexus::getInstance()->getGameManager()->getGambleManager()->removeCoinFlip($target);
                    $task = new RollCoinFlipTask($this->target, new CoinFlipEntry($player, $this->amount, $color, CoinFlipEntry::MONEY));
                    Nexus::getInstance()->getGameManager()->getGambleManager()->addActiveCoinFlip($task);
                    Nexus::getInstance()->getScheduler()->scheduleRepeatingTask($task, 1);
                }
            }
            return;
        }));
    }

    public function initItems(): void {
        for($i = 0; $i < 9; $i++) {
            $meta = self::META[$i];
            $wool = ItemFactory::getInstance()->get(ItemIds::WOOL, $meta, 1);
            if($this->target === null or ($this->target !== null and $this->metaToColor($meta) !== $this->target->getColor())) {
                $wool->setCustomName(TextFormat::RESET . TextFormat::BOLD . $this->metaToColor($meta) . $this->metaToColorName($meta));
                $lore = [];
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "Click to select this color";
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "and start the coin flip for";
                $lore[] = TextFormat::RESET . TextFormat::GREEN . "$" . number_format($this->amount);
                $wool->setLore($lore);
            }
            else {
                $wool->setCustomName(TextFormat::RESET . TextFormat::BOLD . $this->metaToColor($meta) . "Opponent's Color");
                $lore = [];
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "This color is not available.";
                $wool->setLore($lore);
            }
            $this->getInventory()->setItem($i, $wool);
        }
    }

    /**
     * @param string $meta
     *
     * @return string
     */
    public function metaToColor(string $meta): string {
        switch($meta) {
            case 14:
                return TextFormat::RED;
                break;
            case 1:
                return TextFormat::GOLD;
                break;
            case 4:
                return TextFormat::YELLOW;
                break;
            case 5:
                return TextFormat::GREEN;
                break;
            case 3:
                return TextFormat::AQUA;
                break;
            case 10:
                return TextFormat::DARK_PURPLE;
                break;
            case 8:
                return TextFormat::GRAY;
                break;
            case 15:
                return TextFormat::BLACK;
                break;
            default:
                return TextFormat::WHITE;
                break;
        }
    }

    /**
     * @param string $meta
     *
     * @return string
     */
    public function metaToColorName(string $meta): string {
        switch($meta) {
            case 14:
                return "Red";
                break;
            case 1:
                return "Orange";
                break;
            case 4:
                return "Yellow";
                break;
            case 5:
                return "Green";
                break;
            case 3:
                return "Blue";
                break;
            case 10:
                return "Purple";
                break;
            case 8:
                return "Gray";
                break;
            case 15:
                return "Black";
                break;
            default:
                return "White";
                break;
        }
    }
}