<?php
declare(strict_types=1);

namespace core\command;

use core\command\types\AddMoneyCommand;
use core\command\types\AddPermissionCommand;
use core\command\types\AddXPCommand;
use core\command\types\AgeCommand;
use core\command\types\AliasCommand;
use core\command\types\BalanceCommand;
use core\command\types\BalanceTopCommand;
use core\command\types\BankBlockCommand;
use core\command\types\BoostsCommand;
use core\command\types\BossCommand;
use core\command\types\BroadcastCommand;
use core\command\types\CEInfoCommand;
use core\command\types\ChangeLogCommand;
use core\command\types\ClearCommand;
use core\command\types\CriminalRecordCommand;
use core\command\types\DebugCommand;
use core\command\types\DisguiseCommand;
use core\command\types\DrinkLimitCommand;
use core\command\types\DupeCommand;
use core\command\types\EnchantCommand;
use core\command\types\ExecutiveCommand;
use core\command\types\ExtractCommand;
use core\command\types\FeedCommand;
use core\command\types\FindNickCommand;
use core\command\types\FlyCommand;
use core\command\types\FreezeCommand;
use core\command\types\GiveItemCommand;
use core\command\types\GlobalMuteCommand;
use core\command\types\GodCommand;
use core\command\types\HomeCommand;
use core\command\types\InboxCommand;
use core\command\types\InformationCommand;
use core\command\types\ItemSkinCommand;
use core\command\types\JetCommand;
use core\command\types\KitCommand;
use core\command\types\KOTHCommand;
use core\command\types\LastDeathCommand;
use core\command\types\LevelCapCommand;
use core\command\types\LevelTopCommand;
use core\command\types\ListCommand;
use core\command\types\LobbyCommand;
use core\command\types\LootboxCommand;
use core\command\types\MeteorTimerCommand;
use core\command\types\MinesCommand;
use core\command\types\NearCommand;
use core\command\types\NickCommand;
use core\command\types\NickResetCommand;
use core\command\types\OnlineTimeCommand;
use core\command\types\PayCommand;
use core\command\types\PingCommand;
use core\command\types\PlaySoundCommand;
use core\command\types\PrestigeCommand;
use core\command\types\PulseCommand;
use core\command\types\QuestsCommand;
use core\command\types\RemoveGuardCommand;
use core\command\types\RemoveHomeCommand;
use core\command\types\RemoveItemSkinCommand;
use core\command\types\RemoveMaskCommand;
use core\command\types\ReplyCommand;
use core\command\types\RestedCommand;
use core\command\types\RulesCommand;
use core\command\types\SaveGeometryCommand;
use core\command\types\SaveSkinCommand;
use core\command\types\SeeBragCommand;
use core\command\types\SeeItemCommand;
use core\command\types\SellCommand;
use core\command\types\SetHomeCommand;
use core\command\types\SetMoneyCommand;
use core\command\types\SetRankCommand;
use core\command\types\GKitCommand;
use core\command\types\ShopCommand;
use core\command\types\ShowcaseCommand;
use core\command\types\SkinListCommand;
use core\command\types\SpawnCommand;
use core\command\types\SpawnGuardCommand;
use core\command\types\SpectateCommand;
use core\command\types\StaffChatCommand;
use core\command\types\StopCommand;
use core\command\types\TeleportAskCommand;
use core\command\types\TellCommand;
use core\command\types\TestCommand;
use core\command\types\TitleCommand;
use core\command\types\TitleResetCommand;
use core\command\types\TradeCommand;
use core\command\types\TrashCommand;
use core\command\types\VanishCommand;
use core\command\types\VoteCommand;
use core\command\types\WithdrawCommand;
use core\command\types\XPExtractCommand;
use core\command\types\XYZCommand;
use core\game\auction\command\AuctionHouseCommand;
use core\game\blackAuction\command\BlackAuctionHouseCommand;
use core\game\boop\command\BanCommand;
use core\game\boop\command\BlockCommand;
use core\game\boop\command\HistoryCommand;
use core\game\boop\command\KickCommand;
use core\game\boop\command\MuteCommand;
use core\game\boop\command\PardonCommand;
use core\game\boop\command\PunishCommand;
use core\game\boop\command\TempBanCommand;
use core\game\boop\command\TempBlockCommand;
use core\game\boop\command\UnblockCommand;
use core\game\boop\command\UnmuteCommand;
use core\game\fund\command\FundCommand;
use core\game\gamble\command\CoinFlipCommand;
use core\game\gamble\command\LotteryCommand;
use core\game\item\sets\command\GiveSetCommand;
use core\game\plots\command\PlotCommand;
use core\player\gang\command\GangCommand;
use core\Nexus;
use core\player\rank\Rank;
use core\player\vault\command\PlayerVaultCommand;
use pocketmine\command\Command;
use pocketmine\plugin\PluginException;

