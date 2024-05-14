<?php

namespace libs\utils;

abstract class Task extends \pocketmine\scheduler\Task {

    public function cancel(): void {
        $handler = $this->getHandler();
        if($handler !== null) {
            $handler->cancel();
        }
    }
}