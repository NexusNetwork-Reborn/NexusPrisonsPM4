<?php

namespace core\provider;

use core\player\NexusPlayer;
use libs\utils\Utils;

class SocketRequests {

    private static $requests = [];

    const CONFIRMATION_REQUEST = "CONFIRMATION_REQUEST";

    const SET_ROLES = "SET_ROLES";

    const SEND_ALERT = "SEND_ALERT";

    /**
     * @param string $type
     * @param NexusPlayer $player
     *
     * @return bool
     */
    public static function hasOutgoingRequest(string $type, NexusPlayer $player): bool {
        if(!isset(self::$requests[$type][$player->getName()])) {
            return false;
        }
        return (time() - self::$requests[$type][$player->getName()]) < 300;
    }

    /**
     * @param NexusPlayer $player
     * @param string $discordId
     */
    public static function sendConfirmationRequest(NexusPlayer $player, string $discordId): void {
        $socket = stream_socket_client("tcp://127.0.0.1:47806");
        $time = time();
        $request = [
            "type" => self::CONFIRMATION_REQUEST,
            "username" => $player->getName(),
            "discordId" => $discordId,
            "time" => $time
        ];
        Utils::sendDataToSocket($socket, json_encode($request));
        self::$requests[self::CONFIRMATION_REQUEST][$player->getName()] = $time;
    }

    /**
     * @param NexusPlayer $player
     * @param string $discordId
     * @param string $role
     */
    public static function sendSetRoleRequest(NexusPlayer $player, string $discordId, string $role): void {
        $socket = stream_socket_client("tcp://127.0.0.1:47806");
        $request = [
            "type" => self::SET_ROLES,
            "username" => $player->getName(),
            "discordId" => $discordId,
            "role" => $role,
        ];
        Utils::sendDataToSocket($socket, json_encode($request));
    }

    /**
     * @param NexusPlayer $player
     * @param string $discordId
     * @param string $message
     */
    public static function sendAlertRequest(NexusPlayer $player, string $discordId, string $message): void {
        $socket = stream_socket_client("tcp://127.0.0.1:47806");
        $request = [
            "type" => self::SEND_ALERT,
            "username" => $player->getName(),
            "discordId" => $discordId,
            "message" => $message,
        ];
        Utils::sendDataToSocket($socket, json_encode($request));
    }
}