class CommandManager {

    const DISGUISES = [
        "ButterBean46" => Rank::NOBLE,
        "FinnaDropEm21" => Rank::NOBLE,
        "DuckThePolice12" => Rank::IMPERIAL,
        "LetsScrapF00l" => Rank::PLAYER,
        "XtraCapp" => Rank::IMPERIAL,
        "BigRipsss" => Rank::PLAYER,
        "DrunkenSailer123" => Rank::IMPERIAL,
        "thiccMarshall" => Rank::SUPREME,
        "FBIwatchinu" => Rank::SUPREME,
        "HeavySetJoe321" => Rank::PLAYER,
        "Stonrs4Lif3" => Rank::PLAYER,
    ];

    /** @var Nexus */
    private $core;

    /** @var string */
    private $usedDisguise = [];

    /**
     * CommandManager constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
        $core->getServer()->getPluginManager()->registerEvents(new CommandListener($core), $core);
        $this->init();
    }

    public function init(): void {
        $this->registerCommand(new AddMoneyCommand());
        $this->registerCommand(new AddPermissionCommand());
        $this->registerCommand(new AddXPCommand());
        $this->registerCommand(new AgeCommand());
        $this->registerCommand(new AliasCommand());
        $this->registerCommand(new AuctionHouseCommand());
        $this->registerCommand(new BalanceCommand());
        $this->registerCommand(new BalanceTopCommand());
        $this->registerCommand(new BanCommand());
        $this->registerCommand(new BankBlockCommand());
        $this->registerCommand(new BlackAuctionHouseCommand());
        $this->registerCommand(new BlockCommand());
        $this->registerCommand(new BoostsCommand());
        $this->registerCommand(new BroadcastCommand());
        $this->registerCommand(new CEInfoCommand());
        $this->registerCommand(new ChangeLogCommand());
        $this->registerCommand(new ClearCommand());
        $this->registerCommand(new CriminalRecordCommand());
        $this->registerCommand(new CoinFlipCommand());
        $this->registerCommand(new DebugCommand());
        $this->registerCommand(new DisguiseCommand());
        $this->registerCommand(new DupeCommand());
        $this->registerCommand(new DrinkLimitCommand());
        $this->registerCommand(new EnchantCommand());
        $this->registerCommand(new ExtractCommand());
        $this->registerCommand(new FeedCommand());
        $this->registerCommand(new FindNickCommand());
        $this->registerCommand(new FlyCommand());
        $this->registerCommand(new FreezeCommand());
        $this->registerCommand(new FundCommand());
        $this->registerCommand(new GangCommand());
        $this->registerCommand(new GiveItemCommand());
        $this->registerCommand(new GKitCommand());
        $this->registerCommand(new GlobalMuteCommand());
        $this->registerCommand(new GodCommand());
        $this->registerCommand(new HistoryCommand());
        $this->registerCommand(new HomeCommand());
        $this->registerCommand(new InboxCommand());
        $this->registerCommand(new JetCommand());
        $this->registerCommand(new KickCommand());
        $this->registerCommand(new KitCommand());
        $this->registerCommand(new KOTHCommand());
        $this->registerCommand(new LastDeathCommand());
        $this->registerCommand(new LevelCapCommand());
        $this->registerCommand(new LevelTopCommand());
        $this->registerCommand(new ListCommand());
        $this->registerCommand(new LobbyCommand());
        $this->registerCommand(new LootboxCommand());
        $this->registerCommand(new LotteryCommand());
        $this->registerCommand(new MeteorTimerCommand());
        $this->registerCommand(new MinesCommand());
        $this->registerCommand(new BossCommand());
        $this->registerCommand(new ExecutiveCommand());
        $this->registerCommand(new MuteCommand());
        $this->registerCommand(new NearCommand());
        $this->registerCommand(new NickCommand());
        $this->registerCommand(new NickResetCommand());
        $this->registerCommand(new OnlineTimeCommand());
        $this->registerCommand(new PardonCommand());
        $this->registerCommand(new PayCommand());
        $this->registerCommand(new PingCommand());
        $this->registerCommand(new PlayerVaultCommand());
        $this->registerCommand(new PlaySoundCommand());
        $this->registerCommand(new PlotCommand());
        $this->registerCommand(new PrestigeCommand());
        $this->registerCommand(new PulseCommand());
        $this->registerCommand(new PunishCommand());
        $this->registerCommand(new QuestsCommand());
        $this->registerCommand(new RemoveGuardCommand());
        $this->registerCommand(new RemoveHomeCommand());
        $this->registerCommand(new RemoveMaskCommand());
        //$this->registerCommand(new RemoveItemSkinCommand());
        $this->registerCommand(new SkinListCommand());
        $this->registerCommand(new ItemSkinCommand());
        $this->registerCommand(new ReplyCommand());
        $this->registerCommand(new RestedCommand());
        $this->registerCommand(new RulesCommand());
        $this->registerCommand(new SaveGeometryCommand());
        $this->registerCommand(new SaveSkinCommand());
        $this->registerCommand(new SeeBragCommand());
        $this->registerCommand(new SeeItemCommand());
        $this->registerCommand(new SellCommand());
        $this->registerCommand(new SetHomeCommand());
        $this->registerCommand(new SetMoneyCommand());
        $this->registerCommand(new SetRankCommand());
        $this->registerCommand(new ShopCommand());
        $this->registerCommand(new ShowcaseCommand());
        $this->registerCommand(new SpawnCommand());
        $this->registerCommand(new SpawnGuardCommand());
        $this->registerCommand(new SpectateCommand());
        $this->registerCommand(new StaffChatCommand());
        $this->registerCommand(new StopCommand());
        $this->registerCommand(new TeleportAskCommand());
        $this->registerCommand(new TellCommand());
        $this->registerCommand(new TempBanCommand());
        $this->registerCommand(new TempBlockCommand());
        $this->registerCommand(new TestCommand());
        //$this->registerCommand(new TinkererCommand());
        $this->registerCommand(new TitleCommand());
        $this->registerCommand(new TitleResetCommand());
        $this->registerCommand(new TradeCommand());
        $this->registerCommand(new TrashCommand());
        $this->registerCommand(new UnblockCommand());
        $this->registerCommand(new UnmuteCommand());
        $this->registerCommand(new VanishCommand());
        $this->registerCommand(new VoteCommand());
        $this->registerCommand(new WithdrawCommand());
        $this->registerCommand(new XPExtractCommand());
        $this->registerCommand(new XYZCommand());
        $this->registerCommand(new GiveSetCommand());
        $this->unregisterCommand("about");
        $this->unregisterCommand("help");
        $this->unregisterCommand("me");
        $this->unregisterCommand("particle");
    }

    /**
     * @param Command $command
     */
    public function registerCommand(Command $command): void {
        $commandMap = $this->core->getServer()->getCommandMap();
        $existingCommand = $commandMap->getCommand($command->getName());
        if($existingCommand !== null) {
            $commandMap->unregister($existingCommand);
        }
        $commandMap->register($command->getName(), $command);
    }

    /**
     * @param string $name
     */
    public function unregisterCommand(string $name): void {
        $commandMap = $this->core->getServer()->getCommandMap();
        $command = $commandMap->getCommand($name);
        if($command === null) {
            throw new PluginException("Invalid command: $name to un-register.");
        }
        $commandMap->unregister($commandMap->getCommand($name));
    }

    /**
     * @param array|null $disguises
     *
     * @return string|null
     */
    public function selectDisguise(?array $disguises = null): ?string {
        if($disguises === null) {
            $disguises = self::DISGUISES;
        }
        if(empty($disguises)) {
            return null;
        }
        $name = array_rand($disguises);
        if(in_array($name, $this->usedDisguise)) {
            unset($disguises[$name]);
            return $this->selectDisguise($disguises);
        }
        $this->usedDisguise[] = $name;
        return $name;
    }

    /**
     * @param string $name
     */
    public function removeUsedDisguise(string $name): void {
        unset($this->usedDisguise[array_search($name, $this->usedDisguise)]);
    }
}