<?php

namespace core\game\plots\plot;

class PlotUser {

    /** @var string */
    private $username;

    /** @var PermissionManager */
    private $permissionManager;

    /**
     * PlotUser constructor.
     *
     * @param string $username
     * @param PermissionManager $permissions
     */
    public function __construct(string $username, PermissionManager $permissions) {
        $this->username = $username;
        $this->permissionManager = $permissions;
    }

    /**
     * @return string
     */
    public function getUsername(): string {
        return $this->username;
    }

    /**
     * @return PermissionManager
     */
    public function getPermissionManager(): PermissionManager {
        return $this->permissionManager;
    }
}