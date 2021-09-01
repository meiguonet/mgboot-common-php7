<?php

namespace mgboot\util;

use mgboot\Cast;
use mgboot\constant\Regexp;
use mgboot\constant\RequestParamSecurityMode as SecurityMode;
use mgboot\HtmlPurifier;

final class ArrayUtils
{
    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function first(array $arr, callable $callback)
    {
        if (empty($arr) || !self::isList($arr)) {
            return null;
        }

        return collect($arr)->first($callback);
    }

    public static function camelCaseKeys(array $arr): array
    {
        if (empty($arr)) {
            return [];
        }

        foreach ($arr as $key => $value) {
            if (!is_string($key)) {
                unset($arr[$key]);
                continue;
            }

            $newKey = $key;
            $needUcwords = false;

            if (strpos($newKey, '-') !== false) {
                $newKey = str_replace('-', ' ', $newKey);
                $needUcwords = true;
            } else if (strpos($newKey, '_') !== false) {
                $newKey = str_replace('_', ' ', $newKey);
                $needUcwords = true;
            }

            if ($needUcwords) {
                $newKey = str_replace(' ', '', ucwords($newKey));
            }

            if ($newKey === $key) {
                continue;
            }

            $arr[$newKey] = $value;
            unset($key);
        }

        return $arr;
    }

    /**
     * @param array $arr
     * @param string[]|string $keys
     * @return array
     */
    public static function removeKeys(array $arr, $keys): array
    {
        if (is_string($keys) && $keys !== '') {
            $keys = preg_split('/[\x20\t]*,[\x20\t]*/', $keys);
        }

        if (!is_array($keys) || empty($keys)) {
            return $arr;
        }

        if (!self::isAssocArray($arr)) {
            foreach ($arr as $key => $val) {
                $arr[$key] = self::removeKeys($val, $keys);
            }

            return $arr;
        }

        foreach ($arr as $key => $val) {
            if (!is_string($key) || !in_array($key, $keys)) {
                continue;
            }

            unset($arr[$key]);
        }

        return $arr;
    }

    public static function removeEmptyFields(array $arr): array
    {
        if (empty($arr)) {
            return [];
        }

        foreach ($arr as $key => $value) {
            if ($value === null) {
                unset($arr[$key]);
                continue;
            }

            if (!is_string($value)) {
                continue;
            }

            if ($value === '') {
                unset($arr[$key]);
            }
        }

        return $arr;
    }

