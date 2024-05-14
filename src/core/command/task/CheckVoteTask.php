<?php

declare(strict_types = 1);

namespace core\command\task;

use core\game\item\types\GodStone;
use core\Nexus;
use core\player\NexusPlayer;
use core\translation\Translation;
use core\translation\TranslationException;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Internet;
use pocketmine\utils\TextFormat;

class CheckVoteTask extends AsyncTask {

    const API_KEY = "wsi7ZP60Lec2bPAo6q4vGE1NHncICU0AY";

    const STATS_URL = "https://minecraftpocket-servers.com/api/?object=servers&element=detail&key=" . self::API_KEY;

    const CHECK_URL = "http://minecraftpocket-servers.com/api-vrc/?object=votes&element=claim&key=" . self:: API_KEY . "&username={USERNAME}";

    const POST_URL = "http://minecraftpocket-servers.com/api-vrc/?action=post&object=votes&element=claim&key=" . self:: API_KEY . "&username={USERNAME}";

    const VOTED = "voted";

    const CLAIMED = "claimed";

    /** @var string */
    private $player;

    /**
     * CheckVoteTask constructor.
     *
     * @param string $player
     */
    public function __construct(string $player) {
        $this->player = $player;
    }

    public function onRun(): void {
        $result = [];
        $player = str_replace(" ", "%20", $this->player);
        $get = Internet::getURL(str_replace("{USERNAME}", $player, self::CHECK_URL));
        if($get === null) {
            return;
        }
        $get = json_decode($get->getBody(), true);
        if((!isset($get[self::VOTED])) or (!isset($get[self::CLAIMED]))) {
            return;
        }
        $result[self::VOTED] = $get[self::VOTED];
        $result[self::CLAIMED] = $get[self::CLAIMED];
        if($get[self::VOTED] === true and $get[self::CLAIMED] === false) {
            $post = Internet::postURL(str_replace("{USERNAME}", $player, self::POST_URL), []);
            if($post === false) {
                $result = null;
            }
        }
        $this->setResult($result);
    }

    /**
     * @param Server $server
     *
     * @throws TranslationException
     */
    public function onCompletion(): void {
        $server = Server::getInstance();
        $player = $server->getPlayerByPrefix($this->player);
        if((!$player instanceof NexusPlayer) or $player->isClosed()) {
            return;
        }
        $result = $this->getResult();
        if(empty($result)) {
            $player->sendMessage(Translation::getMessage("errorOccurred"));
            return;
        }
        $player->setCheckingForVote(false);
        if($result[self::VOTED] === true) {
            if($result[self::CLAIMED] === true) {
                $player->setVoted();
                $player->sendMessage(Translation::getMessage("alreadyVoted"));
                return;
            }
            $player->setVoted();
            $player->sendMessage(Translation::AQUA . "Thanks for voting at bit.ly/3m3AOdp!");
            $player->sendMessage(Translation::AQUA . "You've received 1 vote point!");
            $player->getDataSession()->addVotePoints();
            return;
        }
        $player->sendMessage(Translation::getMessage("haveNotVoted"));
        $player->setVoted(false);
    }
}