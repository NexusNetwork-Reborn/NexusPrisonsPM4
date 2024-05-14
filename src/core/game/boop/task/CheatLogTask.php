<?php

namespace core\game\boop\task;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class CheatLogTask extends AsyncTask {

    /** @var string */
    private $curlopts;

    /**
     * CheatLogTask constructor.
     *
     * @param string $message
     */
    public function __construct(string $message) {
        $this->curlopts = serialize($curlopts = [
            "content" => $message,
            "username" => null
        ]);
    }

    public function onRun(): void {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, "https://discord.com/api/webhooks/1013558740512952411/ZHE0MdYMuX-QFDUHcAQOqFgxJ2aMOLe9B-bRxYjwvu1W8v0CM3oN7oNhg5MLs2xOiiqh");
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(unserialize($this->curlopts)));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($curl);
        $curlerror = curl_error($curl);
        $responsejson = json_decode($response, true);
        $success = false;
        $error = "IDK What happened";
        if($curlerror != "") {
            $error = $curlerror;
        }
        elseif(curl_getinfo($curl, CURLINFO_HTTP_CODE) != 204) {
            $error = $responsejson["message"];
        }
        elseif(curl_getinfo($curl, CURLINFO_HTTP_CODE) == 204 OR $response === "") {
            $success = true;
        }
        $result = ["Response" => $response, "Error" => $error, "success" => $success];
        $this->setResult($result);
    }

    /**
     * @param Server $server
     */
    public function onCompletion(): void {
    }
}

