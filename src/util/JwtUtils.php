<?php

namespace mgboot\util;

use DateTime;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Token;
use mgboot\Cast;
use Throwable;

final class JwtUtils
{
    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function getPublicKey(string $pemFilepath): Key
    {
        return Key\LocalFileReference::file($pemFilepath);
    }

    public static function getPrivateKey(string $pemFilepath): Key
    {
        return Key\LocalFileReference::file($pemFilepath);
    }

    public static function verify(Token $jwt, string $issuer): array
    {
        if (!$jwt->hasBeenIssuedBy($issuer)) {
            return [false, -1];
        }

        if ($jwt->isExpired(new DateTime())) {
            return [false, -2];
        }

        return [true, 0];
    }

    /**
     * @param Token|string $arg0
     * @param string $name
     * @param int $default
     * @return int
     */
    public static function intClaim($arg0, string $name, int $default = PHP_INT_MIN): int
    {
        return Cast::toInt(self::claim($arg0, $name), $default);
    }

    /**
     * @param Token|string $arg0
     * @param string $name
     * @param float $default
     * @return float
     */
    public static function floatClaim($arg0, string $name, float $default = PHP_FLOAT_MIN): float
    {
        return Cast::toFloat(self::claim($arg0, $name), $default);
    }

    /**
     * @param Token|string $arg0
     * @param string $name
     * @param bool $default
     * @return bool
     */
    public static function booleanClaim($arg0, string $name, bool $default = false): bool
    {
        return Cast::toBoolean(self::claim($arg0, $name), $default);
    }

    /**
     * @param Token|string $arg0
     * @param string $name
     * @param string $default
     * @return string
     */
    public static function stringClaim($arg0, string $name, string $default = ''): string
    {
        return Cast::toString(self::claim($arg0, $name), $default);
    }

    /**
     * @param Token|string $arg0
     * @param string $name
     * @return array
     */
    public static function arrayClaim($arg0, string $name): array
    {
        $ret = self::claim($arg0, $name);
        return is_array($ret) ? $ret : [];
    }

    /**
     * @param Token|string $arg0
     * @param string $name
     * @return mixed
     */
    private static function claim($arg0, string $name)
    {
        $jwt = null;

        if ($arg0 instanceof Token) {
            $jwt = $arg0;
        } else if (is_string($arg0) && $arg0 !== '') {
            try {
                $jwt = (new Parser())->parse($arg0);
            } catch (Throwable $ex) {
                $jwt = null;
            }
        }

        if (!($jwt instanceof Token)) {
            return null;
        }

        try {
            return $jwt->claims()->get($name);
        } catch (Throwable $ex) {
            return null;
        }
    }
}
