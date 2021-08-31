<?php

namespace mgboot\util;

use Throwable;

final class SerializeUtils
{
    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * @param mixed $arg0
     * @return string
     */
    public static function serialize($arg0): string
    {
        if (extension_loaded('igbinary')) {
            try {
                $contents = igbinary_serialize($arg0);
            } catch (Throwable $ex) {
                $contents = '';
            }

            return "igb:$contents";
        }

        try {
            $contents = serialize($arg0);
        } catch (Throwable $ex) {
            $contents = '';
        }

        return "php:$contents";
    }

    /**
     * @param string $contents
     * @return mixed
     */
    public static function unserialize(string $contents)
    {
        if (preg_match('/^igb:/', $contents)) {
            if (!extension_loaded('igbinary')) {
                return null;
            }

            $contents = StringUtils::substringAfter($contents, ':');

            try {
                return igbinary_unserialize($contents);
            } catch (Throwable $ex) {
                return null;
            }
        }

        $contents = StringUtils::substringAfter($contents, ':');

        try {
            return unserialize($contents);
        } catch (Throwable $ex) {
            return null;
        }
    }
}
