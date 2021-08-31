<?php

namespace mgboot\util;

final class TokenizeUtils
{
    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function getQualifiedClassName(array $tokens): string
    {
        $namespace = self::getNamespace($tokens);
        $className = self::getSimpleClassName($tokens);

        if (empty($className)) {
            return '';
        }

        if (empty($namespace)) {
            return StringUtils::ensureLeft($className, "\\");
        }

        return StringUtils::ensureLeft($namespace, "\\") . StringUtils::ensureLeft($className, "\\");
    }

    public static function getNamespace(array $tokens): string
    {
        $n = -1;
        $idx = -1;
        $sb = [];

        foreach ($tokens as $i => $token) {
            if (!is_array($token)) {
                continue;
            }

            if ($token[0] === T_NAMESPACE) {
                $n = $token[2];
                $idx = $i;
                break;
            }
        }

        if ($n < 0) {
            return '';
        }

        $cnt = count($tokens);

        for ($i = $idx + 1; $i < $cnt; $i++) {
            $token = $tokens[$i];

            if (!is_array($token)) {
                continue;
            }

            if ($token[2] > $n) {
                break;
            }

            if ($token[0] === T_STRING || $token[0] === T_NS_SEPARATOR) {
                $sb[] = $token[1];
            }
        }

        return empty($sb) ? '' : implode('', $sb);
    }

    public static function getUsedClasses(array $tokens): array
    {
        $lineNumbers = [];
        $classes = [];

        foreach ($tokens as $token) {
            if (!is_array($token)) {
                continue;
            }

            if ($token[0] !== T_USE) {
                continue;
            }

            $lineNumbers[] = (int) $token[2];
        }

        foreach ($lineNumbers as $lineNumber) {
            $sb = [];

            foreach ($tokens as $token) {
                if (!is_array($token) || $token[2] !== $lineNumber) {
                    continue;
                }

                if (!in_array($token[0], [T_STRING, T_NS_SEPARATOR])) {
                    continue;
                }

                $sb[] = $token[1];
            }

            if (empty($sb)) {
                continue;
            }

            $classes[] = StringUtils::ensureLeft(implode('', $sb), "\\");
        }

        return $classes;
    }

    private static function getSimpleClassName(array $tokens): string
    {
        $n = -1;
        $idx = -1;

        foreach ($tokens as $i => $token) {
            if (!is_array($token)) {
                continue;
            }

            if ($token[0] === T_CLASS) {
                $n = $token[2];
                $idx = $i;
                break;
            }
        }

        if ($n < 0) {
            return '';
        }

        $cnt = count($tokens);
        $className = '';

        for ($i = $idx + 1; $i < $cnt; $i++) {
            $token = $tokens[$i];

            if (!is_array($token)) {
                continue;
            }

            if ($token[2] === $n && $token[0] === T_STRING) {
                $className = $token[1];
                break;
            }
        }

        return $className;
    }
}
