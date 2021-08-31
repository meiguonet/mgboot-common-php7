<?php

namespace mgboot\util;

use Illuminate\Support\Collection;

final class CollectionUtils
{
    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * @param mixed $arg0
     * @return Collection
     */
    public static function toCollection($arg0): Collection
    {
        if (is_array($arg0)) {
            return collect($arg0);
        }

        if ($arg0 instanceof Collection) {
            return $arg0;
        }

        return collect([]);
    }

    public static function object2array(Collection $list): Collection
    {
        return $list->map(function ($item) {
            return is_array($item) ? $item : get_object_vars($item);
        });
    }

    /**
     * @param Collection $list
     * @param string[]|string $keys
     * @return Collection
     */
    public static function removeKeys(Collection $list, $keys): Collection
    {
        return $list->map(function ($item) use ($keys) {
            return ArrayUtils::removeKeys($item, $keys);
        });
    }
}
