<?php

namespace core\game\plots\plot;

use core\player\NexusPlayer;

class PermissionManager {

    const PERMISSION_PLACE = "Place";

    const PERMISSION_BREAK = "Break";

    const PERMISSION_DOORS = "Doors";

    const PERMISSION_MINE = "Mine";

    const PERMISSION_CHESTS = "Chests";

    const DEFAULTS = [
        self::PERMISSION_PLACE => false,
        self::PERMISSION_BREAK => false,
        self::PERMISSION_DOORS => true,
        self::PERMISSION_MINE => true,
        self::PERMISSION_CHESTS => false
    ];

    /** @var bool[] */
    private $permissions;

    /**
     * PermissionManager constructor.
     *
     * @param array $permissions
     */
    public function __construct(array $permissions) {
        $this->permissions = $this->validate($permissions);
    }

    /**
     * @param array $permissions
     *
     * @return array
     */
    public function validate(array $permissions): array {
        foreach(self::DEFAULTS as $permission => $fallback) {
            if(!isset($permissions[$permission])) {
                $permissions[$permission] = $fallback;
            }
        }
        return $permissions;
    }

    /**
     * @param string $permission
     * @param bool $value
     */
    public function setValue(string $permission, bool $value): void {
        $this->permissions[$permission] = $value;
    }

    /**
     * @param string $permission
     *
     * @return bool
     */
    public function hasPermission(string $permission): bool {
        if(!isset($this->permissions[$permission])) {
            $this->permissions[$permission] = self::DEFAULTS[$permission];
        }
        return $this->permissions[$permission];
    }

    /**
     * @return bool]
     */
    public function getPermissions(): array {
        return $this->permissions;
    }
}