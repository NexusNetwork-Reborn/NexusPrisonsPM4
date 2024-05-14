<?php

namespace core\game\plots\command\inventory;

use core\command\task\TeleportTask;
use core\game\plots\command\forms\PlotManageForm;
use core\game\plots\command\forms\PlotPurchaseConfirmationForm;
use core\game\plots\plot\Plot;
use core\game\plots\PlotManager;
use core\game\plots\task\TickPlotsInventory;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use libs\utils\Utils;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;

class PlotListSectionInventory extends InvMenu
{

    /** @var Plot[] */
    private $plots;

    /** @var Plot[] */
    private $plotsIndexes = [];

    /** @var int */
    private $page;

    /**
     * PlotListSectionInventory constructor.
     *
     * @param World $world
     * @param int $page
     */
    public function __construct(World $world, int $page = 1)
    {
        parent::__construct(InvMenuHandler::getTypeRegistry()->get(InvMenu::TYPE_DOUBLE_CHEST));
        $this->plots = Nexus::getInstance()->getGameManager()->getPlotManager()->getPlotsByWorld($world);
        $this->page = $page;
        $this->initItems();
        $this->setName(TextFormat::RESET . TextFormat::AQUA . TextFormat::BOLD . "Plots");
        $this->setListener(self::readonly(function (DeterministicInvMenuTransaction $transaction) use ($world): void {
            $action = $transaction->getAction();
            $slot = $action->getSlot();
            $player = $transaction->getPlayer();
            if (!$player instanceof NexusPlayer) {
                return;
            }
            if ($slot === 0) {
                $player->removeCurrentWindow();
                $player->sendDelayedWindow(new PlotMenuInventory());
            }
            if ($slot === 4) {
                $player->removeCurrentWindow();
                $player->sendDelayedForm(new PlotManageForm($player));
            }
            if ($slot === 8) {
                $player->removeCurrentWindow();
                Nexus::getInstance()->getScheduler()->scheduleRepeatingTask(new TeleportTask($player, $world->getSpawnLocation(), 10), 20);
            }
            if ($slot === 47 and $this->page > 1) {
                --$this->page;
            }
            if ($slot >= 9 and $slot <= 44) {
                if (isset($this->plotsIndexes[$slot])) {
                    $entry = $this->plotsIndexes[$slot];
                    $owner = $entry->getOwner();
                    if ($owner !== null and $entry->getExpiration() > 0) {
                        if ($owner->getUser($player->getName()) !== null or $owner->getUsername() === $player->getName()) {
                            Nexus::getInstance()->getScheduler()->scheduleRepeatingTask(new TeleportTask($player, $entry->getSpawn(), 10), 20);
                        } else {
                            $player->playErrorSound();
                            $player->sendMessage(Translation::RED . "You don't have access to this plot!");
                        }
                    } else {
                        if (!PlotManager::isPlotWorld($player->getWorld())) {
                            if (Nexus::getInstance()->getServerManager()->getAreaManager()->canDamage($player->getPosition())) {
                                $player->sendTranslatedMessage("inWarzone");
                                $player->playErrorSound();
                                return;
                            }
                        }
                        $plotManager = Nexus::getInstance()->getGameManager()->getPlotManager();
                        foreach($player->getAlias() as $alias) {
                            if($plotManager->getPlotByOwner($alias) !== null) {
                                $player->playErrorSound();
                                $player->sendMessage(Translation::RED . "You can only own 1 plot per an IP!");
                                return;
                            }
                        }
                        $plotManager = Nexus::getInstance()->getGameManager()->getPlotManager();
                        if ($plotManager->getPlotByOwner($player->getName()) !== null) {
                            $player->playErrorSound();
                            $player->sendMessage(Translation::RED . "You can only own 1 plot per player account!");
                            return;
                        }
                        $player->sendDelayedForm(new PlotPurchaseConfirmationForm($entry));
                    }
                    $player->removeCurrentWindow();
                }
            }
            if ($slot === 51 and $this->page < ceil(count($this->plots) / 36)) {
                ++$this->page;
            }
            return;
        }));
        Nexus::getInstance()->getScheduler()->scheduleRepeatingTask(new TickPlotsInventory($this), 20);
    }

