<?php

declare(strict_types = 1);

namespace libs\utils;

use DateTime;
use pocketmine\entity\Skin;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use TypeError;
use function imagecreatefrompng;

class Utils {

    const N = 'N';

    const NE = '/';

    const E = 'E';

    const SE = '\\';

    const S = 'S';

    const SW = '/';

    const W = 'W';

    const NW = '\\';

    const HEX_SYMBOL = "e29688";

    /**
     * @param int $degrees
     *
     * @return string|null
     */
    public static function getCompassPointForDirection(int $degrees): ?string {
        $degrees = ($degrees - 180) % 360;
        if($degrees < 0) {
            $degrees += 360;
        }
        if(0 <= $degrees and $degrees < 22.5) {
            return self::N;
        }
        if(22.5 <= $degrees and $degrees < 67.5) {
            return self::NE;
        }
        if(67.5 <= $degrees and $degrees < 112.5) {
            return self::E;
        }
        if(112.5 <= $degrees and $degrees < 157.5) {
            return self::SE;
        }
        if(157.5 <= $degrees and $degrees < 202.5) {
            return self::S;
        }
        if(202.5 <= $degrees and $degrees < 247.5) {
            return self::SW;
        }
        if(247.5 <= $degrees and $degrees < 292.5) {
            return self::W;
        }
        if(292.5 <= $degrees and $degrees < 337.5) {
            return self::NW;
        }
        if(337.5 <= $degrees and $degrees < 360.0) {
            return self::N;
        }
        return null;
    }

    /**
     * @param $min
     * @param $max
     * @param int $decimals
     *
     * @return float|int
     */
    public static function getRandomFloat($min, $max, int $decimals = 0) {
        $scale = pow(10, $decimals);
        return mt_rand((int)($min * $scale), (int)($max * $scale)) / $scale;
    }

    /**
     * @param string $time
     *
     * @return int|null
     */
    public static function stringToTime(string $time): ?int {
        $array = str_split($time);
        if(count($array) % 2 !== 0) {
            return null;
        }
        $newTime = 0;
        for($i = 0; $i < count($array); $i++) {
            if($i % 2 === 0) {
                if(!is_numeric($array[$i])) {
                    return null;
                }
            }
            switch(strtolower($array[$i])) {
                case "s":
                    $newTime += (int)$array[$i - 1];
                    break;
                case "h":
                    $newTime += ((int)$array[$i - 1] * 3600);
                    break;
                case "d":
                    $newTime += ((int)$array[$i - 1] * 86400);
                    break;
                case "w":
                    $newTime += ((int)$array[$i - 1] * 604800);
                    break;
                case "m":
                    $newTime += ((int)$array[$i - 1] * 2419200);
                    break;
            }
        }
        return $newTime;
    }

    /**
     * @param string $text
     * @param int $length
     * @param int $time
     *
     * @return string
     */
    public static function scrollText(string $text, int $length, int $time = null): string {
        if($time === null) {
            $time = time();
        }
        $start = ($time % strlen($text));
        $newText = substr($text, -$start, $length);
        if($start < $length) {
            $newText .= substr($text, 0, $length - strlen($newText));
        }
        return $newText;
    }

    /**
     * @param $socket
     * @param $data
     *
     * @return string
     */
    public static function sendDataToSocket($socket, $data): string {
        fwrite($socket, $data);
        return fread($socket, 1024);
    }

    /**
     * @param float $deg
     *
     * @return string
     */
    public static function getCompassDirection(float $deg): string {
        $deg %= 360;
        if($deg < 0) {
            $deg += 360;
        }
        if(22.5 <= $deg and $deg < 67.5) {
            return "NW";
        }
        elseif(67.5 <= $deg and $deg < 112.5) {
            return "N";
        }
        elseif(112.5 <= $deg and $deg < 157.5) {
            return "NE";
        }
        elseif(157.5 <= $deg and $deg < 202.5) {
            return "E";
        }
        elseif(202.5 <= $deg and $deg < 247.5) {
            return "SW";
        }
        elseif(247.5 <= $deg and $deg < 292.5) {
            return "S";
        }
        elseif(292.5 <= $deg and $deg < 337.5) {
            return "SW";
        }
        else {
            return "W";
        }
    }

    /**
     * @return string
     */
    public static function getMapBlock(): string {
        return hex2bin(self::HEX_SYMBOL);
    }

