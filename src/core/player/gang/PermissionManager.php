<?php
declare(strict_types=1);

namespace core\player\gang;

use core\Nexus;
use core\player\NexusPlayer;
use libs\utils\Utils;
use libs\utils\UtilsException;

class PermissionManager {

    const PERMISSION_ALLY = "Ally";

    const PERMISSION_DEPOSIT = "Deposit";

    const PERMISSION_WITHDRAW = "Withdraw";

    const PERMISSION_INVITE = "Invite";

    const PERMISSION_USE_VAULT = "Vault";

    const DEFAULTS = [
        self::PERMISSION_ALLY => Gang::OFFICER,
        self::PERMISSION_DEPOSIT => Gang::MEMBER,
        self::PERMISSION_WITHDRAW => Gang::OFFICER,
        self::PERMISSION_INVITE => Gang::OFFICER,
        self::PERMISSION_USE_VAULT => Gang::OFFICER
    ];

    /** @var Gang */
    private $gang;

    /** @var int[] */
    private $permissions;

    /**
     * PermissionManager constructor.
     *
     * @param Gang $gang
     * @param array $permissions
     *
     * @throws GangException
     */
    public function __construct(Gang $gang, array $permissions) {
        $this->gang = $gang;
        $this->permissions = $this->validate($permissions);
    }

    /**
     * @param array $permissions
     *
     * @return int[]
     *
     * @throws GangException
     */
    public function validate(array $permissions): array {
        foreach(self::DEFAULTS as $permission => $fallback) {
            if(!isset($permissions[$permission])) {
                $permissions[$permission] = $fallback;
            }
            if($permissions[$permission] < Gang::RECRUIT or $permissions[$permission] > Gang::LEADER) {
                throw new GangException("Invalid role \"$permissions[$permission]\" for permission: #$permission");
            }
        }
        return $permissions;
    }

    /**
     * @param string $permission
     * @param int $value
     *
     * @throws GangException
     * @throws UtilsException
     */
    public function setValue(string $permission, int $value): void {
        if($value < Gang::RECRUIT or $value > Gang::LEADER) {
            throw new GangException("Invalid role \"$value\" for permission: #$permission");
        }
        $this->permissions[$permission] = $value;
    }

    /**
     * @param NexusPlayer $player
     * @param string $permission
     *
     * @return bool
     */
    public function hasPermission(NexusPlayer $player, string $permission): bool {
        if(!isset($this->permissions[$permission])) {
            $this->permissions[$permission] = self::DEFAULTS[$permission];
        }
        return $player->getDataSession()->getGangRole() >= $this->permissions[$permission];
    }

    /**
     * @return int[]
     */
    public function getPermissions(): array {
        return $this->permissions;
    }
}