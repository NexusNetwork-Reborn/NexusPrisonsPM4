<?php

namespace core\player\vault;

use core\Nexus;

class VaultManager {

    /** @var Nexus */
    private $core;

    /** @var Vault[][] */
    private $vaults = [];

    /**
     * VaultManager constructor.
     *
     * @param Nexus $core
     */
    public function __construct(Nexus $core) {
        $this->core = $core;
    }

    /**
     * @param Vault $vault
     */
    public function addVault(Vault $vault) {
        $this->vaults[strtolower($vault->getOwner())][$vault->getId()] = $vault;
    }

    /**
     * @param string $owner
     * @param int $id
     */
    public function createVault(string $owner, int $id) {
        $empty = "";
        $connector = $this->core->getMySQLProvider()->getConnector();
        $connector->executeUpdate("INSERT INTO vaults(username, id, items) VALUES(?, ?, ?)", "sis", [$owner, $id, $empty]);
        $connector->executeSelect("SELECT uuid FROM vaults WHERE username = ? AND id = ?", "si", [
            $owner,
            $id
        ], function(array $rows) use($owner, $id): void {
            foreach($rows as ["uuid" => $uuid]) {
                $this->addVault(new Vault($owner, $uuid, $id));
            }
        });
    }

    /**
     * @param string $owner
     *
     * @return Vault[]
     */
    public function getVaultsFor(string $owner): array {
        if(!isset($this->vaults[strtolower($owner)])) {
            $database = $this->core->getMySQLProvider()->getDatabase();
            $stmt = $database->prepare("SELECT uuid, username, id, items FROM vaults WHERE username = ?");
            $stmt->bind_param("s", $owner);
            $stmt->bind_result($uuid, $name, $id, $items);
            $stmt->execute();
            while($stmt->fetch()) {
                if($name === null) {
                    return [];
                }
                $this->vaults[strtolower($owner)][$id] = new Vault($name, $uuid, $id, $items);
            }
            $stmt->close();
        }
        return $this->vaults[strtolower($owner)] ?? [];
    }

    /**
     * @param string $owner
     * @param int $id
     *
     * @return Vault|null
     */
    public function getVault(string $owner, int $id): ?Vault {
        if(!isset($this->vaults[strtolower($owner)])) {
            $database = $this->core->getMySQLProvider()->getDatabase();
            $stmt = $database->prepare("SELECT uuid, username, id, items FROM vaults WHERE username = ?");
            $stmt->bind_param("s", $owner);
            $stmt->bind_result($uuid, $name, $id, $items);
            $stmt->execute();
            while($stmt->fetch()) {
                if($name === null) {
                    return null;
                }
                $this->vaults[strtolower($owner)][$id] = new Vault($name, $uuid, $id, $items);
            }
            $stmt->close();
        }
        return $this->vaults[strtolower($owner)][$id] ?? null;
    }
}