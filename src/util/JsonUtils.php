<?php

namespace mgboot\util;

use stdClass;

final class JsonUtils
{
    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * @param mixed $arg0
     * @return array|stdClass
     */
    public static function mapFrom($arg0)
    {
        if (!is_string($arg0) || empty($arg0)) {
            return new stdClass();
        }

        $data = json_decode($arg0, true);
        return ArrayUtils::isAssocArray($data) ? $data : new stdClass();
    }

    /**
     * @param mixed $arg0
     * @return array
     */
    public static function arrayFrom($arg0): array
    {
        if (!is_string($arg0) || empty($arg0)) {
            return [];
        }

        $data = json_decode($arg0, true);
        return ArrayUtils::isList($data) ? $data : [];
    }

    /**
     * @param mixed $arg0
     * @return string
     */
    public static function toJson($arg0): string
    {
        $json = json_encode($arg0, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        return is_string($json) ? $json : '';
    }

    /**
     * @param mixed $arg0
     * @return string
     */
    public static function toJsonObjectString($arg0): string
    {
        $json = json_encode($arg0, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

        if (!is_string($json) || !StringUtils::startsWith($json, '{') || !StringUtils::endsWith($json, '}')) {
            return '{}';
        }

        return $json;
    }

    /**
     * @param mixed $arg0
     * @return string
     */
    public static function toJsonArrayString($arg0): string
    {
        $json = json_encode($arg0, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

        if (!is_string($json) || !StringUtils::startsWith($json, '[') || !StringUtils::endsWith($json, ']')) {
            return '[]';
        }

        return $json;
    }
}
