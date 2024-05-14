<?php

namespace core\provider\thread;

use core\provider\MySQLCredentials;
use core\provider\MySQLException;
use pocketmine\thread\Thread;
use Threaded;

class MySQLThread extends Thread {

    /** @var bool */
    private $running = true;

    /** @var Threaded */
    private $queries;

    /** @var Threaded */
    private $results = [];

    /** @var int */
    private $queryId = 0;

    /** @var string */
    private $credentials;

    /**
     * MySQLThread constructor.
     *
     * @param MySQLCredentials $credentials
     */
    public function __construct(MySQLCredentials $credentials) {
        $this->queries = new Threaded();
        $this->credentials = serialize($credentials);
    }

    /**
     * @throws MySQLException
     */
    public function onRun(): void {
        $this->registerClassLoaders();
        /** @var MySQLCredentials $credentials */
        $credentials = unserialize($this->credentials);
        $mysqli = $credentials->createInstance();
        while($this->running) {
            while(($query = $this->queries->shift()) !== null) {
                $query = igbinary_unserialize($query);
                switch($query["type"]) {
                    case "select":
                        $stmt = $mysqli->prepare($query["query"]);
                        if(!$stmt->bind_param($query["types"], ...$query["params"])) {
                            throw new MySQLException("MySQL error: " . $stmt->error . ($query["query"] === null ? "" : (", for query {$query["query"]} | " . json_encode($query["params"]))));
                        }
                        if(!$stmt->execute()) {
                            throw new MySQLException("MySQL error: " . $stmt->error . ($query["query"] === null ? "" : (", for query {$query["query"]} | " . json_encode($query["params"]))));
                        }
                        $results = [];
                        $res = $stmt->get_result();
                        while($row = $res->fetch_assoc()) {
                            $results[] = $row;
                        }
                        $stmt->close();
                        $this->results[] = igbinary_serialize([
                            "id" => $query["id"],
                            "result" => $results
                        ]);
                        break;
                    case "selectQuery":
                        $result = $mysqli->query($query["query"]);
                        if(!$result) {
                            throw new MySQLException("MySQL error: " . $mysqli->error . ($query["query"] === null ? "" : (", for query {$query["query"]} | " . json_encode($query["params"]))));
                        }
                        $results = [];
                        while($row = $result->fetch_assoc()) {
                            $results[] = $row;
                        }
                        $this->results[] = igbinary_serialize([
                            "id" => $query["id"],
                            "result" => $results
                        ]);
                        break;
                    case "update":
                        $stmt = $mysqli->prepare($query["query"]);
                        if(!$stmt->bind_param($query["types"], ...$query["params"])) {
                            throw new MySQLException("MySQL error: " . $stmt->error . ($query["query"] === null ? "" : (", for query {$query["query"]} | " . json_encode($query["params"]))));
                        }
                        if(!$stmt->execute()) {
                            throw new MySQLException("MySQL error: " . $stmt->error . ($query["query"] === null ? "" : (", for query {$query["query"]} | " . json_encode($query["params"]))));
                        }
                        $stmt->close();
                        break;
                }
            }
            $this->sleep();
        }
    }

    public function sleep(): void {
        $this->synchronized(function(): void {
            if($this->running) {
                $this->wait();
            }
        });
    }

    /**
     * @param string $query
     * @param string $types
     * @param array $params
     * @param callable|null $callable
     */
    public function executeSelect(string $query, string $types, array $params, ?callable $callable = null): void {
        $query = [
            "query" => $query,
            "type" => "select",
            "types" => $types,
            "params" => $params,
            "id" => ++$this->queryId
        ];
        CallableCache::$callables[$query["id"]] = $callable;
        $this->queries[] = igbinary_serialize($query);
        $this->synchronized(function(): void {
            $this->notify();
        });
    }

    /**
     * @param string $query
     * @param callable|null $callable
     */
    public function executeSelectQuery(string $query, ?callable $callable = null): void {
        $query = [
            "query" => $query,
            "type" => "selectQuery",
            "id" => ++$this->queryId
        ];
        CallableCache::$callables[$query["id"]] = $callable;
        $this->queries[] = igbinary_serialize($query);
        $this->synchronized(function(): void {
            $this->notify();
        });
    }

    /**
     * @param string $query
     * @param string $types
     * @param array $params
     */
    public function executeUpdate(string $query, string $types, array $params): void {
        $query = [
            "query" => $query,
            "type" => "update",
            "types" => $types,
            "params" => $params,
            "id" => ++$this->queryId
        ];
        $this->queries[] = igbinary_serialize($query);
        $this->synchronized(function(): void {
            $this->notify();
        });
    }

    public function checkResults(): void {
        while(($result = $this->results->shift()) !== null) {
            $result = igbinary_unserialize($result);
            $callable = CallableCache::$callables[$result["id"]];
            $callable($result["result"]);
        }
    }

    public function quit(): void {
        $this->running = false;
        parent::quit();
    }
}