    /**
     * @param mixed $arg0
     * @return bool
     */
    public static function isAssocArray($arg0): bool
    {
        if (!is_array($arg0) || empty($arg0)) {
            return false;
        }

        $keys = array_keys($arg0);

        foreach ($keys as $key) {
            if (!is_string($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param mixed $arg0
     * @return bool
     */
    public static function isList($arg0): bool
    {
        if (!is_array($arg0) || empty($arg0)) {
            return false;
        }

        $keys = array_keys($arg0);
        $n1 = count($keys);

        for ($i = 0; $i < $n1; $i++) {
            if (!is_int($keys[$i]) || $keys[$i] < 0) {
                return false;
            }

            if ($i > 0 && $keys[$i] - 1 !== $keys[$i - 1]) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param mixed $arg0
     * @return bool
     */
    public static function isIntArray($arg0): bool
    {
        if (!self::isList($arg0)) {
            return false;
        }

        foreach ($arg0 as $val) {
            if (!is_int($val)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param mixed $arg0
     * @return bool
     */
    public static function isStringArray($arg0): bool
    {
        if (!self::isList($arg0)) {
            return false;
        }

        foreach ($arg0 as $val) {
            if (!is_string($val)) {
                return false;
            }
        }

        return true;
    }

    public static function toxml(array $arr, array $cdataKeys = []): string
    {
        $sb = [str_replace('/', '', '<xml/>')];

        foreach ($arr as $key => $val) {
            if (!is_string($key) || $key === '') {
                continue;
            }

            if (is_int($val) || is_numeric($val) || !in_array($key, $cdataKeys)) {
                $sb[] = "<$key>$val</$key>";
            } else {
                $sb[] = "<$key><![CDATA[$val]]></$key>";
            }
        }

        $sb[] = '</xml>';
        return implode('', $sb);
    }

    /**
     * @param array $arr
     * @param string[]|string $rules
     * @return array
     */
    public static function requestParams(array $arr, $rules): array
    {
        if (is_string($rules) && $rules !== '') {
            $rules = preg_split('/[\x20\t]*,[\x20\t]*/', $rules);
        }
        
        if (!self::isStringArray($rules) || empty($rules)) {
            return $arr;
        }

        $map1 = [];

        foreach ($rules as $rule) {
            $type = 1;
            $securityMode = SecurityMode::STRIP_TAGS;
            $defaultValue = null;

            if (StringUtils::startsWith($rule, 'i:')) {
                $type = 2;
                $rule = StringUtils::substringAfter($rule, ':');
            } else if (StringUtils::startsWith($rule, 'd:')) {
                $type = 3;
                $rule = StringUtils::substringAfter($rule, ':');
            } else if (StringUtils::startsWith($rule, 's:')) {
                $rule = StringUtils::substringAfter($rule, ':');
            } else if (StringUtils::startsWith($rule, 'a:')) {
                $type = 4;
                $rule = StringUtils::substringAfter($rule, ':');
            }

            $paramName = '';

            switch ($type) {
                case 1:
                    if (StringUtils::endsWith($rule, ':0')) {
                        $paramName = StringUtils::substringBeforeLast($rule, ':');
                        $securityMode = SecurityMode::NONE;
                    } else if (StringUtils::endsWith($rule, ':1')) {
                        $paramName = StringUtils::substringBeforeLast($rule, ':');
                        $securityMode = SecurityMode::HTML_PURIFY;
                    } else if (StringUtils::endsWith($rule, ':2')) {
                        $paramName = StringUtils::substringBeforeLast($rule, ':');
                    } else {
                        $paramName = $rule;
                    }

                    break;
                case 2:
                    if (strpos($rule, ':') !== false) {
                        $defaultValue = StringUtils::substringAfterLast($rule, ':');
                        $defaultValue = StringUtils::isInt($defaultValue) ? (int) $defaultValue : PHP_INT_MIN;
                        $paramName = StringUtils::substringBeforeLast($rule, ':');
                    } else {
                        $paramName = $rule;
                    }

                    $defaultValue = is_int($defaultValue) ? $defaultValue : PHP_INT_MIN;
                    break;
                case 3:
                    if (strpos($rule, ':') !== false) {
                        $defaultValue = StringUtils::substringAfterLast($rule, ':');
                        $defaultValue = StringUtils::isFloat($defaultValue) ? bcadd($defaultValue, 0, 2) : null;
                        $paramName = StringUtils::substringBeforeLast($rule, ':');
                    } else {
                        $paramName = $rule;
                    }

                    $defaultValue = is_string($defaultValue) ? $defaultValue : '0.00';
                    break;
            }

            if (empty($paramName)) {
                continue;
            }

            switch ($type) {
                case 2:
                    $value = Cast::toInt($arr[$paramName], is_int($defaultValue) ? $defaultValue : PHP_INT_MIN);
                    break;
                case 3:
                    $value = Cast::toString($arr[$paramName]);
                    $value = StringUtils::isFloat($value) ? bcadd($value, 0, 2) : $defaultValue;
                    break;
                case 4:
                    $value = json_decode(Cast::toString($arr[$paramName]), true);
                    $value = is_array($value) ? $value : [];
                    break;
                default:
                    $value = self::getStringWithSecurityMode($arr, $paramName, $securityMode);
                    break;
            }

            $map1[$paramName] = $value;
        }

        return $map1;
    }

    /**
     * @param array $arr
     * @param string[]|string $keys
     * @return array
     */
    public static function copyFields(array $arr, $keys): array
    {
        if (is_string($keys) && $keys !== '') {
            $keys = preg_split(Regexp::COMMA_SEP, $keys);
        }

        if (empty($keys) || !self::isStringArray($keys)) {
            return [];
        }

        $map1 = [];

        foreach ($arr as $key => $val) {
            if (!in_array($key, $keys)) {
                continue;
            }

            $map1[$key] = $val;
        }

        return $map1;
    }

    /**
     * @param object $obj
     * @param array $propertyNameToMapKey
     * @param bool $ignoreNull
     * @return array
     */
    public static function fromBean(object $obj, array $propertyNameToMapKey = [], bool $ignoreNull = false): array
    {
        if (method_exists($obj, 'toMap')) {
            return $obj->toMap($propertyNameToMapKey, $ignoreNull);
        }

        return [];
    }

    private static function getStringWithSecurityMode(
        array $arr,
        string $key,
        int $securityMode = SecurityMode::STRIP_TAGS
    ): string
    {
        $value = $arr[$key];

        if (is_int($value) || is_float($value)) {
            return "$value";
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (!is_string($value)) {
            return '';
        }

        if ($value === '') {
            return $value;
        }

        switch ($securityMode) {
            case SecurityMode::HTML_PURIFY:
                return HtmlPurifier::purify($value);
            case SecurityMode::STRIP_TAGS:
                return strip_tags($value);
            default:
                return $value;
        }
    }
}
