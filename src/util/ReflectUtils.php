<?php

namespace mgboot\util;

use Doctrine\Common\Annotations\AnnotationReader;
use mgboot\Cast;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Throwable;

final class ReflectUtils
{
    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function getClassAnnotation(ReflectionClass $refClazz, string $annoClass): ?object
    {
        $annoClass = StringUtils::ensureLeft($annoClass, "\\");

        try {
            $reader = new AnnotationReader();
            $annotations = $reader->getClassAnnotations($refClazz);
        } catch (Throwable $ex) {
            $annotations = [];
        }

        foreach ($annotations as $anno) {
            if (StringUtils::ensureLeft(get_class($anno), "\\") === $annoClass) {
                return $anno;
            }
        }

        return null;
    }

    public static function getMethodAnnotation(ReflectionMethod $method, string $annoClass): ?object
    {
        $annoClass = StringUtils::ensureLeft($annoClass, "\\");

        try {
            $reader = new AnnotationReader();
            $annotations = $reader->getMethodAnnotations($method);
        } catch (Throwable $ex) {
            $annotations = [];
        }

        foreach ($annotations as $anno) {
            if (StringUtils::ensureLeft(get_class($anno), "\\") === $annoClass) {
                return $anno;
            }
        }

        return null;
    }

    /**
     * @param ReflectionProperty $property
     * @param ReflectionMethod[] $methods
     * @return ReflectionMethod|null
     */
    public static function getGetter(ReflectionProperty $property, array $methods = []): ?ReflectionMethod
    {
        $fieldName = strtolower($property->getName());

        if (empty($methods)) {
            try {
                $methods = $property->getDeclaringClass()->getMethods(ReflectionMethod::IS_PUBLIC);
            } catch (Throwable $ex) {
                $methods = [];
            }
        }

        if (empty($methods)) {
            return null;
        }

        $getter = null;

        foreach ($methods as $method) {
            if (strtolower($method->getName()) === "get$fieldName") {
                $getter = $method;
                break;
            }

            $s1 = StringUtils::ensureLeft($fieldName, 'is');
            $s2 = StringUtils::ensureLeft(strtolower($method->getName()), 'is');

            if ($s1 === $s2) {
                $getter = $method;
                break;
            }
        }

        return $getter;
    }

    /**
     * @param ReflectionProperty $property
     * @param ReflectionMethod[] $methods
     * @return ReflectionMethod|null
     */
    public static function getSetter(ReflectionProperty $property, array $methods = []): ?ReflectionMethod
    {
        $fieldName = strtolower($property->getName());

        if (empty($methods)) {
            try {
                $methods = $property->getDeclaringClass()->getMethods(ReflectionMethod::IS_PUBLIC);
            } catch (Throwable $ex) {
                $methods = [];
            }
        }

        if (empty($methods)) {
            return null;
        }

        $setter = null;

        foreach ($methods as $method) {
            try {
                $args = $method->getParameters();
            } catch (Throwable $ex) {
                $args = [];
            }

            if (count($args) !== 1) {
                continue;
            }

            if (strtolower($method->getName()) === "set$fieldName") {
                $setter = $method;
                break;
            }
        }

        return $setter;
    }

    public static function getPropertyAnnotation(ReflectionProperty $property, string $annoClass): ?object
    {
        $annoClass = StringUtils::ensureLeft($annoClass, "\\");

        try {
            $reader = new AnnotationReader();
            $annotations = $reader->getPropertyAnnotations($property);
        } catch (Throwable $ex) {
            $annotations = [];
        }

        foreach ($annotations as $anno) {
            if (StringUtils::ensureLeft(get_class($anno), "\\") === $annoClass) {
                return $anno;
            }
        }

        return null;
    }

    public static function getMapKeyByProperty(ReflectionProperty $property, array $propertyNameToMapKey = []): string
    {
        try {
            $reader = new AnnotationReader();
            $annotations = $reader->getPropertyAnnotations($property);
        } catch (Throwable $ex) {
            $annotations = [];
        }

        $annoMapKey = null;

        foreach ($annotations as $anno) {
            if (preg_match('/MapKey$/', get_class($anno))) {
                $annoMapKey = $anno;
                break;
            }
        }

        if (is_object($annoMapKey) && method_exists($annoMapKey, 'getValue')) {
            $mapKey = Cast::toString($annoMapKey->getValue());

            if ($mapKey !== '') {
                return $mapKey;
            }
        }

        $fieldName = $property->getName();

        if (!is_string($fieldName) || $fieldName === '') {
            return '';
        }

        $mapKey = Cast::toString($propertyNameToMapKey[$fieldName]);
        return $mapKey === '' ? $fieldName : $mapKey;
    }

    /**
     * @param array $map1
     * @param ReflectionProperty $property
     * @param array $propertyNameToMapKey
     * @return mixed
     */
    public static function getMapValueByProperty(array $map1, ReflectionProperty $property, array $propertyNameToMapKey = [])
    {
        if (empty($map1)) {
            return null;
        }

        $mapKey = self::getMapKeyByProperty($property, $propertyNameToMapKey);
        $mapKey = strtolower(strtr($mapKey, ['-' => '', '_' => '']));

        if (empty($mapKey)) {
            return null;
        }

        foreach ($map1 as $key => $val) {
            if (!is_string($key) || $key === '') {
                continue;
            }

            $key = strtolower(strtr($key, ['-' => '', '_' => '']));

            if ($key === $mapKey) {
                return $val;
            }

            if (StringUtils::ensureLeft($key, 'is') === StringUtils::ensureLeft($mapKey, 'is')) {
                return $val;
            }
        }

        return null;
    }
}
