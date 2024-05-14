<?php
declare(strict_types=1);

namespace core\translation;

use pocketmine\utils\TextFormat;

class Translation implements Messages {

    /**
     * @param string $identifier
     * @param array $args
     * @param string $color
     *
     * @return string
     * @throws TranslationException
     */
    public static function getMessage(string $identifier, array $args = [], string $color = TextFormat::GRAY): string {
        if(!isset(self::MESSAGE[$identifier])) {
            throw new TranslationException("Invalid identifier: $identifier");
        }
        $message = self::MESSAGE[$identifier];
        foreach($args as $arg => $value) {
            $message = str_replace("{" . $arg . "}", $value . TextFormat::RESET . TextFormat::GRAY, $message);
        }
        return (string)str_replace(TextFormat::GRAY, $color, $message);
    }
}