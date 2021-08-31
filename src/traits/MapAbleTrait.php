<?php

namespace mgboot\traits;

use mgboot\util\ReflectUtils;
use mgboot\util\StringUtils;
use ReflectionClass;
use ReflectionProperty;
use Throwable;

trait MapAbleTrait
{
    public function fromMap(array $data): void
    {
        foreach ($data as $key => $value) {
            if (!is_string($key) || $key === '') {
                unset($data[$key]);
                continue;
            }

            $propertyName = $key;
            $needUcwords = false;

            if (strpos($propertyName, '-') !== false) {
                $propertyName = str_replace('-', ' ', $propertyName);
                $needUcwords = true;
            } else if (strpos($propertyName, '_') !== false) {
                $propertyName = str_replace('_', ' ', $propertyName);
                $needUcwords = true;
            }

            if ($needUcwords) {
                $propertyName = ucwords($propertyName);
                $propertyName = str_replace(' ', '', $propertyName);
            }

            $propertyName = lcfirst($propertyName);
            $isNewValue = false;

            if (is_string($value)) {
                if (StringUtils::startsWith($value, '@Duration:')) {
                    $value = StringUtils::toDuration(str_replace('@Duration:', '', $value));
                    $isNewValue = true;
                } else if (StringUtils::startsWith($value, '@DataSize:')) {
                    $value = StringUtils::toDataSize(str_replace('@DataSize:', '', $value));
                    $isNewValue = true;
                }
            }

            if ($key !== $propertyName) {
                $data[$propertyName] = $value;
                unset($data[$key]);
            } else if ($isNewValue) {
                $data[$key] = $value;
            }
        }

        foreach ($data as $propertyName => $value) {
            if (!property_exists($this, $propertyName)) {
                continue;
            }

            try {
                $this->$propertyName = $value;
            } catch (Throwable $ex) {
            }
        }
    }

    public function toMap(array $propertyNameToMapKey = [], bool $ignoreNull = false): array
    {
        try {
            $clazz = new ReflectionClass(StringUtils::ensureLeft(get_class($this), "\\"));
        } catch (Throwable $ex) {
            $clazz = null;
        }

        if (!($clazz instanceof ReflectionClass)) {
            return [];
        }

        $map1 = [];

        foreach ($clazz->getProperties() as $property) {
            $propertyName = $property->getName();

            if ($propertyName === '') {
                continue;
            }

            try {
                $value = $this->$propertyName;
            } catch (Throwable $ex) {
                continue;
            }

            if ($ignoreNull && $value === null) {
                continue;
            }

            $mapKey = $this->getMapKeyByProperty($property, $propertyNameToMapKey);

            if ($mapKey === '') {
                continue;
            }

            $map1[$mapKey] = $value;
        }

        return $map1;
    }

    private function getMapKeyByProperty(ReflectionProperty $property, array $propertyNameToMapKey = []): string
    {
        return ReflectUtils::getMapKeyByProperty($property, $propertyNameToMapKey);
    }

    /**
     * @param array $map1
     * @param ReflectionProperty $property
     * @param array $propertyNameToMapKey
     * @return mixed
     */
    private function getMapValueByProperty(array $map1, ReflectionProperty $property, array $propertyNameToMapKey = [])
    {
        return ReflectUtils::getMapValueByProperty($map1, $property, $propertyNameToMapKey);
    }
}
