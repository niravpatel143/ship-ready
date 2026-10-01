<?php

namespace ShipReady\Checks;

use ReflectionClass;

final class CheckMetaReader
{
    /** @var array<string, CheckMeta> */
    private static array $cache = [];

    public static function for(string $class): CheckMeta
    {
        if (isset(self::$cache[$class])) {
            return self::$cache[$class];
        }

        $reflection = new ReflectionClass($class);
        $attributes = $reflection->getAttributes(CheckMeta::class);

        if (empty($attributes)) {
            throw new \RuntimeException(
                "Class {$class} does not have a #[CheckMeta] attribute."
            );
        }

        $meta = $attributes[0]->newInstance();

        self::$cache[$class] = $meta;

        return $meta;
    }

    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