    public function initItems(): void
    {
        $plots = $this->getPageItems();
        for ($i = 0; $i < 9; $i++) {
            if ($i === 0) {
                $home = VanillaBlocks::OAK_DOOR()->asItem();
                $home->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Home");
                $lore = [];
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "Return to the main";
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "plots page";
                $home->setLore($lore);
                $this->getInventory()->setItem($i, $home);
                continue;
            }
            if ($i === 4) {
                $offers = VanillaItems::PAPER();
                $offers->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Plots");
                $lore = [];
                $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Use this menu to manage";
                $lore[] = TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "and teleport to plots";
                $lore[] = "";
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "Shows the plots that you own";
                $offers->setLore($lore);
                $this->getInventory()->setItem($i, $offers);
                continue;
            }
            if ($i === 8) {
                $offers = VanillaItems::ENDER_PEARL();
                $offers->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_AQUA . "Teleport");
                $lore = [];
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "Teleport to this plot";
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "world's spawn";
                $offers->setLore($lore);
                $this->getInventory()->setItem($i, $offers);
                continue;
            }
        }
        $i = 9;
        foreach ($plots as $plot) {
            if ($plot->getOwner() !== null) {
                if ($plot->getExpiration() < 0) {
                    $item = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::RED())->asItem();
                } else {
                    $item = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::WHITE())->asItem();
                }
            } else {
                $item = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::GRAY())->asItem();
            }
            $price = PlotManager::getPlotPrice($plot);
            $item->setCustomName(TextFormat::RESET . TextFormat::WHITE . "Plot " . TextFormat::BOLD . TextFormat::AQUA . $plot->getId());
            $time = $plot->getExpiration();
            $lore = $item->getLore();
            $lore[] = "";
            $lore[] = TextFormat::RESET . TextFormat::WHITE . "Price " . TextFormat::GREEN . "$" . number_format($price, 2);
            $lore[] = TextFormat::RESET . TextFormat::WHITE . "Security " . TextFormat::BOLD . PlotManager::getPlotColor($plot) . ucfirst($plot->getWorld()->getFolderName());
            $lore[] = "";
            if ($plot->getOwner() !== null) {
                $lore[] = TextFormat::RESET . TextFormat::WHITE . "Owner " . TextFormat::BOLD . TextFormat::GOLD . $plot->getOwner()->getUsername();
                $lore[] = "";
                if ($time > 0) {
                    $lore[] = TextFormat::RESET . TextFormat::GRAY . "Expiring " . TextFormat::GOLD . Utils::secondsToTime($time);
                } else {
                    $lore[] = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "EXPIRED";
                    $lore[] = "";
                    $lore[] = TextFormat::RESET . TextFormat::GRAY . "Click to purchase";
                }
            } else {
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "Click to purchase";
            }
            $item->setLore($lore);
            $this->plotsIndexes[$i] = $plot;
            $this->getInventory()->setItem($i, $item);
            ++$i;
        }
        for ($i = (9 + count($plots)); $i < 54; $i++) {
            if ($i === 47 and $this->page > 1) {
                $prevPage = $this->page - 1;
                $pageSelector = VanillaBlocks::REDSTONE()->asItem();
                $pageSelector->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "<  Previous page ($prevPage)");
                $this->getInventory()->setItem($i, $pageSelector);
                continue;
            }
            if ($i === 49) {
                $page = VanillaItems::PAPER();
                $page->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Page $this->page");
                $this->getInventory()->setItem($i, $page);
                continue;
            }
            if ($i === 51 and $this->page < $this->getMaxPages()) {
                $nextPage = $this->page + 1;
                $pageSelector = VanillaBlocks::REDSTONE()->asItem();
                $pageSelector->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Next page ($nextPage)  >");
                $this->getInventory()->setItem($i, $pageSelector);
                continue;
            }
            $this->getInventory()->setItem($i, VanillaBlocks::AIR()->asItem());
        }
    }

    public function tick(): bool
    {
        $plots = $this->getPageItems($this->page);
        $i = 9;
        foreach ($plots as $plot) {
            if ($plot->getOwner() !== null) {
                if ($plot->getExpiration() < 0) {
                    $item = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::RED())->asItem();
                } else {
                    $item = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::WHITE())->asItem();
                }
            } else {
                $item = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::GRAY())->asItem();
            }
            $price = PlotManager::getPlotPrice($plot);
            $item->setCustomName(TextFormat::RESET . TextFormat::WHITE . "Plot " . TextFormat::BOLD . TextFormat::AQUA . $plot->getId());
            $time = $plot->getExpiration();
            $lore = $item->getLore();
            $lore[] = "";
            $lore[] = TextFormat::RESET . TextFormat::WHITE . "Price " . TextFormat::GREEN . "$" . number_format($price, 2);
            $lore[] = TextFormat::RESET . TextFormat::WHITE . "Security " . TextFormat::BOLD . PlotManager::getPlotColor($plot) . ucfirst($plot->getWorld()->getFolderName());
            $lore[] = "";
            if ($plot->getOwner() !== null) {
                $lore[] = TextFormat::RESET . TextFormat::WHITE . "Owner " . TextFormat::BOLD . TextFormat::GOLD . $plot->getOwner()->getUsername();
                $lore[] = "";
                if ($time > 0) {
                    $lore[] = TextFormat::RESET . TextFormat::GRAY . "Expiring " . TextFormat::GOLD . Utils::secondsToTime($time);
                } else {
                    $lore[] = TextFormat::RESET . TextFormat::RED . TextFormat::BOLD . "EXPIRED";
                    $lore[] = "";
                    $lore[] = TextFormat::RESET . TextFormat::GRAY . "Click to purchase";
                }
            } else {
                $lore[] = TextFormat::RESET . TextFormat::GRAY . "Click to purchase";
            }
            $item->setLore($lore);
            $this->plotsIndexes[$i] = $plot;
            $this->getInventory()->setItem($i, $item);
            ++$i;
        }
        for ($i = (9 + count($plots)); $i < 54; $i++) {
            if ($i === 47 and $this->page > 1) {
                $prevPage = $this->page - 1;
                $pageSelector = VanillaBlocks::REDSTONE()->asItem();
                $pageSelector->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "<  Previous page ($prevPage)");
                $this->getInventory()->setItem($i, $pageSelector);
                continue;
            }
            if ($i === 49) {
                $page = VanillaItems::PAPER();
                $page->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Page $this->page");
                $this->getInventory()->setItem($i, $page);
                continue;
            }
            if ($i === 51 and $this->page < $this->getMaxPages()) {
                $nextPage = $this->page + 1;
                $pageSelector = VanillaBlocks::REDSTONE()->asItem();
                $pageSelector->setCustomName(TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "Next page ($nextPage)  >");
                $this->getInventory()->setItem($i, $pageSelector);
                continue;
            }
            $this->getInventory()->setItem($i, VanillaBlocks::AIR()->asItem());
        }
        foreach ($this->getInventory()->getViewers() as $viewer) {
            if ($viewer instanceof NexusPlayer and $viewer->isOnline()) {
                $viewer->getNetworkSession()->getInvManager()->syncContents($this->getInventory());
            }
        }
        return true;
    }

    /**
     * @param int $page
     *
     * @return Plot[]
     */
    public function getPageItems(int $page = 1): array
    {
        return array_chunk($this->plots, 36, true)[$page - 1] ?? [];
    }

    /**
     * @return int
     */
    public function getMaxPages(): int
    {
        return ceil(count($this->plots) / 36);
    }

    /**
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * @return Plot[]
     */
    public function getPlots(): array
    {
        return $this->plots;
    }

}