    /**
     * @param int $degrees
     * @param string $colorActive
     * @param string $colorDefault
     *
     * @return array
     */
    public static function getASCIICompass(int $degrees, string $colorActive, string $colorDefault): array {
        $ret = [];
        $point = self::getCompassPointForDirection($degrees);
        $row = "";
        $row .= ($point === self::NW ? $colorActive : $colorDefault) . self::NW;
        $row .= ($point === self::N ? $colorActive : $colorDefault) . self::N;
        $row .= ($point === self::NE ? $colorActive : $colorDefault) . self::NE;
        $ret[] = $row;
        $row = "";
        $row .= ($point === self::W ? $colorActive : $colorDefault) . self::W;
        $row .= $colorDefault . "+";
        $row .= ($point === self::E ? $colorActive : $colorDefault) . self::E;
        $ret[] = $row;
        $row = "";
        $row .= ($point === self::SW ? $colorActive : $colorDefault) . self::SW;
        $row .= ($point === self::S ? $colorActive : $colorDefault) . self::S;
        $row .= ($point === self::SE ? $colorActive : $colorDefault) . self::SE;
        $ret[] = $row;
        return $ret;
    }

    /**
     * Check if classes in an array are a block of class
     *
     * @param array $array
     * @param string $class
     *
     * @return bool
     *
     * @throws TypeError
     */
    public static function validateObjectArray(array $array, string $class): bool {
        foreach($array as $key => $item) {
            if(!($item instanceof $class)) {
                throw new TypeError("Element \"$key\" is not an instance of $class");
            }
        }
        return true;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public static function getSkinDataFromPNG(string $path): string {
        $image = imagecreatefrompng($path);
        $data = "";
        for($y = 0, $height = imagesy($image); $y < $height; $y++) {
            for($x = 0, $width = imagesx($image); $x < $width; $x++) {
                $color = imagecolorat($image, $x, $y);
                $data .= chr(($color >> 16) & 0xFF)
                    . chr(($color >> 8) & 0xFF)
                    . chr($color & 0xFF)
                    . chr((127 - (($color >> 24) & 0x7F)) * 2);
            }
        }
        return $data;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public static function getSkinDataFromJSON(string $path): string {
        $config = new Config($path);
        return Utils::getSkinDataFromArray($config->get("skinData"));
    }

    /**
     * @param array $data
     *
     * @return string
     */
    public static function getSkinDataFromArray(array $data): string {
        $skinData = "";
        foreach($data as $datum) {
            $skinData .= chr($datum);
        }
        return $skinData;
    }

    /**
     * @param Skin $skin
     *
     * @return array
     */
    public static function createArrayFromSkin(Skin $skin): array {
        $save = [];
        $bytes = $skin->getSkinData();
        for($pos = 0; $pos < strlen($bytes); $pos++) {
            $byte = substr($bytes, $pos);
            $save[] = ord($byte);
        }
        return $save;
    }

    /**
     * @param string $skinData
     * @param string $skinId
     * @param string $capeData
     * @param string $geometryName
     * @param string $geomentryData
     *
     * @return Skin
     */
    public static function createSkin(string $skinData, string $skinId = "Standard_Custom", string $capeData = "", string $geometryName = "geometry.humanoid.custom", string $geomentryData = "") {
        return new Skin($skinId, $skinData, $capeData, $geometryName, $geomentryData);
    }

    /**
     * @param string $data
     *
     * @return array
     */
    public static function getRGBArrayFromSkinData(string $data): array {
        $skinData = [];
        for($pos = 0; $pos < strlen($data); $pos++) {
            $byte = substr($data, $pos);
            $skinData[] = ord($byte);
        }
        return $skinData;
    }

    /**
     * @param string $data
     *
     * @return false|\GdImage|resource
     * @throws UtilsException
     */
    public static function createImageFromSkinData(string $data) {
        switch(strlen($data)) {
            case 64 * 32 * 4:
                $w = 64;
                $h = 32;
                break;
            case 64 * 64 * 4:
                $w = $h = 64;
                break;
            case 128 * 128 * 4:
                $w = $h = 128;
                break;
            default:
                throw new UtilsException("Invalid skin!");
        }
        $skinData = [];
        for($pos = 0; $pos < strlen($data); $pos++) {
            $byte = substr($data, $pos);
            $skinData[] = ord($byte);
        }
        $img = imagecreatetruecolor($w, $h);
        imagesavealpha($img, true);
        imagealphablending($img, false);
        for($y = 0; $y < $h; $y++) {
            for($x = 0; $x < $w; $x++) {
                $r = array_shift($skinData);
                $g = array_shift($skinData);
                $b = array_shift($skinData);
                $alpha = min(127, abs((255 - array_shift($skinData))) / 2);
                if($alpha === 127) {
                    $color = imagecolortransparent($img);
                }
                else {
                    $color = imagecolorallocatealpha($img, $r, $g, $b, (int)$alpha);
                }
                imagesetpixel($img, $x, $y, $color);
            }
        }
        return $img;
    }

    /**
     * @param \GdImage|resource
     *
     * @return false|\GdImage|resource
     */
    public static function getHeadSkinFromImage($im) {
        $size = 64;
        $av = imagecreatetruecolor($size, $size);
        imagecopyresized($av, $im, 0, 0, 8, 8, $size, $size, 8, 8);
        imagecolortransparent($im, imagecolorat($im, 63, 0));
        imagecopyresized($av, $im, 0, 0, 8 + 32, 8, $size, $size, 8, 8);
        return $av;
    }

    /**
     * @param int $n
     * @param int $precision
     *
     * @return string
     */
    public static function shrinkNumber(int $n, int $precision = 1): string {
        if($n < 900) {
            $n_format = number_format($n, $precision);
            $suffix = '';
        }
        else {
            if($n < 900000) {
                $n_format = number_format($n / 1000, $precision);
                $suffix = 'K';
            }
            else {
                if($n < 900000000) {
                    $n_format = number_format($n / 1000000, $precision);
                    $suffix = 'M';
                }
                else {
                    if($n < 900000000000) {
                        $n_format = number_format($n / 1000000000, $precision);
                        $suffix = 'B';
                    }
                    else {
                        $n_format = number_format($n / 1000000000000, $precision);
                        $suffix = 'T';
                    }
                }
            }
        }
        if($precision > 0) {
            $dotzero = '.' . str_repeat('0', $precision);
            $n_format = str_replace($dotzero, '', $n_format);
        }
        return $n_format . $suffix;
    }

    /**
     * @param int $seconds
     *
     * @return string
     */
    public static function secondsToTime(int $seconds): string {
        $dtF = new DateTime('@0');
        $dtT = new DateTime("@$seconds");
        $hours = floor($seconds / 3600);
        if($hours >= 24) {
            return $dtF->diff($dtT)->format('%ad %hh %im %ss');
        }
        if($hours >= 1) {
            return $dtF->diff($dtT)->format('%hh %im %ss');
        }
        return $dtF->diff($dtT)->format('%im %ss');
    }

    /**
     * @param string $color
     * @param string $text
     * @param string $endingColor
     *
     * @return string
     */
    public static function createPrefix(string $color, string $text, string $endingColor = TextFormat::GRAY): string {
        return TextFormat::DARK_GRAY . "[" . TextFormat::BOLD . $color . $text . TextFormat::RESET . TextFormat::DARK_GRAY . "] " . $endingColor;
    }

    /**
     * @param string $shorten
     *
     * @return int|null
     */
    public static function shortenToNumber(string $shorten): ?int {
        switch(strtolower(substr($shorten, -1))) {
            case "k":
                $shorten = substr($shorten, 0, -1);
                if(!is_numeric($shorten)) {
                    return null;
                }
                $shorten = (float)$shorten;
                return (int)$shorten * 1000;
                break;
            case "m":
                $shorten = substr($shorten, 0, -1);
                if(!is_numeric($shorten)) {
                    return null;
                }
                $shorten = (float)$shorten;
                return (int)$shorten * 1000000;
                break;
            case "b":
                $shorten = substr($shorten, 0, -1);
                if(!is_numeric($shorten)) {
                    return null;
                }
                $shorten = (float)$shorten;
                return (int)$shorten * 1000000000;
                break;
            case "t":
                $shorten = substr($shorten, 0, -1);
                if(!is_numeric($shorten)) {
                    return null;
                }
                $shorten = (float)$shorten;
                return (int)$shorten * 1000000000000;
                break;
            default:
                return null;
                break;
        }
    }

    /**
     * @param string $text
     * @param int $length
     *
     * @return string
     */
    public static function centerAlignText(string $text, int $length): string {
        $textLength = strlen(TextFormat::clean($text));
        $length -= $textLength;
        $times = (int)floor($length / 2);
        $times = $times > 0 ? $times : 1;
        return str_repeat(" ", $times) . $text . str_repeat(" ", $times);
    }

    /**
     * @param string $message
     * @param string $server
     * @param string $url
     */
    public static function sendDiscordWebhook(string $message, string $server, string $url): void {
//        $curl = curl_init();
//        $message = "[$server] " . date("[n/j/Y][G:i:s] ", time()). $message;
//        curl_setopt($curl, CURLOPT_URL, $url);
//        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(["content" => $message, "username" => null]));
//        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
//        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
//        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
//        curl_exec($curl);
    }
}