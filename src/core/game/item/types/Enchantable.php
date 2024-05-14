<?php

declare(strict_types = 1);

namespace core\game\item\types;

interface Enchantable {

    /**
     * @return int
     */
    public function getMaxLevel(): int;
}