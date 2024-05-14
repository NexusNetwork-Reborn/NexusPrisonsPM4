<?php

declare(strict_types = 1);

namespace core\game\boop\task;

use core\Nexus;
use core\player\NexusPlayer;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Internet;
use pocketmine\utils\TextFormat;

class ProxyCheckTask extends AsyncTask {

    const URL = "http://v2.api.iphub.info/ip/{ADDRESS}";

    /** @var string */
    private $player;

    /** @var string */
    private $address;

    /** @var string */
    private $key;

    /**
     * ProxyCheckTask constructor.
     *
     * @param string $player
     * @param string $address
     * @param string $key
     */
    public function __construct(string $player, string $address, string $key) {
        $this->player = $player;
        $this->address = $address;
        $this->key = $key;
        Nexus::getInstance()->getLogger()->notice("Unknown ip detected in $player, checking for a vpn or proxy bow.");
    }

    public function onRun(): void {
        $url = str_replace("{ADDRESS}", $this->address, self::URL);
        $get = Internet::getURL($url, 10, ["X-Key: $this->key"]);
        if($get === null) {
            $this->setResult($get);
            return;
        }
        $get = json_decode($get->getBody(), true);
        if(!is_array($get)) {
            $this->setResult(false);
            return;
        }
        $result = $get["block"];
        $this->setResult($result);
        return;
    }

    /**
     * @param Server $server
     */
    public function onCompletion(): void {
        $result = $this->getResult();
        if($result === null) {
            $result = 1;
        }
        Nexus::getInstance()->getScheduler()->scheduleDelayedTask(new UploadIPTask($this->player, $this->address, $result), 1);
    }
}