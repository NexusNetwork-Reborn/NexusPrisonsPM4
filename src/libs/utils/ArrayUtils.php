<?php

namespace libs\utils;

class ArrayUtils {

    /**
     * @param array $array
     *
     * @return string
     * @throws UtilsException
     */
    public static function encodeArray(array $array): string {
        $parts = [];
        foreach($array as $key => $value) {
            if(is_array($value)) {
                throw new UtilsException("Unable to encode a multi-dimensional array.");
            }
            $parts[] = "$key:$value";
        }
        return implode(",", $parts);
    }

    /**
     * @param string $encryption
     *
     * @return array
     */
    public static function decodeArray(string $encryption): array {
        if(empty($encryption)) {
            return [];
        }
        $array = [];
        foreach(explode(",", $encryption) as $section) {
            $parts = explode(":", $section);
            $array[$parts[0]] = is_numeric($parts[1]) ? (int)$parts[1] : (string)$parts[1];
        }
        return $array;
    }

    /**
     * @param bool[] $array
     *
     * @return string
     * @throws UtilsException
     */
    public static function encodeBoolArray(array $array): string {
        $parts = [];
        foreach($array as $key => $value) {
            if(is_array($value)) {
                throw new UtilsException("Unable to encode a multi-dimensional array.");
            }
            $value = (int)$value;
            $parts[] = "$key:$value";
        }
        return implode(",", $parts);
    }

    /**
     * @param string $encryption
     *
     * @return array
     */
    public static function decodeBoolArray(string $encryption): array {
        if(empty($encryption)) {
            return [];
        }
        $array = [];
        foreach(explode(",", $encryption) as $section) {
            $parts = explode(":", $section);
            if(!isset($parts[0]) or !isset($parts[1])) {
                continue;
            }
            $array[$parts[0]] = (bool)$parts[1];
        }
        return $array;
    }

    /**
     * @param array $array
     *
     * @return string
     * @throws UtilsException
     */
    public static function encodeMultiBoolArray(array $array): string {
        $parts = [];
        foreach($array as $key => $set) {
            if(!is_array($set)) {
                throw new UtilsException("Expected a multidimensional array, got a regular array.");
            }
            $sets = [];
            foreach($set as $id => $value) {
                if(!is_bool($value)) {
                    throw new UtilsException("Expected a bool value, got something else.");
                }
                $value = (int)$value;
                $sets[] = "$id:$value";
            }
            $parts[] = $key . "+" . implode("&", $sets);
        }
        return implode(",", $parts);
    }

    /**
     * @param string $encryption
     *
     * @return array
     */
    public static function decodeMultiBoolArray(string $encryption): array {
        if(empty($encryption)) {
            return [];
        }
        $array = [];
        foreach(explode(",", $encryption) as $set) {
            $sections = explode("+", $set);
            if(!isset($sections[0]) or !isset($sections[1])) {
                continue;
            }
            $key = $sections[0];
            $sets = explode("&", $sections[1]);
            foreach($sets as $data) {
                $parts = explode(":", $data);
                if(!isset($parts[0]) or !isset($parts[1])) {
                    continue;
                }
                $array[$key][$parts[0]] = (bool)$parts[1];
            }
        }
        return $array;
    }
}